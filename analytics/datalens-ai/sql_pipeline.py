"""
AI pipeline: schema -> one chart at a time -> validated SQL.
"""

from __future__ import annotations

import json
import logging
from dataclasses import dataclass
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path

from chart_dedup import sql_fingerprint, title_fingerprint
from chart_sanity import chart_shape_error
from data_profile import build_data_profile, format_data_profile
from llm_client import ChatMessage, get_llm_client
from models.schema import ChartPlan, ChartType, DashboardPlan
from sql_validator import count_nonzero_rows, count_query_rows, validate_sql as validate_sql_request

logger = logging.getLogger(__name__)

PROMPTS_DIR = Path(__file__).parent / "prompts"
ALLOWED_TYPES = {"line", "area", "column", "bar", "pie", "table", "metric"}

CHART_RESPONSE_SCHEMA = {
    "type": "object",
    "additionalProperties": False,
    "properties": {
        "title": {"type": "string", "minLength": 1, "maxLength": 160},
        "section": {"type": "string", "minLength": 1, "maxLength": 120},
        "note": {"type": "string", "maxLength": 300},
        "chart_type": {"type": "string", "enum": ["line", "area", "column", "bar", "pie", "table", "metric"]},
        "sql": {"type": "string", "minLength": 1},
        "columns": {
            "type": "array",
            "minItems": 1,
            "maxItems": 12,
            "items": {
                "type": "object",
                "additionalProperties": False,
                "properties": {
                    "name": {"type": "string", "minLength": 1},
                    "data_type": {"type": "string", "enum": ["string", "integer", "float", "date"]},
                },
                "required": ["name", "data_type"],
            },
        },
    },
    "required": ["title", "section", "note", "chart_type", "sql", "columns"],
}

SQL_FIX_SCHEMA = {
    "type": "object",
    "additionalProperties": False,
    "properties": {
        "sql": {"type": "string", "minLength": 1},
        "columns": CHART_RESPONSE_SCHEMA["properties"]["columns"],
    },
    "required": ["sql", "columns"],
}


MAX_CATEGORY_ROWS = 20
MAX_PIE_ROWS = 7
MAX_TIME_ROWS = 60


def chart_density_error(chart_type: str, result_rows: int) -> str | None:
    """Reject charts whose data makes them unreadable or meaningless.

    Both extremes are rejected: a comparison chart with a single category has
    nothing to compare (a lone bar/sector — such data belongs in a KPI metric),
    and a dynamics chart with a single point has no dynamics.
    """
    kind = chart_type.lower()
    if kind in {"metric", "table", "flattable"}:
        return None  # no density limit for single-value or detail charts
    if kind == "pie" and not 2 <= result_rows <= MAX_PIE_ROWS:
        return f"Pie требует от 2 до {MAX_PIE_ROWS} категорий, получено {result_rows}."
    if kind in {"column", "bar"}:
        if result_rows < 2:
            return (
                "Для сравнения нужно минимум 2 категории, получена 1. "
                "Одиночное значение лучше показать KPI-метрикой (metric)."
            )
        if result_rows > MAX_CATEGORY_ROWS:
            return f"Bar/column содержит слишком много категорий ({result_rows}). Нужен TOP-N, bins или агрегация."
    if kind in {"line", "area"}:
        if result_rows < 2:
            return "Для динамики нужно минимум 2 точки по времени, получена 1. Покажите итог KPI-метрикой (metric)."
        if result_rows > MAX_TIME_ROWS:
            return f"Line/area содержит слишком много точек ({result_rows}). Нужна агрегация по неделям или месяцам."
    return None


def _load_prompt(file_name: str, **kwargs: str) -> str:
    text = (PROMPTS_DIR / file_name).read_text(encoding="utf-8")
    for key, value in kwargs.items():
        text = text.replace("{" + key + "}", value)
    return text


# Chart types that must use the wizard pipeline (dataset + wizard_chart_builder).
# Only metric/flatTable truly require a dataset: pie and table have working QL
# templates, and this DataLens build rejects the old mix.createChartV1 action
# that previously broke every wizard chart.
WIZARD_CHART_TYPES = {"metric", "flattable"}
QL_CHART_TYPES = {"line", "area", "column", "bar", "table", "pie"}


