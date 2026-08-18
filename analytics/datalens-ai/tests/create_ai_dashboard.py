"""
Create a full AI-generated dashboard from the configured PostgreSQL database.

This test creates real visible objects and does NOT delete them.
Run:
    python tests/create_ai_dashboard.py
"""

from __future__ import annotations

import asyncio
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from config import get_settings  # noqa: E402
from dashboard_service import create_ai_dashboard  # noqa: E402


async def main() -> None:
    settings = get_settings()
    result = await create_ai_dashboard(
        db_url=settings.db_url,
        message="Создай краткий аналитический дашборд по основной активности пользователей",
    )

    print("\nDONE")
    print("dashboard_id:", result["dashboard_id"])
    print("connection_id:", result["connection_id"])
    print("charts:", len(result["charts"]))
    print("open:", result["dashboard_url"])


if __name__ == "__main__":
    asyncio.run(main())
