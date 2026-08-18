"""Read-only diagnostic dump of a rendered DataLens dashboard and QL charts.

Usage:
    ./venv/bin/python tests/inspect_dashboard_runtime.py <datalens_dashboard_id>
"""

from __future__ import annotations

import json
import sys
from urllib.request import urlopen


def get_json(url: str) -> dict:
    with urlopen(url, timeout=30) as response:  # nosec B310 - local diagnostic endpoint
        return json.loads(response.read().decode("utf-8"))


def main() -> int:
    if len(sys.argv) != 2:
        print("Usage: inspect_dashboard_runtime.py <datalens_dashboard_id>", file=sys.stderr)
        return 2

    dashboard_id = sys.argv[1]
    dashboard = get_json(f"http://127.0.0.1:8100/api/dashboards/{dashboard_id}")
    entry = dashboard.get("entry", dashboard)
    print("DASHBOARD:", entry.get("entryId") or dashboard_id)
    print("NAME:", entry.get("name"))

    chart_ids: list[str] = []
    for tab in entry.get("data", {}).get("tabs", []):
        print("\nTAB:", tab.get("title"))
        for item in tab.get("items", []):
            if item.get("type") != "widget":
                continue
            for chart_tab in item.get("data", {}).get("tabs", []):
                chart_id = chart_tab.get("chartId")
                if chart_id:
                    chart_ids.append(chart_id)
                    print("WIDGET:", chart_tab.get("title"), "→", chart_id)

    for chart_id in chart_ids:
        chart = get_json(f"http://127.0.0.1:8100/api/charts/{chart_id}")
        shared = chart.get("_shared") or {}
        visualization = shared.get("visualization") or {}
        placeholders = [
            {
                "id": placeholder.get("id"),
                "items": [
                    {"guid": field.get("guid"), "data_type": field.get("data_type")}
                    for field in placeholder.get("items", [])
                ],
            }
            for placeholder in visualization.get("placeholders", [])
        ]
        print("\n" + "=" * 88)
        print("CHART:", chart_id)
        print("TYPE:", visualization.get("id"))
        print("SQL:\n", shared.get("queryValue"))
        print("PLACEHOLDERS:", json.dumps(placeholders, ensure_ascii=False))
        print("COLORS CONFIG:", json.dumps(shared.get("colorsConfig", {}), ensure_ascii=False))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
