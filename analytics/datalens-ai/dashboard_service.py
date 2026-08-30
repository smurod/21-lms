"""
End-to-end AI dashboard service — hybrid QL + wizard pipeline.

Pipeline order:
1.  Login to DataLens and create workbook + connection.
2.  Analyze DB schema (biggest tables first, with sample rows).
3.  Ask LLM for charts and SQL.
4.  Validate / repair SQL.
5a. QL charts  (line/area/column/bar/table) → POST /api/charts/v1/charts
5b. Wizard charts (pie/metric/flatTable)    → dataset + wizard_chart_builder
6.  Assemble dashboard layout and publish.

On any failure after DataLens objects have been created the saga rollback
deletes them so the workbook does not accumulate orphaned objects.
"""

from __future__ import annotations

import asyncio
import logging
import re
import time
from collections.abc import Callable
from typing import Any

from config import get_settings
from database_support import ensure_ai_database_supported
from entity_resolver import entity_context, resolve_entities_async
from dashboard_builder import build_dashboard_data
from dataset_pipeline import DatasetSpec, introspect_sql
from datalens_client import DataLensClient
from ql_chart_builder import build_ql_chart
from schema_analyzer import analyze_schema
from sql_pipeline import PlannedChart, is_wizard_chart, plan_and_validate_dashboard
from wizard_chart_builder import (
    build_pie_shared,
    build_metric_shared,
    build_flat_table_shared,
    finalise_shared,
)

logger = logging.getLogger(__name__)

ProgressCallback = Callable[[str, str, int], None]


def _section_chart_kind(chart_type: str) -> str:
    value = chart_type.value if hasattr(chart_type, "value") else str(chart_type)
    return value if value in {"line", "area", "column", "bar", "pie", "table", "metric", "flatTable"} else "bar"


def _progress(callback: ProgressCallback | None, stage: str, message: str, step: int) -> None:
    if callback:
        callback(stage, message, step)


def _datalens_safe_dashboard_name(value: str) -> str:
    """Keep generated entry names within DataLens United Storage validation."""
    allowed = r"[^A-Za-zА-Яа-яЁё0-9_@()%.,:;'|\-–—−$*& ]+"
    safe = re.sub(allowed, " ", value)
    safe = " ".join(safe.split()).strip(" .,:;'|-–—−$*&")
    return safe[:120] or "AI Analytics Dashboard"


def _rollback(
    settings: Any,
    connection_id: str | None,
    chart_ids: list[str],
    dataset_ids: list[str],
    dashboard_id: str | None,
) -> None:
    """Best-effort deletion of all DataLens objects created before a failure."""
    if not (connection_id or chart_ids or dataset_ids or dashboard_id):
        return
    logger.warning(
        "Rolling back DataLens objects: connection=%s charts=%d datasets=%d dashboard=%s",
        connection_id, len(chart_ids), len(dataset_ids), dashboard_id,
    )
    try:
        with DataLensClient(settings) as client:
            client.login()
            for cid in chart_ids:
                try:
                    client.delete_ql_chart(cid)
                    logger.info("Rollback: deleted chart %s", cid)
                except Exception as exc:
                    logger.warning("Rollback: chart %s: %s", cid, exc)
            for did in dataset_ids:
                try:
                    client.delete_dataset(did)
                    logger.info("Rollback: deleted dataset %s", did)
                except Exception as exc:
                    logger.warning("Rollback: dataset %s: %s", did, exc)
            if dashboard_id:
                try:
                    client.delete_entry(dashboard_id, "dash")
                    logger.info("Rollback: deleted dashboard %s", dashboard_id)
                except Exception as exc:
                    logger.warning("Rollback: dashboard %s: %s", dashboard_id, exc)
            if connection_id:
                try:
                    client.delete_connection(connection_id)
                    logger.info("Rollback: deleted connection %s", connection_id)
                except Exception as exc:
                    logger.warning("Rollback: connection %s: %s", connection_id, exc)
    except Exception as exc:
        logger.warning("Rollback: login failed, objects may remain: %s", exc)


