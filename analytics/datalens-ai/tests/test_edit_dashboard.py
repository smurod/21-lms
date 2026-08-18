"""
Full test of the dashboard editor.

Sequence:
1. create a fresh AI dashboard (login -> connection -> schema -> charts -> dashboard);
2. add a chart;
3. reorder that chart to the first position;
4. update the first chart;
5. delete one chart.

Run:
    python tests/test_edit_dashboard.py
"""

from __future__ import annotations

import asyncio
import sys
import time
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from ai_editor import edit_dashboard  # noqa: E402
from config import get_settings  # noqa: E402
from dashboard_service import create_ai_dashboard  # noqa: E402

USER_REQUESTS = [
    (
        "Добавь таблицу топ-10 пользователей по XP. "
        "Используй users и xp_transactions."
    ),
    (
        "Перемести таблицу топ-10 пользователей по XP в самую первую секцию "
        "и поставь ее на первое место."
    ),
    (
        "Замени первый график: покажи количество submissions по статусу "
        "столбчатой диаграммой."
    ),
    (
        "Удали график, который показывает средний балл отзывов, ревью "
        "или качество проверок."
    ),
]


async def main() -> None:
    settings = get_settings()

    print("1. Create a fresh AI dashboard...")
    created = await create_ai_dashboard(
        db_url=settings.db_url,
        message=(
            "Создай дашборд по активности, пользователям и отзывам: "
            "линейный график динамики, столбчатый график и таблицу."
        ),
    )
    dashboard_id = created["dashboard_id"]
    print("   dashboard_id:", dashboard_id)
    print("   connection_id:", created["connection_id"])
    print("   dashboard_url:", created["dashboard_url"])

    for index, request in enumerate(USER_REQUESTS, start=2):
        print(f"\n{index}. Editor request: {request}")
        result = await edit_dashboard(
            dashboard_id=dashboard_id,
            instruction=request,
            db_url=settings.db_url,
            connection_id=created["connection_id"],
        )
        print("   added:", result["added"])
        print("   updated:", result["updated"])
        print("   deleted:", result["deleted"])
        print("   sections:")
        for section in result.get("sections", []):
            print(
                "    -",
                section["title"],
                "=>",
                [c["title"] for c in section["charts"]],
            )
        # Small pause so a human can refresh the dashboard in browser.
        time.sleep(5)

    print("\nDONE")
    print("dashboard_id:", dashboard_id)
    print("open:", created["dashboard_url"])


if __name__ == "__main__":
    asyncio.run(main())
