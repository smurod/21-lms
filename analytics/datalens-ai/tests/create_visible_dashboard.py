"""
Create a production-like dashboard from the three visible AI charts.

Does not delete anything. Run create_visible_artifacts.py first.
"""

from __future__ import annotations

import sys
import time
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from dashboard_builder import build_dashboard_data  # noqa: E402
from datalens_client import DataLensClient  # noqa: E402

WORKBOOK_ID = "kecfkcbwgwpa3"
CHARTS = [
    {
        "prefix": "AI Line - XP Dynamics by Day",
        "title": "XP Dynamics by Day",
        "kind": "line",
        "section": "Activity & Growth",
        "note": "Daily transactions and earned XP over time",
    },
    {
        "prefix": "AI Column - Submissions by Status",
        "title": "Submissions by Status",
        "kind": "column",
        "section": "Learning Process",
        "note": "Current state of submissions",
    },
    {
        "prefix": "AI Table - Top 10 Users by XP",
        "title": "Top 10 Users by XP",
        "kind": "table",
        "section": "Leaderboard",
        "note": "Most active learners",
    },
]


def entry_title(entry: dict) -> str:
    if entry.get("name"):
        return str(entry["name"])
    key = entry.get("key") or ""
    return key.split("/")[-1] if key else ""


def main() -> None:
    with DataLensClient() as client:
        print("1. Login...")
        client.login()

        print("2. List workbook charts...")
        result = client.gateway(
            "us",
            "getWorkbookEntries",
            {"workbookId": WORKBOOK_ID, "pageSize": 100},
        )
        entries = result.get("entries", [])

        found: dict[str, dict] = {}
        for spec in CHARTS:
            matches = [
                e for e in entries
                if e.get("scope") == "widget" and entry_title(e).startswith(spec["prefix"])
            ]
            if not matches:
                print("   not found:", spec["prefix"])
                continue
            matches.sort(key=entry_title, reverse=True)
            chart = matches[0]
            found[spec["prefix"]] = chart
            print("   using:", entry_title(chart), "->", chart["entryId"])

        if len(found) != len(CHARTS):
            raise SystemExit("Not all charts found. Run create_visible_artifacts.py first.")

        print("3. Build sections...")
        sections = []
        for section_name in dict.fromkeys(spec["section"] for spec in CHARTS):
            section_charts = []
            note = ""
            for spec in CHARTS:
                if spec["section"] != section_name:
                    continue
                chart = found[spec["prefix"]]
                section_charts.append(
                    {
                        "id": chart["entryId"],
                        "title": spec["title"],
                        "kind": spec["kind"],
                    }
                )
                note = spec["note"]
            sections.append(
                {
                    "title": section_name,
                    "note": note,
                    "charts": section_charts,
                }
            )

        data = build_dashboard_data(
            title="AI Analytics Dashboard",
            sections=sections,
            tab_title="Overview",
        )

        print("4. Create dashboard...")
        dash_id = client.create_dashboard(
            name=f"AI Production Dashboard {int(time.time())}",
            workbook_id=WORKBOOK_ID,
            data=data,
        )
        print("   dashboard_id:", dash_id)

    print("\nDONE. Open:")
    print(f"http://localhost:8085/workbooks/{WORKBOOK_ID}")
    print("Nothing was deleted.")


if __name__ == "__main__":
    main()
