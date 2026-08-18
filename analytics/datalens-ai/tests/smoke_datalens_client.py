"""
Quick smoke test for datalens_client.py.

It logs into DataLens, finds or creates the test workbook, creates a temporary
PostgreSQL connection, prints its ID, and deletes it.

Run from the project root:
    python tests/smoke_datalens_client.py
"""

from __future__ import annotations

import sys
import time
from pathlib import Path

# Allow running this file directly: `python tests/smoke_datalens_client.py`.
PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from datalens_client import DataLensClient  # noqa: E402


def main() -> None:
    with DataLensClient() as client:
        print("1. Login...")
        cookies = client.login()
        print("   cookies:", ", ".join(cookies.keys()))

        print("2. Ensure workbook...")
        workbook = client.ensure_workbook()
        workbook_id = workbook.get("workbookId") or workbook.get("id")
        print("   workbook:", workbook.get("title"), workbook_id)

        print("3. Create temporary connection...")
        name = f"AI Smoke Test {int(time.time())}"
        connection_id = client.create_postgres_connection(name=name, workbook_id=workbook_id)
        print("   created:", connection_id)

        print("4. Delete temporary connection...")
        client.delete_connection(connection_id)
        print("   deleted")

        print("OK: DataLens client works.")


if __name__ == "__main__":
    main()
