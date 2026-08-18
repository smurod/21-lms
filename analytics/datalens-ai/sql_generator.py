"""
SQL Generator — generates SQL queries using LLM.
"""

from __future__ import annotations

import json
import logging
import re
from pathlib import Path

from llm_client import ChatMessage, get_llm_client
from models.schema import (
    ChartPlan,
    ChartType,
    DashboardPlan,
    SQLQuery,
    SchemaAnalysis,
    TableInfo,
)

logger = logging.getLogger(__name__)

PROMPTS_DIR = Path(__file__).parent / "prompts"


def _load_prompt(name: str) -> str:
    """Load prompt template from file."""
    path = PROMPTS_DIR / f"{name}.txt"
    return path.read_text(encoding="utf-8")


def _load_few_shot_examples() -> list[dict]:
    """Load few-shot SQL examples."""
    path = PROMPTS_DIR.parent / "examples" / "sql_examples.json"
    if path.exists():
        return json.loads(path.read_text(encoding="utf-8"))
    return []


def _extract_json_from_response(content: str) -> str:
    """
    Extract JSON from LLM response.
    Handles:
    - Markdown code blocks: ```json ... ```
    - Multiple JSON objects in response
    - Extra text before/after JSON
    """
    content = content.strip()

    # Strategy 1: Remove markdown code blocks
    # Match ```json ... ``` or ``` ... ```
    markdown_match = re.search(r'```(?:json)?\s*\n?(.*?)\n?```', content, re.DOTALL)
    if markdown_match:
        return markdown_match.group(1).strip()

    # Strategy 2: Find JSON object by matching braces
    # Find the first { and last }
    first_brace = content.find('{')
    last_brace = content.rfind('}')
    if first_brace != -1 and last_brace != -1 and last_brace > first_brace:
        return content[first_brace:last_brace + 1]

    # Strategy 3: Return as-is (might be plain SQL)
    return content


def _extract_sql_from_value(value) -> str:
    """
    Extract SQL string from a value that might be:
    - A plain SQL string
    - A JSON string containing SQL
    - A dict with sql_query or sql field
    """
    if isinstance(value, str):
        # Check if it's a JSON string containing SQL
        value = value.strip()
        if value.startswith('{') and value.endswith('}'):
            try:
                inner = json.loads(value)
                # Try common field names
                for key in ['sql_query', 'sql', 'query']:
                    if key in inner:
                        return str(inner[key])
                # If no known key, return the first string value
                for v in inner.values():
                    if isinstance(v, str) and ('SELECT' in v.upper() or 'FROM' in v.upper()):
                        return v
            except json.JSONDecodeError:
                pass
        return value
    elif isinstance(value, dict):
        for key in ['sql_query', 'sql', 'query']:
            if key in value:
                return str(value[key])
    return str(value)


def _build_schema_summary(analysis: SchemaAnalysis, max_tables: int = 10) -> str:
    """Build a concise schema summary for LLM context."""
    lines = []
    # Only include top key tables to avoid overflowing context
    key_tables_limited = analysis.key_tables[:max_tables]
    for table in analysis.tables:
        if table.name in key_tables_limited:
            cols = ", ".join(f"{c.name} ({c.data_type})" for c in table.columns[:10])
            lines.append(f"- **{table.name}**: {cols}")
            if table.has_metrics:
                lines.append(f"  Metrics: {', '.join(table.has_metrics)}")
            if table.has_timestamp:
                ts_cols = [c.name for c in table.columns
                          if c.data_type.lower() in ('timestamp', 'timestamptz', 'date', 'datetime')]
                lines.append(f"  Time: {', '.join(ts_cols)}")
    return "\n".join(lines)


