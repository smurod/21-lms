"""Smoke test: create one QL line chart in the AI Generated workbook."""

from __future__ import annotations

import sys
import time
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from datalens_client import DataLensClient  # noqa: E402
from ql_chart_builder import build_ql_chart  # noqa: E402

SQL = """
SELECT
  DATE(created_at) AS tx_date,
  COUNT(*) AS transactions_count,
  SUM(amount) AS total_xp
FROM xp_transactions
GROUP BY DATE(created_at)
ORDER BY tx_date
""".strip()

COLUMNS = [
    ("tx_date", "date"),
    ("transactions_count", "integer"),
    ("total_xp", "integer"),
]


def main() -> None:
    with DataLensClient() as client:
        print("1. Login...")
        client.login()

        print("2. Workbook...")
        workbook = client.ensure_workbook()
        workbook_id = workbook.get("workbookId") or workbook.get("id")
        print("   ", workbook_id)

        print("3. Connection...")
        connection_name = f"AI Chart Smoke {int(time.time())}"
        connection_id = client.create_postgres_connection(
            name=connection_name, workbook_id=workbook_id
        )
        print("   ", connection_id)

        print("4. Build QL chart JSON...")
        chart_data = build_ql_chart(
            connection_id=connection_id,
            sql=SQL,
            chart_type="line",
            columns=COLUMNS,
        )
        print("   visualization:", chart_data["visualization"]["id"])

        print("5. Create QL chart...")
        chart_id = client.create_ql_chart(
            name=f"AI Smoke Line {int(time.time())}",
            workbook_id=workbook_id,
            data=chart_data,
        )
        print("   chart_id:", chart_id)

        print("6. Delete chart and connection...")
        client.delete_ql_chart(chart_id)
        client.delete_connection(connection_id)
        print("   deleted")

        print("OK: QL chart creation works.")


if __name__ == "__main__":
    main()