def is_wizard_chart(chart_type: str) -> bool:
    """Return True when this chart type requires a DataLens dataset (wizard)."""
    return chart_type.lower() in WIZARD_CHART_TYPES


@dataclass
class PlannedChart:
    title: str
    chart_type: ChartType
    sql: str
    columns: list[tuple[str, str]]
    section: str
    note: str
    sample_rows: list[dict] | None = None
    # For wizard charts: category values from sample rows (for colour binding)
    category_values: list[object] | None = None


def _format_schema(schema_analysis, max_tables: int = 8) -> str:
    """Ranked schema text: tables ordered by the composite data profile."""
    profile = build_data_profile(schema_analysis)
    selected = profile.top_tables[:max_tables]
    score_by_table = {item.table: item for item in profile.ranked}
    table_by_name = {table.name: table for table in schema_analysis.tables}

    lines: list[str] = [format_data_profile(profile, top_n=max_tables)]
    for name in selected:
        table = table_by_name.get(name)
        if table is None:
            continue
        stats = score_by_table.get(name)
        cols = []
        for col in table.columns[:12]:
            flags = []
            if col.is_primary_key:
                flags.append("PK")
            if col.is_foreign_key:
                flags.append(f"FK->{col.foreign_table}.{col.foreign_column}")
            flag_text = f" [{', '.join(flags)}]" if flags else ""
            cols.append(f"{col.name} {col.data_type}{flag_text}")
        block = (
            f"Таблица {table.name} (строк: {table.row_count}, связей: {stats.links if stats else 0}):\n  "
            + "\n  ".join(cols)
        )
        # Append 2 sample rows so LLM sees real values and writes accurate SQL.
        if table.sample_rows:
            samples = "; ".join(str(row) for row in table.sample_rows[:2])
            block += f"\n  Примеры: {samples}"
        lines.append(block)
    rels = [
        r for r in schema_analysis.relationships
        if r["from_table"] in selected
        or r["to_table"] in selected
    ]
    if rels:
        lines.append("Связи:")
        for rel in rels:
            lines.append(f"- {rel['from_table']}.{rel['from_column']} -> {rel['to_table']}.{rel['to_column']}")
    return "\n\n".join(lines)


def _normalize_data_type(value: str) -> str:
    v = (value or "").lower()
    if "int" in v or "serial" in v:
        return "integer"
    if any(x in v for x in ("numeric", "decimal", "real", "double", "number", "money")):
        return "float"
    if "date" in v or "time" in v:
        return "date"
    return "string"


def is_aggregated_query(sql: str) -> bool:
    """Recognize a category aggregation that is clearer as a bar/column chart."""
    return "group by" in (sql or "").casefold()


def dashboard_title_from_message(message: str) -> str:
    """Create a readable dashboard title from the admin's natural-language goal."""
    text = " ".join((message or "").split()).strip(" .!?:;")
    if text.casefold() in {"визуализируй бд", "визуализируй эту бд", "visualize database"}:
        text = "Обзор LMS"
    text = text[:78].rstrip()
    return f"AI Analytics — {text or 'Обзор LMS'}"


def aggregated_bar_columns(columns: list[tuple[str, str]]) -> list[tuple[str, str]]:
    """Keep category + primary metric for a grouped aggregation rendered as bar."""
    if len(columns) < 2:
        return columns
    metric = next(
        (column for column in columns[1:] if column[1] in {"integer", "float"}),
        columns[-1],
    )
    return [columns[0], metric]


def columns_from_sample_rows(rows: list[dict], fallback: list[tuple[str, str]]) -> list[tuple[str, str]]:
    """Derive QL fields from actual PostgreSQL result values, not LLM guesses."""
    if not rows:
        return fallback

    inferred: list[tuple[str, str]] = []
    for name in rows[0]:
        value = next((row.get(name) for row in rows if row.get(name) is not None), None)
        # QL cannot reliably render a selected field whose sampled values are
        # all NULL: PostgreSQL exposes no usable runtime type for it. Do not
        # place such a field into a chart placeholder.
        if value is None:
            continue
        if isinstance(value, bool):
            data_type = "string"
        elif isinstance(value, int):
            data_type = "integer"
        elif isinstance(value, (float, Decimal)):
            data_type = "float"
        elif isinstance(value, (date, datetime)):
            data_type = "date"
        else:
            data_type = "string"
        inferred.append((name, data_type))
    return inferred