async def _create_wizard_chart(
    client: DataLensClient,
    chart: PlannedChart,
    connection_id: str,
    wb_id: str,
    db_url: str,
    dataset_ids: list[str],
    connection_type: str,
) -> dict[str, Any]:
    """Create a wizard chart (pie / metric / flatTable) with its own dataset."""
    # 1. Introspect SQL column types from PostgreSQL
    cols = await introspect_sql(db_url, chart.sql)
    if not cols:
        raise RuntimeError(f"Could not introspect SQL for wizard chart '{chart.title}'")

    spec = DatasetSpec(sql=chart.sql, columns=cols)

    # 2. Create dataset
    dataset_name = f"DS {chart.title[:60]} {int(time.time() * 1000) % 100000:05d}"
    dataset_id = client.create_dataset(
        name=dataset_name,
        workbook_id=wb_id,
        connection_id=connection_id,
        result_schema=spec.result_schema,
        source_id=spec.source_id,
        avatar_id=spec.avatar_id,
        sql=chart.sql,
        raw_schema=spec.raw_schema,
    )
    spec.dataset_id = dataset_id
    dataset_ids.append(dataset_id)
    logger.info("wizard chart '%s': dataset=%s", chart.title, dataset_id)

    # 3. Build wizard shared config
    ct = chart.chart_type.value if hasattr(chart.chart_type, "value") else str(chart.chart_type)
    cols_list = chart.columns  # list of (name, type)

    try:
        if ct == "pie":
            # dimension = first string/category col, measure = last numeric col
            dim_name = next(
                (name for name, typ in cols_list if typ not in {"integer", "float"}),
                cols_list[0][0],
            )
            msr_name = next(
                (name for name, typ in reversed(cols_list) if typ in {"integer", "float"}),
                cols_list[-1][0],
            )
            cats = [str(r.get(dim_name)) for r in (chart.sample_rows or []) if r.get(dim_name) is not None]
            shared = build_pie_shared(spec, dim_name, msr_name, category_values=cats)

        elif ct == "metric":
            msr_name = next(
                (name for name, typ in cols_list if typ in {"integer", "float"}),
                cols_list[0][0],
            )
            shared = build_metric_shared(spec, msr_name)

        elif ct in ("table", "flatTable"):
            shared = build_flat_table_shared(spec, [name for name, _ in cols_list])
            ct = "flatTable"

        else:
            # Fallback: treat as metric
            msr_name = cols_list[-1][0]
            shared = build_metric_shared(spec, msr_name)

    except (ValueError, IndexError) as exc:
        raise RuntimeError(f"Cannot build wizard shared for '{chart.title}': {exc}") from exc

    # 4. Inject real dataset_id
    shared = finalise_shared(shared, dataset_id)

    # 5. Create wizard chart entry
    chart_id = client.create_wizard_chart(
        name=chart.title[:80],
        workbook_id=wb_id,
        chart_type=ct,
        shared=shared,
    )
    return {
        "id": chart_id,
        "title": chart.title,
        "kind": ct,
        "section": chart.section,
        "note": chart.note,
        "sql": chart.sql,
        "dataset_id": dataset_id,
    }


