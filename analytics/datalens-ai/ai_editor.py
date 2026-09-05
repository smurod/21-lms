"""AI editor for dashboards: update/add/delete/reorder charts."""

from __future__ import annotations

import asyncio
import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from chart_dedup import sql_fingerprint, title_fingerprint
from chart_sanity import chart_shape_error
from datalens_client import AsyncDataLensClient
from llm_client import ChatMessage, get_llm_client
from ql_chart_builder import build_ql_chart, normalize_chart_type
from schema_analyzer import analyze_schema
from utils import _extract_json_from_response
from sql_pipeline import _format_schema, _normalize_data_type, aggregated_bar_columns, chart_density_error, columns_from_sample_rows
from sql_validator import count_query_rows, validate_sql as validate_sql_request

PROMPTS_DIR = Path(__file__).parent / "prompts"


@dataclass
class ChartState:
    entry_id: str
    title: str
    chart_type: str
    sql: str
    columns: list[tuple[str, str]]
    section: str
    note: str = ""
    category_values: list[object] = field(default_factory=list)
    is_new: bool = False
    changed: bool = False


def _load_prompt(file_name: str, **kwargs: str) -> str:
    text = (PROMPTS_DIR / file_name).read_text(encoding="utf-8")
    for key, value in kwargs.items():
        text = text.replace("{" + key + "}", value)
    return text


def _dashboard_charts(dashboard_entry: dict) -> list[dict]:
    """Read chart order and section titles from the real dashboard layout."""
    refs: list[dict] = []
    for tab in dashboard_entry["entry"]["data"].get("tabs", []):
        positions = {row["i"]: row for row in tab.get("layout", [])}
        items = sorted(
            tab.get("items", []),
            key=lambda item: (
                positions.get(item.get("id"), {}).get("y", 0),
                positions.get(item.get("id"), {}).get("x", 0),
            ),
        )
        current_section = tab.get("title") or "Overview"

        for item in items:
            if item.get("type") == "title":
                current_section = item.get("data", {}).get("text") or current_section
                continue
            if item.get("type") != "widget":
                continue
            for chart_tab in item.get("data", {}).get("tabs", []):
                if chart_tab.get("chartId"):
                    refs.append({
                        "entry_id": chart_tab["chartId"],
                        "title": chart_tab.get("title") or "",
                        "item_id": item["id"],
                        "section": current_section,
                    })
    return refs


async def _enrich_charts(client, refs: list[dict]) -> list[ChartState]:
    states: list[ChartState] = []
    for ref in refs:
        entry = await client.get_ql_chart(ref["entry_id"])
        shared = entry.get("_shared") or {}
        viz = shared.get("visualization") or {}
        columns = []
        for placeholder in viz.get("placeholders", []):
            for field in placeholder.get("items", []):
                columns.append((
                    field.get("guid") or field.get("title"),
                    _normalize_data_type(field.get("data_type", "string")),
                ))
        states.append(ChartState(
            entry_id=ref["entry_id"],
            title=ref["title"],
            chart_type=viz.get("id", "table"),
            sql=shared.get("queryValue", ""),
            columns=columns,
            section=ref.get("section") or "Overview",
        ))
    return states


def _describe_state(charts: list[ChartState]) -> str:
    lines = []
    for index, chart in enumerate(charts, start=1):
        lines.append(
            f"{index}. chart_id={chart.entry_id}, title={chart.title}, "
            f"section={chart.section}, type={chart.chart_type}\n"
            f"   SQL: {chart.sql}\n   columns: {chart.columns}"
        )
    return "\n".join(lines)


# Wizard chart types as stored in ChartState.chart_type (from DataLens viz id).
WIZARD_STATE_TYPES = {"metric", "flattable", "flat_table", "flat-table"}


def _charts_payload(charts: list[ChartState]) -> list[dict]:
    """Final dashboard charts in the same shape the generate pipeline returns.

    Callers (Laravel + the job summary) rely on real titles instead of opaque
    entry ids, otherwise the AI reply invents chart descriptions.
    """
    return [
        {
            "id": chart.entry_id,
            "title": chart.title,
            "kind": str(chart.chart_type).lower(),
            "section": chart.section,
            "note": chart.note,
            "sql": chart.sql,
        }
        for chart in charts
    ]