async def decide_chart_count(client, schema_text: str, message: str, entity_context: str) -> int:
    """Ask the LLM for an appropriate dashboard scope instead of using a fixed count."""
    prompt = _load_prompt(
        "dashboard_count.md",
        schema=schema_text,
        message=message,
        entity_context=entity_context,
    )
    for attempt in range(2):
        try:
            data = await client.chat_json(
                [
                    ChatMessage(role="system", content="Определи масштаб dashboard по профилю данных и запросу."),
                    ChatMessage(role="user", content=prompt if attempt == 0 else "Выбери число charts от 1 до 12, стремись к 8."),
                ],
                schema_name="dashboard_chart_count",
                schema={
                    "type": "object",
                    "additionalProperties": False,
                    "properties": {"chart_count": {"type": "integer", "minimum": 1, "maximum": 12}},
                    "required": ["chart_count"],
                },
                max_tokens=1024,
            )
            return int(data["chart_count"])
        except (KeyError, TypeError, ValueError, json.JSONDecodeError):
            continue
    raise RuntimeError("LLM did not return a valid chart_count decision.")


async def _generate_one_chart(
    client,
    schema_text: str,
    message: str,
    avoid_titles: list[str],
    used_types: list[str],
    used_sections: list[str],
    entity_context: str,
    index: int,
):
    avoid = "\n".join(f"- {t}" for t in avoid_titles) or "- нет"
    types = ", ".join(used_types) or "нет"
    sections = ", ".join(used_sections) or "нет"
    prompt = _load_prompt(
        "chart_one.md",
        schema=schema_text,
        message=message,
        avoid=avoid,
        used_types=types,
        used_sections=sections,
        entity_context=entity_context,
    )
    system = ChatMessage(role="system", content="Возвращай только JSON.")
    user = ChatMessage(role="user", content=prompt)

    data = await client.chat_json(
        [system, user],
        schema_name="dashboard_chart_plan",
        schema=CHART_RESPONSE_SCHEMA,
        max_tokens=2048,
    )

    chart_type_raw = str(data.get("chart_type", "table")).lower()
    if chart_type_raw not in ALLOWED_TYPES:
        chart_type_raw = "table"
    columns = [
        (c["name"], _normalize_data_type(c.get("data_type", "string")))
        for c in data.get("columns", [])
        if c.get("name")
    ]
    if not columns:
        return None
    return PlannedChart(
        title=data.get("title") or f"Chart {index}",
        chart_type=ChartType(chart_type_raw),
        sql=str(data.get("sql", "")).strip().rstrip(";"),
        columns=columns,
        section=data.get("section") or "Overview",
        note=data.get("note") or "",
    )


async def _validate_and_fix(client, chart, schema_text, db_url, message: str, retries=2):
    sql = chart.sql
    # Pie charts need all category values for colorsConfig.mountedColors.
    # Use a higher limit so no category is missing from the sample.
    dry_run_limit = 8 if chart.chart_type.value == "pie" else 5
    for attempt in range(retries + 1):
        valid, rows, error = await validate_sql_request(db_url, sql, limit=dry_run_limit)
        if valid:
            if not rows:
                error = "0 строк. Выбери другие поля/таблицы."
            else:
                chart.sql = sql
                chart.columns = columns_from_sample_rows(rows, chart.columns)

                result_rows, count_error = await count_query_rows(db_url, sql)
                if count_error:
                    error = f"Не удалось проверить размер результата: {count_error}"
                else:
                    error = chart_density_error(chart.chart_type.value, result_rows or 0)
                if error is None and chart.chart_type.value == "pie":
                    # A pie with a single non-zero sector renders as a plain
                    # gray circle — there is nothing to compare.
                    measure = next(
                        (name for name, t in chart.columns if t in {"integer", "float"}),
                        None,
                    )
                    if measure:
                        nonzero, nz_error = await count_nonzero_rows(db_url, sql, measure)
                        if nz_error is None and nonzero < 2:
                            error = (
                                "У pie должен быть минимум 2 ненулевых сектора, "
                                f"получен {nonzero}. Такие данные лучше показать KPI-метрикой."
                            )
                if error is None:
                    chart.sample_rows = rows
                    print("      rows:", len(rows), "result rows:", result_rows, "sample:", rows[:2])
                    return chart
        if attempt == retries:
            return None
        fix_prompt = _load_prompt(
            "chart_fix.md",
            schema=schema_text,
            sql=sql,
            error=str(error),
            title=chart.title,
            chart_type=chart.chart_type.value,
            message=message,
        )
        fixed = await client.chat_json(
            [
                ChatMessage(role="system", content="Исправь SQL строго по схеме JSON."),
                ChatMessage(role="user", content=fix_prompt),
            ],
            schema_name="sql_fix",
            schema=SQL_FIX_SCHEMA,
            max_tokens=2048,
        )
        sql = str(fixed["sql"]).strip().rstrip(";")
        if fixed.get("columns"):
            chart.columns = [
                (c["name"], _normalize_data_type(c.get("data_type", "string")))
                for c in fixed["columns"] if c.get("name")
            ]
    return chart