async def generate_sql(
    db_url: str,
    table: str,
    goal: str,
    chart_type: ChartType | None = None,
    schema_context: str | None = None,
) -> SQLQuery:
    """Generate SQL query for a given visualization goal."""
    prompt_template = _load_prompt("sql_generation")

    metrics = "unknown"
    dimensions = "unknown"
    time_field = "unknown"

    prompt = prompt_template.format(
        table_name=table,
        goal=goal,
        metrics=metrics,
        dimensions=dimensions,
        time_field=time_field,
        chart_type=chart_type.value if chart_type else "auto",
    )

    # Add few-shot examples
    examples = _load_few_shot_examples()
    if examples:
        examples_text = "\n\nДополнительные примеры:\n"
        for ex in examples[:3]:
            examples_text += f"- {ex['goal']}: {ex['sql']}\n"
        prompt += examples_text

    messages = [
        ChatMessage(
            role="system",
            content="Ты — SQL-эксперт для PostgreSQL. Отвечай ТОЛЬКО JSON объектом "
                    "с полями: sql (строка SQL), chart_type, title. "
                    "НЕ оборачивай ответ в markdown блоки.",
        ),
        ChatMessage(role="user", content=prompt),
    ]

    client = get_llm_client()
    response = await client.chat(messages)

    content = response.content.strip()

    # Extract JSON from potentially messy response
    json_str = _extract_json_from_response(content)

    try:
        data = json.loads(json_str)

        # Extract SQL - handle nested formats
        sql = _extract_sql_from_value(data.get("sql", ""))
        if not sql or sql == json_str:
            # Fallback: maybe the whole JSON is wrapped differently
            sql = _extract_sql_from_value(data)

        return SQLQuery(
            sql=sql,
            chart_type=ChartType(data.get("chart_type", chart_type.value or "table")),
            table=table,
            title=data.get("title", goal),
            x_field=data.get("x_field"),
            y_field=data.get("y_field"),
            business_question=data.get("business_question", goal),
        )
    except (json.JSONDecodeError, ValueError) as e:
        logger.warning(f"JSON parse failed, treating as raw SQL: {e}")
        # Fallback: the whole response might be SQL
        sql = _extract_sql_from_value(content)
        return SQLQuery(
            sql=sql,
            chart_type=chart_type or ChartType.TABLE,
            table=table,
            title=goal,
        )


async def plan_dashboard(
    schema_analysis: SchemaAnalysis,
    message: str = "Визуализируй эту БД",
) -> DashboardPlan:
    """Generate a complete dashboard plan from schema analysis."""
    prompt_template = _load_prompt("dashboard_planning")

    # Limit context to avoid overflow
    schema_summary = _build_schema_summary(schema_analysis, max_tables=10)

    key_tables_info = []
    for table in schema_analysis.tables:
        if table.name in schema_analysis.key_tables[:10]:  # Limit to 10
            key_tables_info.append({
                "name": table.name,
                "metrics": table.has_metrics,
                "time_field": next(
                    (c.name for c in table.columns
                     if c.data_type.lower() in ('timestamp', 'timestamptz', 'date')),
                    None,
                ),
                "has_relations": any(c.is_foreign_key for c in table.columns),
            })

    prompt = prompt_template.replace("{key_tables}", json.dumps(key_tables_info, indent=2, ensure_ascii=False))

    messages = [
        ChatMessage(
            role="system",
            content="Ты — BI-аналитик. Отвечай ТОЛЬКО JSON объектом без markdown блоков. "
                    "Формат: {\"dashboard_title\": \"...\", \"charts\": [{\"title\": ..., \"chart_type\": ..., \"table\": ..., \"sql\": ..., \"x_field\": ..., \"y_field\": ...}]}. "
                    "Все SQL-запросы должны быть для PostgreSQL.",
        ),
        ChatMessage(role="user", content=prompt),
    ]

    client = get_llm_client()
    response = await client.chat(messages)

    content = response.content.strip()
    logger.info(f"LLM raw response (first 300 chars): {content[:300]}")

    # Robust JSON extraction
    json_str = _extract_json_from_response(content)
    logger.info(f"Extracted JSON (first 300 chars): {json_str[:300]}")

    try:
        data = json.loads(json_str)
        logger.info(f"Parsed JSON keys: {list(data.keys())}")

        charts = []
        for i, chart_data in enumerate(data.get("charts", [])):
            try:
                sql = _extract_sql_from_value(chart_data.get("sql", ""))
                charts.append(ChartPlan(
                    title=chart_data.get("title", ""),
                    chart_type=ChartType(chart_data.get("chart_type", "table")),
                    table=chart_data.get("table", ""),
                    sql=sql,
                    x_field=chart_data.get("x_field"),
                    y_field=chart_data.get("y_field"),
                    business_question=chart_data.get("business_question"),
                ))
            except Exception as ce:
                logger.warning(f"Chart {i} parse error: {ce}, data={chart_data}")

        title = data.get("dashboard_title", "AI Generated Dashboard")
        logger.info(f"Dashboard plan: title='{title}', charts={len(charts)}")

        return DashboardPlan(
            dashboard_title=title,
            charts=charts,
            layout=data.get("layout"),
        )

    except json.JSONDecodeError as e:
        logger.error(f"Failed to parse dashboard plan JSON: {e}")
        logger.error(f"JSON string (first 500): {json_str[:500]}")
        logger.error(f"Raw content (first 500): {content[:500]}")
        return DashboardPlan(
            dashboard_title="AI Generated Dashboard",
            charts=[],
        )
    except Exception as e:
        import traceback
        logger.error(f"Unexpected error in plan_dashboard: {e}\n{traceback.format_exc()}")
        return DashboardPlan(
            dashboard_title="AI Generated Dashboard",
            charts=[],
        )

