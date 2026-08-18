"""
Read both dashboard IDs from /home/smurod_8880/exam2/result.txt, fetch their
full JSON from DataLens, validate all SQL, and write a complete report back to
the same file.
"""

from __future__ import annotations

import asyncio
import json
import re
import sys
from pathlib import Path
from typing import Any

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from config import get_settings  # noqa: E402
from datalens_client import DataLensClient  # noqa: E402
from sql_validator import validate_sql  # noqa: E402

RESULT_PATH = Path("/home/smurod_8880/exam2/result.txt")


def extract_dashboard_ids(text: str) -> list[str]:
    """Pull every 'dashboard_id: <id>' from the original test log."""
    return re.findall(r"dashboard_id:\s*([a-z0-9]+)", text)


def _widget_refs(dashboard_entry: dict) -> list[dict[str, str]]:
    refs: list[dict[str, str]] = []
    for tab in dashboard_entry["entry"]["data"].get("tabs", []):
        for item in tab.get("items", []):
            if item.get("type") != "widget":
                continue
            for chart_tab in item.get("data", {}).get("tabs", []):
                if chart_tab.get("chartId"):
                    refs.append(
                        {
                            "chartId": chart_tab["chartId"],
                            "title": chart_tab.get("title") or "",
                            "itemId": item["id"],
                        }
                    )
    return refs


def _load_chart(client: DataLensClient, chart_id: str) -> dict[str, Any]:
    entry = client.get_ql_chart(chart_id)
    shared = entry.get("_shared") or {}
    viz = shared.get("visualization") or {}
    columns: list[tuple[str, str]] = []
    for placeholder in viz.get("placeholders", []):
        for field in placeholder.get("items", []):
            columns.append(
                (
                    field.get("guid") or field.get("title") or "",
                    field.get("data_type", "string"),
                )
            )
    return {
        "entryId": entry.get("entryId"),
        "name": entry.get("name"),
        "type": entry.get("type"),
        "chartType": viz.get("id", "unknown"),
        "sql": shared.get("queryValue", ""),
        "columns": columns,
    }


async def _validate(db_url: str, sql: str) -> tuple[bool, int, str]:
    if not sql:
        return False, 0, "empty SQL"
    ok, rows, error = await validate_sql(db_url, sql)
    return ok, len(rows or []), error or ""


async def inspect(client: DataLensClient, dashboard_id: str, label: str, db_url: str) -> dict[str, Any]:
    print(f"[{label}] loading {dashboard_id}...")
    dashboard = client.get_dashboard(dashboard_id)
    refs = _widget_refs(dashboard)
    print(f"[{label}] {len(refs)} widgets")

    charts = []
    for ref in refs:
        chart = _load_chart(client, ref["chartId"])
        chart["widgetTitle"] = ref["title"]
        ok, rows_count, error = await _validate(db_url, chart["sql"])
        chart["sqlValid"] = ok
        chart["dryRunRows"] = rows_count
        chart["sqlError"] = error
        charts.append(chart)
        print(
            f"[{label}]   - {chart['widgetTitle']} "
            f"({chart['chartType']}) valid={ok} rows={rows_count}"
        )

    return {
        "label": label,
        "dashboardId": dashboard_id,
        "entryName": dashboard["entry"].get("name"),
        "dashboardData": dashboard["entry"]["data"],
        "charts": charts,
    }


def _summary(report: dict[str, Any]) -> str:
    charts = report["charts"]
    valid = sum(1 for c in charts if c["sqlValid"])
    total_rows = sum(c["dryRunRows"] for c in charts)
    return (
        f"model={report['label']} dashboard_id={report['dashboardId']} "
        f"widgets={len(charts)} valid_sql={valid}/{len(charts)} "
        f"total_rows={total_rows}"
    )


def write_report(a: dict[str, Any], b: dict[str, Any], original_text: str) -> None:
    lines: list[str] = []

    lines.append("=" * 80)
    lines.append("DASHBOARD MODEL COMPARISON")
    lines.append("=" * 80)
    lines.append("")
    lines.append("SUMMARY:")
    lines.append("  " + _summary(a))
    lines.append("  " + _summary(b))
    lines.append("")

    for report in (a, b):
        lines.append("-" * 80)
        lines.append(f"MODEL: {report['label']}")
        lines.append("-" * 80)
        for i, chart in enumerate(report["charts"], start=1):
            lines.append(f"{i}. {chart['widgetTitle']}")
            lines.append(f"   type={chart['chartType']} sqlValid={chart['sqlValid']} rows={chart['dryRunRows']}")
            if chart["sqlError"]:
                lines.append(f"   error={chart['sqlError']}")
            lines.append("   SQL:")
            for line in (chart["sql"] or "").splitlines():
                lines.append(f"     {line}")
            lines.append("   columns: " + ", ".join(f"{n}:{t}" for n, t in chart["columns"]))
        lines.append("")

    lines.append("=" * 80)
    lines.append("ORIGINAL RESULT.TXT (kept for reference)")
    lines.append("=" * 80)
    lines.append(original_text)

    lines.append("")
    lines.append("=" * 80)
    lines.append("FULL DASHBOARD JSON — MODEL A")
    lines.append("=" * 80)
    lines.append(json.dumps(
        {
            "dashboardId": a["dashboardId"],
            "entryName": a["entryName"],
            "data": a["dashboardData"],
            "charts": [
                {k: v for k, v in c.items() if k not in ("shared",)}
                for c in a["charts"]
            ],
        },
        ensure_ascii=False, indent=2, default=str,
    ))

    lines.append("")
    lines.append("=" * 80)
    lines.append("FULL DASHBOARD JSON — MODEL B")
    lines.append("=" * 80)
    lines.append(json.dumps(
        {
            "dashboardId": b["dashboardId"],
            "entryName": b["entryName"],
            "data": b["dashboardData"],
            "charts": [
                {k: v for k, v in c.items() if k not in ("shared",)}
                for c in b["charts"]
            ],
        },
        ensure_ascii=False, indent=2, default=str,
    ))

    RESULT_PATH.write_text("\n".join(lines), encoding="utf-8")
    print(f"\nReport written to {RESULT_PATH}")


async def main() -> None:
    original_text = RESULT_PATH.read_text(encoding="utf-8")
    ids = extract_dashboard_ids(original_text)
    unique_ids = list(dict.fromkeys(ids))
    if len(unique_ids) < 2:
        raise SystemExit(
            f"Expected at least 2 dashboard_id values in {RESULT_PATH}, "
            f"found: {unique_ids}"
        )

    id_a, id_b = unique_ids[0], unique_ids[1]
    settings = get_settings()

    with DataLensClient(settings) as client:
        print("1. Login...")
        client.login()
        report_a = await inspect(client, id_a, "A (first model)", settings.db_url)
        report_b = await inspect(client, id_b, "B (second model)", settings.db_url)

    write_report(report_a, report_b, original_text)


if __name__ == "__main__":
    asyncio.run(main())
