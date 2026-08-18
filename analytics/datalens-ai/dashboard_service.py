"""
End-to-end AI dashboard service.

Pipeline order:
1. login to DataLens and create workbook/connection;
2. analyze DB schema (biggest tables first);
3. ask LLM for charts and SQL;
4. validate/repair SQL;
5. create QL charts and dashboard.
"""

from __future__ import annotations

import re
import time
from collections.abc import Callable
from typing import Any

from config import get_settings
from database_support import ensure_ai_database_supported
from entity_resolver import entity_context, resolve_entities
from dashboard_builder import build_dashboard_data
from datalens_client import DataLensClient
from ql_chart_builder import build_ql_chart
from schema_analyzer import analyze_schema
from sql_pipeline import plan_and_validate_dashboard

ProgressCallback = Callable[[str, str, int], None]


def _section_chart_kind(chart_type: str) -> str:
    value = chart_type.value if hasattr(chart_type, "value") else str(chart_type)
    return value if value in {"line", "area", "column", "bar", "pie", "table", "metric"} else "table"


def _progress(callback: ProgressCallback | None, stage: str, message: str, step: int) -> None:
    if callback:
        callback(stage, message, step)


def _datalens_safe_dashboard_name(value: str) -> str:
    """Keep generated entry names within DataLens United Storage validation."""
    allowed = r"[^A-Za-zА-Яа-яЁё0-9_@()%.,:;'|\-–—−$*& ]+"
    safe = re.sub(allowed, " ", value)
    safe = " ".join(safe.split()).strip(" .,:;'|-–—−$*&")
    return safe[:120] or "AI Analytics Dashboard"


async def create_ai_dashboard(
    *,
    db_url: str,
    message: str = "Визуализируй эту БД",
    workbook_id: str | None = None,
    chart_count: int | None = None,
    progress_callback: ProgressCallback | None = None,
) -> dict[str, Any]:
    settings = get_settings()
    connection_type = ensure_ai_database_supported(settings, db_url)
    timestamp = int(time.time())

    # 1. Connect to DataLens first. If this fails, we should not spend LLM time.
    with DataLensClient(settings) as client:
        _progress(progress_callback, "login", "Подключаемся к DataLens…", 1)
        print("1. Login to DataLens...")
        client.login()

        _progress(progress_callback, "workbook", "Находим рабочую тетрадь AI Generated…", 2)
        print("2. Ensure workbook...")
        wb = client.ensure_workbook()
        wb_id = workbook_id or (wb.get("workbookId") or wb.get("id"))

        _progress(progress_callback, "connection", "Создаём подключение к аналитической БД…", 3)
        print(f"3. Create {connection_type} connection...")
        connection_id = client.create_database_connection(
            name=f"AI Connection {timestamp}",
            workbook_id=wb_id,
            connection_type=connection_type,
        )

    # 2. Analyze database schema (biggest populated tables first).
    _progress(progress_callback, "schema", "Анализируем таблицы, поля и связи базы данных…", 4)
    print("4. Analyze schema...")
    schema = await analyze_schema(db_url)
    print("   key tables:")
    for table in schema.tables:
        if table.name in schema.key_tables:
            print(f"   - {table.name} ({table.row_count} rows)")

    resolved_entities = resolve_entities(db_url=db_url, schema=schema, message=message)

    # 3. Generate charts one by one and validate SQL.
    _progress(progress_callback, "sql", "AI подбирает и проверяет SQL для графиков…", 5)
    print("5. Generate and validate SQL charts...")
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

    # A timestamp makes repeated generic prompts distinguishable in Laravel's
    # saved dashboard list while retaining the meaningful AI-derived title.
    title = _datalens_safe_dashboard_name(
        f"{title} - {time.strftime('%d.%m %H:%M', time.localtime(timestamp))}"
    )

    # 4. Create DataLens objects.
    with DataLensClient(settings) as client:
        client.login()
        wb = client.ensure_workbook()
        wb_id = wb_id or (wb.get("workbookId") or wb.get("id"))

        _progress(progress_callback, "charts", f"Создаём {len(charts)} QL-чарта в DataLens…", 6)
        print(f"6. Create {len(charts)} QL charts...")
        created = []
        for index, chart in enumerate(charts, start=1):
            _progress(
                progress_callback,
                "charts",
                f"Создаём график {index} из {len(charts)}: {chart.title}",
                6,
            )
            data = build_ql_chart(
                connection_id=connection_id,
                sql=chart.sql,
                chart_type=chart.chart_type.value,
                columns=chart.columns,
                connection_type=connection_type,
                category_values=[
                    row.get(chart.columns[0][0])
                    for row in (chart.sample_rows or [])
                    if chart.columns
                ],
            )
            chart_id = client.create_ql_chart(
                name=chart.title[:80],
                workbook_id=wb_id,
                data=data,
            )
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
            print(f"   {index}/{len(charts)} {chart.title} -> {chart_id}")

        _progress(progress_callback, "layout", "Собираем сетку и секции dashboard…", 7)
        print("7. Build dashboard...")
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
        print("8. Create dashboard...")
        dashboard_id = client.create_dashboard(
            name=f"{title[:70]} {timestamp}",
            workbook_id=wb_id,
            data=dashboard_data,
        )
        dashboard_url = f"{settings.datalens_base_url.rstrip('/')}/workbooks/{wb_id}"
        embed_url = client.dashboard_embed_url(dashboard_id, wb_id)

        _progress(progress_callback, "completed", "Dashboard готов.", 9)
        return {
            "dashboard_id": dashboard_id,
            "dashboard_url": dashboard_url,
            "embed_url": embed_url,
            "workbook_id": wb_id,
            "connection_id": connection_id,
            "title": title,
            "charts": created,
            "plan": plan.model_dump(mode="json"),
        }