async def _delete_orphan_charts(client, orphans: list[dict], *, keep_ids: set[str]) -> int:
    """Remove workbook chart entries that are no longer placed on any dashboard.

    Wizard charts are deleted together with their datasets. Cleanup failures
    are logged but never fail the job: the dashboard is already updated.
    """
    removed = 0
    for ref in orphans:
        entry_id = ref.get("entry_id")
        if not entry_id or entry_id in keep_ids:
            continue
        try:
            entry = await client.get_ql_chart(entry_id)
        except Exception as exc:
            print(f"   orphan cleanup: skip unreadable chart {entry_id}: {exc}")
            continue
        shared = entry.get("_shared") or {}
        dataset_ids = shared.get("datasetsIds") or []
        try:
            if dataset_ids:
                await client.delete_wizard_chart(entry_id)
                for dataset_id in dataset_ids:
                    try:
                        await client.delete_dataset(dataset_id)
                    except Exception:
                        continue
            else:
                await client.delete_ql_chart(entry_id)
            removed += 1
            print("   orphan cleanup: deleted", entry_id, ref.get("title") or "")
        except Exception as exc:
            print(f"   orphan cleanup: failed for {entry_id}: {exc}")
    return removed


def _discard_duplicate_sql_actions(charts: list[ChartState], actions: list[dict]) -> list[dict]:
    """Reject additions/updates whose SQL duplicates another dashboard chart."""
    owners = {
        sql_fingerprint(chart.sql): chart.entry_id
        for chart in charts
        if chart.sql.strip()
    }
    title_owners = {
        title_fingerprint(chart.title): chart.entry_id
        for chart in charts
        if chart.title.strip()
    }
    accepted: list[dict] = []

    for action in actions:
        if action.get("action") not in {"add", "update"} or not action.get("sql"):
            accepted.append(action)
            continue

        action_title = str(action.get("title") or "")
        title_owner = title_owners.get(title_fingerprint(action_title)) if action_title else None
        current_id = action.get("chart_id") if action.get("action") == "update" else None
        if title_owner and title_owner != current_id:
            print("   skipped duplicate chart title:", action_title, "duplicates", title_owner)
            continue

        action_columns = [
            (column["name"], _normalize_data_type(column.get("data_type", "string")))
            for column in action.get("columns", [])
            if column.get("name")
        ]
        if not action_columns:
            print("   skipped chart action without output columns:", action.get("title") or action.get("chart_id"))
            continue
        shape_error = chart_shape_error(action.get("chart_type", "table"), action_columns)
        if shape_error:
            print("   skipped invalid chart shape:", action.get("title") or action.get("chart_id"), "-", shape_error)
            continue

        fingerprint = sql_fingerprint(str(action["sql"]))
        owner = owners.get(fingerprint)
        if owner and owner != current_id:
            print(
                "   skipped duplicate SQL action:",
                action.get("action"),
                action.get("title") or current_id,
                "duplicates",
                owner,
            )
            continue

        pending_id = current_id or f"pending-{len(accepted)}"
        owners[fingerprint] = pending_id
        if action_title:
            title_owners[title_fingerprint(action_title)] = pending_id
        accepted.append(action)

    return accepted


