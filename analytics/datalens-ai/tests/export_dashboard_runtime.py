"""Read-only full JSON export of a DataLens dashboard and every referenced QL chart.

Usage:
    ./venv/bin/python tests/export_dashboard_runtime.py <datalens_dashboard_id>

The JSON is written to stdout so callers can redirect it outside the project.
"""

from __future__ import annotations

import json
import sys
from urllib.request import urlopen


def get_json(url: str) -> dict:
    with urlopen(url, timeout=30) as response:  # nosec B310 - localhost diagnostic API
        return json.loads(response.read().decode("utf-8"))


def main() -> int:
    if len(sys.argv) != 2:
        print("Usage: export_dashboard_runtime.py <datalens_dashboard_id>", file=sys.stderr)
        return 2

    dashboard_id = sys.argv[1]
    dashboard = get_json(f"http://127.0.0.1:8100/api/dashboards/{dashboard_id}")
    entry = dashboard.get("entry", dashboard)
    chart_ids: list[str] = []
    for tab in entry.get("data", {}).get("tabs", []):
        for item in tab.get("items", []):
            if item.get("type") != "widget":
                continue
            for chart_tab in item.get("data", {}).get("tabs", []):
                chart_id = chart_tab.get("chartId")
                if chart_id and chart_id not in chart_ids:
                    chart_ids.append(chart_id)

    charts = {chart_id: get_json(f"http://127.0.0.1:8100/api/charts/{chart_id}") for chart_id in chart_ids}
    print(json.dumps({"dashboard": dashboard, "charts": charts}, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