async def create_ai_dashboard(
    *,
    db_url: str,
    message: str = "Визуализируй эту БД",
    workbook_id: str | None = None,
    chart_count: int | None = None,
    cleanup_on_error: bool = True,
    progress_callback: ProgressCallback | None = None,
) -> dict[str, Any]:
    """
    Full hybrid pipeline: schema → LLM → SQL → QL + wizard → DataLens dashboard.

    QL pipeline:     line / area / column / bar / table
    Wizard pipeline: pie / metric / flatTable
    """
    settings = get_settings()
    connection_type = ensure_ai_database_supported(settings, db_url)
    timestamp = int(time.time())

    # Saga state
    _connection_id: str | None = None
    _chart_ids: list[str] = []
    _dataset_ids: list[str] = []
    _dashboard_id: str | None = None
    wb_id: str | None = workbook_id

    try:
        # ── Step 1–3: DataLens login, workbook, connection ─────────────────
        with DataLensClient(settings) as client:
            _progress(progress_callback, "login", "Подключаемся к DataLens…", 1)
            logger.info("step=login")
            client.login()

            _progress(progress_callback, "workbook", "Находим рабочую тетрадь AI Generated…", 2)
            logger.info("step=workbook")
            wb = client.ensure_workbook()
            wb_id = wb_id or (wb.get("workbookId") or wb.get("id"))

            _progress(progress_callback, "connection", "Создаём подключение к аналитической БД…", 3)
            logger.info("step=connection type=%s", connection_type)
            _connection_id = client.create_database_connection(
                name=f"AI Connection {timestamp}",
                workbook_id=wb_id,
                connection_type=connection_type,
            )
            logger.info("step=connection id=%s", _connection_id)

        # ── Step 4: schema analysis ────────────────────────────────────────
        _progress(progress_callback, "schema", "Анализируем таблицы, поля и связи базы данных…", 4)
        logger.info("step=schema")
        schema = await analyze_schema(db_url)
        logger.info("step=schema tables=%d key_tables=%s", len(schema.tables), schema.key_tables[:6])

        resolved_entities = await resolve_entities_async(db_url=db_url, schema=schema, message=message)

        # ── Step 5: generate & validate SQL charts ─────────────────────────
        _progress(progress_callback, "sql", "AI подбирает и проверяет SQL для графиков…", 5)
        logger.info("step=sql_pipeline")
        title, sections, charts, plan = await plan_and_validate_dashboard(
            schema,
            message,
            db_url,
            chart_count=chart_count,
            entity_context=entity_context(resolved_entities),
            progress_callback=progress_callback,
        )
        if not charts:
            raise RuntimeError("LLM did not produce any valid SQL chart.")

        logger.info("step=sql_pipeline charts=%d title=%s", len(charts), title)
        title = _datalens_safe_dashboard_name(
            f"{title} - {time.strftime('%d.%m %H:%M', time.localtime(timestamp))}"
        )

        # ── Steps 6–8: create DataLens objects ─────────────────────────────
        with DataLensClient(settings) as client:
            client.login()
            wb = client.ensure_workbook()
            wb_id = wb_id or (wb.get("workbookId") or wb.get("id"))

            _progress(progress_callback, "charts", f"Создаём {len(charts)} чартов в DataLens…", 6)
            logger.info("step=create_charts count=%d", len(charts))
            created = []

            for index, chart in enumerate(charts, start=1):
                ct = chart.chart_type.value if hasattr(chart.chart_type, "value") else str(chart.chart_type)
                _progress(
                    progress_callback,
                    "charts",
                    f"Создаём график {index}/{len(charts)}: {chart.title}",
                    6,
                )

                if is_wizard_chart(ct):
                    # ── Wizard chart ──────────────────────────────────────
                    logger.info("step=wizard_chart %d/%d %s", index, len(charts), chart.title)
                    try:
                        chart_info = await _create_wizard_chart(
                            client=client,
                            chart=chart,
                            connection_id=_connection_id,
                            wb_id=wb_id,
                            db_url=db_url,
                            dataset_ids=_dataset_ids,
                            connection_type=connection_type,
                        )
                        _chart_ids.append(chart_info["id"])
                        created.append(chart_info)
                        logger.info("step=wizard_chart_created id=%s", chart_info["id"])
                    except Exception as exc:
                        logger.warning("Wizard chart '%s' failed, skipping: %s", chart.title, exc)
                        continue
                else:
                    # ── QL chart ──────────────────────────────────────────
                    logger.info("step=ql_chart %d/%d %s", index, len(charts), chart.title)
                    cat_vals = [
                        row.get(chart.columns[0][0])
                        for row in (chart.sample_rows or [])
                        if chart.columns
                    ]
                    data = build_ql_chart(
                        connection_id=_connection_id,
                        sql=chart.sql,
                        chart_type=ct,
                        columns=chart.columns,
                        connection_type=connection_type,
                        category_values=cat_vals,
                    )
                    chart_id = client.create_ql_chart(
                        name=chart.title[:80],
                        workbook_id=wb_id,
                        data=data,
                    )
                    _chart_ids.append(chart_id)
                    created.append(
                        {
                            "id": chart_id,
                            "title": chart.title,
                            "kind": _section_chart_kind(chart.chart_type),
                            "section": chart.section,
                            "note": chart.note,
                            "sql": chart.sql,
                        }
                    )
                    logger.info("step=ql_chart_created id=%s", chart_id)

            if not created:
                raise RuntimeError("No charts were successfully created.")

            # ── Dashboard layout ──────────────────────────────────────────
            _progress(progress_callback, "layout", "Собираем сетку и секции dashboard…", 7)
            logger.info("step=build_layout")
            section_map = {s["title"]: s for s in sections}
            built_sections = []
            for section_title in section_map:
                section_charts = [c for c in created if c["section"] == section_title]
                if section_charts:
                    built_sections.append(
                        {
                            "title": section_title,
                            "note": section_map[section_title].get("note", ""),
                            "charts": section_charts,
                        }
                    )

            dashboard_data = build_dashboard_data(
                title=title,
                sections=built_sections,
                tab_title="Overview",
            )

            _progress(progress_callback, "dashboard", "Публикуем готовый dashboard…", 8)
            logger.info("step=create_dashboard title=%s", title)
            _dashboard_id = client.create_dashboard(
                name=f"{title[:70]} {timestamp}",
                workbook_id=wb_id,
                data=dashboard_data,
            )
            logger.info("step=dashboard_created id=%s", _dashboard_id)

            dashboard_url = f"{settings.datalens_base_url.rstrip('/')}/workbooks/{wb_id}"
            embed_url = client.dashboard_embed_url(_dashboard_id, wb_id)

            _progress(progress_callback, "completed", "Dashboard готов.", 9)
            logger.info("step=completed dashboard_id=%s charts=%d", _dashboard_id, len(created))
            return {
                "dashboard_id": _dashboard_id,
                "dashboard_url": dashboard_url,
                "embed_url": embed_url,
                "workbook_id": wb_id,
                "connection_id": _connection_id,
                "title": title,
                "charts": created,
                "plan": plan.model_dump(mode="json"),
            }

    except Exception:
        logger.exception("create_ai_dashboard failed")
        if cleanup_on_error:
            _rollback(settings, _connection_id, _chart_ids, _dataset_ids, _dashboard_id)
        raise