def _apply_actions(charts, actions):
    by_id = {c.entry_id: c for c in charts}
    new_charts: list[ChartState] = []
    deleted_ids: list[str] = []
    # Wizard chart types cannot be added by the editor: they need their own
    # dataset, which only the generate pipeline provisions.
    unsupported_add_types = {"metric", "flattable", "flat_table", "flat-table"}

    for action in actions:
        kind = action.get("action")
        if kind == "update":
            chart = by_id.get(action.get("chart_id"))
            if not chart:
                continue
            if chart.chart_type.lower() in WIZARD_STATE_TYPES:
                # Wizard charts (KPI metric / flatTable) have no queryValue;
                # rebuilding them through build_ql_chart would silently turn
                # a metric card into a bar chart.
                print("   skip update of wizard chart:", chart.title)
                continue
            if action.get("title"):
                chart.title = action["title"]
            if action.get("chart_type"):
                chart.chart_type = normalize_chart_type(action["chart_type"])
            if action.get("sql"):
                chart.sql = action["sql"].strip().rstrip(";")
            if action.get("columns"):
                chart.columns = [
                    (c["name"], _normalize_data_type(c.get("data_type", "string")))
                    for c in action["columns"] if c.get("name")
                ]
            if action.get("section"):
                chart.section = action["section"]
            if action.get("note"):
                chart.note = action["note"]
            if action.get("_category_values") is not None:
                chart.category_values = list(action["_category_values"])
            chart.changed = True
        elif kind == "add":
            if str(action.get("chart_type", "")).strip().lower() in unsupported_add_types:
                print("   skipped unsupported wizard-type addition:", action.get("title") or "metric")
                continue
            chart = ChartState(
                entry_id=f"new_{len(new_charts)}",
                title=action.get("title", "AI Chart"),
                chart_type=normalize_chart_type(action.get("chart_type", "table")),
                sql=str(action.get("sql", "")).strip().rstrip(";"),
                columns=[
                    (c["name"], _normalize_data_type(c.get("data_type", "string")))
                    for c in action.get("columns", []) if c.get("name")
                ],
                section=action.get("section", "AI Update"),
                note=action.get("note", ""),
                category_values=list(action.get("_category_values", [])),
                is_new=True,
            )
            after_chart_id = action.get("after_chart_id")
            after_index = next(
                (i for i, existing in enumerate(charts) if existing.entry_id == after_chart_id),
                None,
            )
            position = action.get("position")
            if after_index is not None:
                if not action.get("section"):
                    chart.section = charts[after_index].section
                charts.insert(after_index + 1, chart)
            elif isinstance(position, int) and 0 <= position <= len(charts):
                charts.insert(position, chart)
            else:
                charts.append(chart)
            new_charts.append(chart)
        elif kind == "delete":
            chart = by_id.get(action.get("chart_id"))
            if chart:
                charts.remove(chart)
                by_id.pop(chart.entry_id, None)
                if not chart.is_new:
                    deleted_ids.append(chart.entry_id)
        elif kind == "reorder":
            chart = by_id.get(action.get("chart_id"))
            if chart:
                if action.get("section"):
                    chart.section = action["section"]
                charts.remove(chart)
                after_chart_id = action.get("after_chart_id")
                after_index = next(
                    (i for i, existing in enumerate(charts) if existing.entry_id == after_chart_id),
                    None,
                )
                position = action.get("position")
                if after_index is not None:
                    charts.insert(after_index + 1, chart)
                elif isinstance(position, int) and 0 <= position <= len(charts):
                    charts.insert(position, chart)
                else:
                    charts.append(chart)

    updated = [c for c in charts if c.changed and not c.is_new]
    return new_charts, updated, deleted_ids


def _build_sections(charts):
    sections_order: list[str] = []
    grouped: dict[str, list[ChartState]] = {}
    for chart in charts:
        if chart.section not in grouped:
            grouped[chart.section] = []
            sections_order.append(chart.section)
        grouped[chart.section].append(chart)
    return [
        {
            "title": title,
            "note": next((c.note for c in grouped[title] if c.note), ""),
            "charts": [
                {"id": c.entry_id, "title": c.title, "kind": c.chart_type}
                for c in grouped[title]
            ],
        }
        for title in sections_order
    ]