async def plan_and_validate_dashboard(
    schema_analysis,
    message: str,
    db_url: str,
    chart_count: int | None = None,
    max_attempts: int | None = None,
    max_retries=2,
    entity_context: str = "",
    progress_callback=None,
):
    schema_text = _format_schema(schema_analysis)
    client = get_llm_client()
    if chart_count is None:
        chart_count = await decide_chart_count(client, schema_text, message, entity_context)
        print(f"   AI selected dashboard scope: {chart_count} charts")

    charts: list[PlannedChart] = []
    sections: list[dict] = []
    tried_titles: list[str] = []
    seen_sql: set[str] = set()
    seen_titles: set[str] = set()

    attempts = 0
    max_attempts = max_attempts or max(8, chart_count * 2)
    while len(charts) < chart_count and attempts < max_attempts:
        attempts += 1
        index = len(charts) + 1
        if progress_callback:
            progress_callback(
                "sql",
                f"AI готовит график {index} из {chart_count}, попытка {attempts}…",
                5,
            )
        print(f"   generating chart {index}/{chart_count} (attempt {attempts})...")
        try:
            chart = await _generate_one_chart(
                client,
                schema_text,
                message,
                tried_titles + [c.title for c in charts],
                [c.chart_type.value for c in charts],
                [section["title"] for section in sections],
                entity_context,
                index,
            )
        except Exception as exc:
            print("   generation failed:", exc)
            continue
        if not chart:
            continue
        print("   proposed:", chart.chart_type.value, chart.title)
        fixed = await _validate_and_fix(client, chart, schema_text, db_url, message, max_retries)
        if fixed:
            title_key = title_fingerprint(fixed.title)
            if title_key in seen_titles:
                tried_titles.append(fixed.title)
                print("   skipped duplicate chart title:", fixed.title)
                continue
            shape_error = chart_shape_error(fixed.chart_type.value, fixed.columns)
            if shape_error:
                tried_titles.append(fixed.title)
                print("   skipped invalid chart shape:", fixed.title, "-", shape_error)
                continue
            fingerprint = sql_fingerprint(fixed.sql)
            if fingerprint in seen_sql:
                tried_titles.append(fixed.title)
                print("   skipped duplicate SQL:", fixed.title)
                continue
            seen_sql.add(fingerprint)
            seen_titles.add(title_key)
            charts.append(fixed)
            if not any(s["title"] == fixed.section for s in sections):
                sections.append({"title": fixed.section, "note": fixed.note})
            print("   valid:", fixed.title)
        else:
            tried_titles.append(chart.title)
            print("   invalid after fixes:", chart.title)

    if not charts:
        raise RuntimeError("LLM did not produce any valid SQL chart.")

    dashboard_title = dashboard_title_from_message(message)
    plan = DashboardPlan(
        dashboard_title=dashboard_title,
        charts=[
            ChartPlan(
                title=c.title,
                chart_type=c.chart_type,
                table="",
                sql=c.sql,
                x_field=c.columns[0][0] if c.columns else None,
                y_field=c.columns[1][0] if len(c.columns) > 1 else None,
            )
            for c in charts
        ],
    )
    return dashboard_title, sections, charts, plan
