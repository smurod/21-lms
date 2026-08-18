"""
Create visible DataLens objects WITHOUT deleting them.

Run:
    python tests/create_visible_artifacts.py

Then open:
    http://localhost:8085/workbooks/kecfkcbwgwpa3

The script creates one PostgreSQL connection and three QL charts:
- line: XP dynamics by day;
- column: submissions by status;
- table: top 10 users by earned XP.

Delete test objects manually in DataLens UI after review.
"""

from __future__ import annotations

import sys
import time
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from datalens_client import DataLensClient  # noqa: E402
from ql_chart_builder import build_ql_chart  # noqa: E402

WORKBOOK_ID = "kecfkcbwgwpa3"

CHARTS = [
    {
        "name": "AI Line - XP Dynamics by Day",
        "type": "line",
        "sql": """
SELECT
  DATE(created_at) AS tx_date,
  COUNT(*) AS transactions_count,
  SUM(amount) AS total_xp
FROM xp_transactions
GROUP BY DATE(created_at)
ORDER BY tx_date
""".strip(),
        "columns": [
            ("tx_date", "date"),
            ("transactions_count", "integer"),
            ("total_xp", "integer"),
        ],
    },
    {
        "name": "AI Column - Submissions by Status",
        "type": "column",
        "sql": """
SELECT
  status,
  COUNT(*) AS count
FROM submissions
GROUP BY status
ORDER BY count DESC
""".strip(),
        "columns": [
            ("status", "string"),
            ("count", "integer"),
        ],
    },
    {
        "name": "AI Table - Top 10 Users by XP",
        "type": "table",
        "sql": """
SELECT
  u.name,
  u.username,
  u.level,
  COALESCE(SUM(xt.amount), 0) AS earned_xp,
  COUNT(xt.id) AS xp_count,
  (SELECT COUNT(*) FROM submissions s WHERE s.user_id = u.id) AS submission_count
FROM users u
LEFT JOIN xp_transactions xt ON u.id = xt.user_id
GROUP BY u.id, u.name, u.username, u.level
ORDER BY earned_xp DESC
LIMIT 10
""".strip(),
        "columns": [
            ("name", "string"),
            ("username", "string"),
            ("level", "integer"),
            ("earned_xp", "integer"),
            ("xp_count", "integer"),
            ("submission_count", "integer"),
        ],
    },
]


def main() -> None:
    timestamp = int(time.time())

    with DataLensClient() as client:
        print("1. Login...")
        client.login()

        print(f"2. Create connection in workbook {WORKBOOK_ID}...")
        connection_id = client.create_postgres_connection(
            name=f"AI Visible Connection {timestamp}",
            workbook_id=WORKBOOK_ID,
        )
        print("   connection_id:", connection_id)

        created_chart_ids: list[str] = []
        for index, chart in enumerate(CHARTS, start=3):
            print(f"{index}. Create chart: {chart['name']}...")
            chart_data = build_ql_chart(
                connection_id=connection_id,
                sql=chart["sql"],
                chart_type=chart["type"],
                columns=chart["columns"],
            )
            chart_id = client.create_ql_chart(
                name=f"{chart['name']} {timestamp}",
                workbook_id=WORKBOOK_ID,
                data=chart_data,
            )
            created_chart_ids.append(chart_id)
            print("   chart_id:", chart_id)

    print("\nDONE. Open DataLens and refresh:")
    print(f"http://localhost:8085/workbooks/{WORKBOOK_ID}")
    print("\nCreated objects:")
    print("  connection_id:", connection_id)
    for chart_id in created_chart_ids:
        print("  chart_id:", chart_id)
    print("\nNothing was deleted. Remove these objects manually in UI.")


if __name__ == "__main__":
    main()