async def edit_dashboard(
    *,
    dashboard_id: str,
    instruction: str,
    db_url: str,
    connection_id: str,
    progress_callback=None,
    cancel_check=None,
) -> dict[str, Any]:
    from datalens_client import DataLensClient
    from config import get_settings
    from database_support import ensure_ai_database_supported
    from entity_resolver import EntityDataUnavailableError, entity_context, resolve_entities_async

    settings = get_settings()
    ensure_ai_database_supported(settings, db_url)
    clear_only = instruction == '__CLEAR_DASHBOARD__'
    replace_mode = instruction.startswith('__REPLACE_DASHBOARD__:')
    if replace_mode:
        instruction = instruction.split(':', 1)[1].strip()
    if clear_only:
        schema_text = ''
        resolved_entities = []
    else:
        if progress_callback:
            progress_callback("schema", "Анализируем актуальную схему базы данных…", 4)
        print("   loading DB schema…")
        schema = await analyze_schema(db_url)
        schema_text = _format_schema(schema)
        resolved_entities = await resolve_entities_async(db_url=db_url, schema=schema, message=instruction)

    with DataLensClient(settings) as sync_client:
        client = AsyncDataLensClient(sync_client)
        if progress_callback:
            progress_callback("load", "Загружаем текущий dashboard и его графики…", 1)
        print("1. Load dashboard…")
        dashboard = await client.get_dashboard(dashboard_id)
        if clear_only:
            from dashboard_builder import build_dashboard_data

            if progress_callback:
                progress_callback("layout", "Очищаем виджеты текущего dashboard…", 7)
            old_refs = _dashboard_charts(dashboard)
            data = build_dashboard_data(
                title=dashboard["entry"].get("name", "AI Analytics Dashboard"),
                sections=[],
                tab_title=dashboard["entry"]["data"]["tabs"][0].get("title", "Overview"),
            )
            # mix.updateDashboardV1 refuses to write while ANY US lock exists
            # (HTTP 423), so only stale UI locks are force-released here — no
            # external lock is held during publication.
            await client.force_release_entry_lock(dashboard_id)
            await client.update_dashboard(dashboard_id, data)
            await _delete_orphan_charts(client, old_refs, keep_ids=set())
            if progress_callback:
                progress_callback("completed", "Dashboard очищен.", 9)
            return {
                "dashboard_id": dashboard_id,
                "added": [],
                "updated": [],
                "deleted": [],
                "cleared": len(old_refs),
                "charts": [],
                "sections": [],
            }

        old_refs = _dashboard_charts(dashboard)
        refs = [] if replace_mode else old_refs
        print("   charts found:", len(refs), "(replacement mode)" if replace_mode else "")
        charts = await _enrich_charts(client, refs)

        # table_ql_node is now supported natively — no backward-compat conversion needed.

        if progress_callback:
            progress_callback("plan", "AI составляет план изменений dashboard…", 5)
        if cancel_check and cancel_check():
            from dashboard_service import JobCancelled

            raise JobCancelled("CANCELED")
        print("2. Ask LLM for changes…")
        prompt = _load_prompt(
            "editor_plan.md",
            schema=schema_text,
            charts=_describe_state(charts),
            message=(instruction + ('\nРежим замены: собери новый dashboard, старые виджеты не сохраняй.' if replace_mode else '')),
            entity_context=entity_context(resolved_entities),
        )
        llm = get_llm_client()
        response = await llm.chat([
            ChatMessage(role="system", content="Возвращай только JSON."),
            ChatMessage(role="user", content=prompt),
        ], max_tokens=4096)
        plan = json.loads(_extract_json_from_response(response.content))
        actions = plan.get("actions", [])
        print("   actions:", [a.get("action") for a in actions])

        if progress_callback:
            progress_callback("sql", "Проверяем SQL для новых и изменённых графиков…", 5)
        validated = []
        empty_chart_actions = 0
        for action in actions:
            if action.get("action") not in ("update", "add") or not action.get("sql"):
                validated.append(action)
                continue
            sql = action["sql"].strip().rstrip(";")
            columns = [
                (c["name"], _normalize_data_type(c.get("data_type", "string")))
                for c in action.get("columns", []) if c.get("name")
            ]
            valid, _rows, error = await validate_sql_request(db_url, sql)
            if not valid:
                fix_prompt = _load_prompt(
                    "chart_fix.md",
                    schema=schema_text,
                    sql=sql,
                    error=str(error),
                    title=str(action.get("title") or "AI chart"),
                    chart_type=str(action.get("chart_type") or "table"),
                    message=instruction,
                )
                fixed_response = await llm.chat([
                    ChatMessage(role="system", content="Возвращай только JSON."),
                    ChatMessage(role="user", content=fix_prompt),
                ], max_tokens=2048)
                fixed = json.loads(_extract_json_from_response(fixed_response.content))
                sql = fixed["sql"].strip().rstrip(";")
                columns = [
                    (c["name"], _normalize_data_type(c.get("data_type", "string")))
                    for c in fixed.get("columns", []) if c.get("name")
                ]
                valid, _rows, error = await validate_sql_request(db_url, sql)
                if not valid:
                    print("   skipped invalid action:", error)
                    continue
            if not _rows:
                empty_chart_actions += 1
                print("   skipped empty chart action:", action.get("title") or action.get("chart_id"))
                continue
            columns = columns_from_sample_rows(_rows, columns)
            action = dict(action)
            result_rows, count_error = await count_query_rows(db_url, sql)
            density_error = count_error or chart_density_error(str(action.get("chart_type") or "bar"), result_rows or 0)
            if density_error:
                print("   skipped dense chart action:", action.get("title") or action.get("chart_id"), "-", density_error)
                continue
            action["_category_values"] = [
                row.get(columns[0][0])
                for row in (_rows or [])
                if columns
            ]
            action["sql"] = sql
            action["columns"] = [{"name": n, "data_type": t} for n, t in columns]
            validated.append(action)

        requested_additions = sum(1 for action in actions if action.get("action") == "add")
        validated = _discard_duplicate_sql_actions(charts, validated)
        accepted_additions = sum(1 for action in validated if action.get("action") == "add")
        if empty_chart_actions and requested_additions and not accepted_additions:
            raise EntityDataUnavailableError(
                "По указанному пользователю или проекту не найдено данных для запрошенной аналитики."
            )
        requested_deletions = sum(1 for action in actions if action.get("action") == "delete")

        # A recreate/replace plan must never delete existing charts when one of
        # its proposed replacements was rejected as duplicate, empty or invalid.
        # Preserve the old dashboard instead of turning it into a partial result.
        if requested_deletions and requested_additions and accepted_additions < requested_additions:
            print(
                "   skipped recreate plan: only",
                accepted_additions,
                "of",
                requested_additions,
                "replacement charts passed validation",
            )
            # Do not leave a dashboard in a partial state: when a requested
            # replacement set is incomplete, apply neither its additions nor
            # deletions. Non-content section/title changes may still proceed.
            validated = [
                action for action in validated
                if action.get("action") not in {"add", "delete"}
            ]

        new_charts, updated_charts, deleted_ids = _apply_actions(charts, validated)

        # Section titles and descriptions belong to dashboard layout, not QL
        # chart data. Apply dedicated update_section actions before rebuilding.
        for action in validated:
            if action.get("action") != "update_section":
                continue
            old_section = action.get("section")
            if not old_section:
                continue
            new_section = action.get("title") or old_section
            note = action.get("note")
            for chart in charts:
                if chart.section == old_section:
                    chart.section = new_section
                    if note is not None:
                        chart.note = note

        if progress_callback:
            progress_callback("charts", "Применяем изменения к QL-чартам…", 6)
        print("3. Apply changes…")
        for chart in new_charts:
            shared = build_ql_chart(
                connection_id=connection_id,
                sql=chart.sql,
                chart_type=chart.chart_type,
                columns=chart.columns,
                connection_type=settings.datalens_db_type,
                category_values=chart.category_values,
            )
            chart.entry_id = await client.create_ql_chart(
                name=chart.title,
                workbook_id=dashboard["entry"]["workbookId"],
                data=shared,
            )
            print("   added:", chart.entry_id, chart.title)

        for chart in updated_charts:
            shared = build_ql_chart(
                connection_id=connection_id,
                sql=chart.sql,
                chart_type=chart.chart_type,
                columns=chart.columns,
                connection_type=settings.datalens_db_type,
                category_values=chart.category_values,
            )
            await client.update_ql_chart(chart.entry_id, shared)
            print("   updated:", chart.entry_id, chart.title)

        for chart_id in deleted_ids:
            await client.delete_ql_chart(chart_id)
            print("   deleted:", chart_id)

        if progress_callback:
            progress_callback("layout", "Пересобираем секции и layout dashboard…", 7)
        print("4. Rebuild dashboard…")
        sections = _build_sections(charts)
        from dashboard_builder import build_dashboard_data

        data = build_dashboard_data(
            title=dashboard["entry"].get("name", "AI Analytics Dashboard"),
            sections=sections,
            tab_title=dashboard["entry"]["data"]["tabs"][0].get("title", "Overview"),
        )
        # mix.updateDashboardV1 refuses to write while ANY US lock exists
        # (HTTP 423 "The entry is locked"). Holding our own lock therefore
        # breaks the update — only stale UI locks are force-released, and the
        # publish is retried a small fixed number of times against a live UI
        # session instead of failing an otherwise valid AI edit immediately.
        lock_error: Exception | None = None
        for attempt in range(1, 4):
            try:
                if cancel_check and cancel_check():
                    from dashboard_service import JobCancelled

                    raise JobCancelled("CANCELED")
                await client.force_release_entry_lock(dashboard_id)
                await client.update_dashboard(dashboard_id, data)
                lock_error = None
                break
            except Exception as exc:
                lock_error = exc
                if "ENTRY_IS_LOCKED" not in str(exc) or attempt == 3:
                    raise
                wait_seconds = attempt * 2
                print(f"   dashboard locked; retry {attempt}/3 in {wait_seconds}s…")
                await asyncio.sleep(wait_seconds)

        if lock_error is not None:
            raise lock_error

        # In replacement mode the old charts are no longer placed anywhere —
        # remove them (and their datasets) so the workbook stays clean.
        if replace_mode:
            if progress_callback:
                progress_callback("layout", "Удаляем неиспользуемые чарты…", 8)
            removed = await _delete_orphan_charts(
                client,
                old_refs,
                keep_ids={c.entry_id for c in charts},
            )
            print("   orphan cleanup removed:", removed)

        # Final sanity check: ensure there is at least one chart.
        if not charts:
            print("   WARNING: dashboard has no charts after editing!")

        print("   dashboard updated")
        if progress_callback:
            progress_callback("completed", "Dashboard обновлён.", 9)
        return {
            "dashboard_id": dashboard_id,
            "added": [c.entry_id for c in new_charts],
            "updated": [c.entry_id for c in updated_charts],
            "deleted": deleted_ids,
            "cleared": len(old_refs) if replace_mode else 0,
            "charts": _charts_payload(charts),
            "sections": sections,
        }
