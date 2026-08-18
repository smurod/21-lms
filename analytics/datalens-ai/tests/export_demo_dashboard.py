"""
Export a full DataLens dashboard and all chart JSON used inside it.

Usage:
    python tests/export_demo_dashboard.py

The demo dashboard ID from the URL is qvnkqzm0wstyf.
Files are saved into samples/demo/.
"""

from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import Any

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from datalens_client import DataLensClient  # noqa: E402

DASH_ID = "qvnkqzm0wstyf"
OUT_DIR = PROJECT_ROOT / "samples" / "demo"


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print("  saved:", path)


def collect_chart_ids(dashboard_data: dict[str, Any]) -> list[str]:
    ids: list[str] = []
    for tab in dashboard_data.get("tabs", []):
        for item in tab.get("items", []):
            if item.get("type") != "widget":
                continue
            for chart_tab in (item.get("data") or {}).get("tabs", []):
                chart_id = chart_tab.get("chartId")
                if chart_id and chart_id not in ids:
                    ids.append(chart_id)
    return ids


def main() -> None:
    with DataLensClient() as client:
        print("1. Login...")
        client.login()

        print("2. Export dashboard:", DASH_ID)
        response = client.gateway(
            "mix",
            "getDashboardV1",
            {"dashboardId": DASH_ID, "includePermissions": True, "includeLinks": True},
        )

        write_json(OUT_DIR / "dashboard_raw.json", response)

        entry = response.get("entry", response)
        data = entry.get("data", {})
        write_json(OUT_DIR / "dashboard_data.json", data)

        # Save a compact map of dashboard blocks so we can understand the layout.
        blocks = []
        for tab in data.get("tabs", []):
            for item in tab.get("items", []):
                blocks.append(
                    {
                        "tab": tab.get("title"),
                        "id": item.get("id"),
                        "type": item.get("type"),
                        "namespace": item.get("namespace"),
                        "data": item.get("data"),
                    }
                )
        write_json(OUT_DIR / "dashboard_blocks.json", blocks)

        chart_ids = collect_chart_ids(data)
        print("3. Found charts:", len(chart_ids))

        print("4. Export each chart...")
        charts = []
        for chart_id in chart_ids:
            result = client.gateway(
                "us",
                "getEntries",
                {
                    "scope": "widget",
                    "ids": [chart_id],
                    "includeData": True,
                    "includeLinks": True,
                },
            )
            entries = result.get("entries", [])
            if not entries:
                print("  not found:", chart_id)
                continue

            chart = entries[0]
            charts.append(
                {
                    "entryId": chart.get("entryId"),
                    "key": chart.get("key"),
                    "name": chart.get("name"),
                    "type": chart.get("type"),
                }
            )

            safe_name = (
                (chart.get("name") or chart.get("key") or chart_id)
                .split("/")[-1]
                .replace(" ", "_")
                .replace("/", "_")
            )
            write_json(OUT_DIR / "charts" / f"{chart_id}_{safe_name}.json", chart)

        write_json(OUT_DIR / "chart_index.json", charts)

    print("\nDONE.")
    print("Send the whole folder as a zip:")
    print("  cd", PROJECT_ROOT)
    print("  zip -r demo_dashboard_export.zip samples/demo")


if __name__ == "__main__":
    main()
