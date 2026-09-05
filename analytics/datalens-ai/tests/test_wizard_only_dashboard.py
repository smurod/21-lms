"""Integration test: full-DB generation using ONLY wizard/KPI charts.

Starts an isolated service instance on :8101 with the QL chart path disabled
(monkeypatch — no project files are changed), sends exactly «Визуализируй БД»,
waits for the job to finish and prints the DataLens dashboard URL.

Run manually:
    WIZARD_ONLY_TEST=1 venv/bin/pytest tests/test_wizard_only_dashboard.py -q -s
"""

from __future__ import annotations

import os
import threading
import time

import httpx
import pytest
import uvicorn

pytestmark = pytest.mark.skipif(
    os.environ.get("WIZARD_ONLY_TEST") != "1",
    reason="интеграционный тест (реальный DataLens + LLM): WIZARD_ONLY_TEST=1",
)

PORT = 8101
BASE = f"http://127.0.0.1:{PORT}"


def _patch_ql_off() -> None:
    """Disable the QL branch entirely: every chart type goes through the
    wizard pipeline (dataset-based). Dispatches ALL types to proper wizard
    builders — the project fallback turns line/area/column/bar into metrics,
    which would defeat the purpose of this visual test."""
    import dashboard_service
    import sql_pipeline
    from dataset_pipeline import DatasetSpec, introspect_sql
    from wizard_chart_builder import (
        build_column_shared,
        build_flat_table_shared,
        build_line_shared,
        build_metric_shared,
        build_pie_shared,
        finalise_shared,
    )

    always_wizard = lambda chart_type: True  # noqa: E731
    sql_pipeline.is_wizard_chart = always_wizard
    dashboard_service.is_wizard_chart = always_wizard

    accents = ("#84D1EE", "#FFC636", "#54A520", "#BA74B3", "#FFB46C", "#BE2443")

    async def wizard_only_create(
        client, chart, connection_id, wb_id, db_url, dataset_ids, connection_type,
        metric_index: int = 0,
    ):
        cols = await introspect_sql(db_url, chart.sql)
        if not cols:
            raise RuntimeError(f"introspect failed for '{chart.title}'")
        spec = DatasetSpec(sql=chart.sql, columns=cols)
        dataset_id = await client.create_dataset(
            name=f"DS {chart.title[:60]} {int(time.time() * 1000) % 100000:05d}",
            workbook_id=wb_id,
            connection_id=connection_id,
            result_schema=spec.result_schema,
            source_id=spec.source_id,
            avatar_id=spec.avatar_id,
            sql=chart.sql,
            raw_schema=spec.raw_schema,
        )
        spec.dataset_id = dataset_id
        dataset_ids.append(dataset_id)

        ct = chart.chart_type.value if hasattr(chart.chart_type, "value") else str(chart.chart_type)
        planned = [
            (c.name if hasattr(c, "name") else c[0])
            for c in chart.columns
        ]
        planned_types = [
            (c.data_type if hasattr(c, "data_type") else c[1])
            for c in chart.columns
        ]
        numeric = [n for n, t in zip(planned, planned_types) if t in {"integer", "float"}]
        category = [n for n in planned if n not in numeric]
        cats = [
            str(row.get(category[0]))
            for row in (chart.sample_rows or [])
            if category and row.get(category[0]) is not None
        ]

        if ct == "pie":
            shared = build_pie_shared(spec, category[0], numeric[-1], category_values=cats)
        elif ct == "metric":
            shared = build_metric_shared(
                spec, numeric[0], font_size="m",
                font_color=accents[metric_index % len(accents)],
            )
        elif ct in ("table", "flatTable"):
            shared = build_flat_table_shared(spec, [name for name, _t in cols])
        elif ct in ("line", "area"):
            shared = build_line_shared(spec, cols[0][0], numeric or [cols[-1][0]], chart_type=ct)
        elif ct in ("column", "bar"):
            shared = build_column_shared(
                spec, category or [cols[0][0]], numeric or [cols[-1][0]], chart_type=ct,
            )
        else:
            shared = build_metric_shared(spec, numeric[-1] if numeric else cols[-1][0], font_size="m")

        shared = finalise_shared(shared, dataset_id)
        chart_id = await client.create_wizard_chart(
            name=chart.title[:80], workbook_id=wb_id, chart_type=ct, shared=shared,
        )
        return {
            "id": chart_id, "title": chart.title, "kind": ct, "section": chart.section,
            "note": chart.note, "sql": chart.sql, "dataset_id": dataset_id,
        }

    dashboard_service._create_wizard_chart = wizard_only_create


def test_full_db_wizard_only_generation():
    _patch_ql_off()
    import main  # import AFTER the patch so the service uses it

    server = uvicorn.Server(uvicorn.Config(main.app, host="127.0.0.1", port=PORT, log_level="warning"))
    thread = threading.Thread(target=server.run, daemon=True)
    thread.start()

    try:
        for _ in range(60):
            try:
                if httpx.get(f"{BASE}/health", timeout=5).json().get("status") == "ok":
                    break
            except Exception:
                time.sleep(1)
        else:
            pytest.fail("test service did not start")

        # 1) Роутер должен понять делегирование и выбрать generate_dashboard.
        routed = httpx.post(
            f"{BASE}/api/agent/respond",
            json={"message": "Визуализируй БД", "has_dashboard": False, "known_dashboards": []},
            timeout=180,
        ).json()
        assert routed["tool"] == "generate_dashboard", routed
        print("\n[router] tool =", routed["tool"])

        # 2) Запускаем генерацию и ждём завершения, не вмешиваясь.
        job_id = httpx.post(
            f"{BASE}/api/dashboard-jobs",
            json={"operation": "generate", "message": "Визуализируй БД"},
            timeout=30,
        ).json()["job_id"]

        deadline = time.time() + 15 * 60
        status, payload = None, {}
        while time.time() < deadline:
            payload = httpx.get(f"{BASE}/api/dashboard-jobs/{job_id}", timeout=15).json()
            status = payload["status"]
            if status in ("completed", "failed"):
                break
            time.sleep(5)
        assert status == "completed", payload

        # 3) Каждый чарт обязан быть wizard: у него есть свой dataset.
        charts = payload["result"]["charts"]
        assert charts, "no charts built"
        for chart in charts:
            raw = httpx.get(f"{BASE}/api/charts/{chart['id']}", timeout=30).json()
            shared = raw.get("_shared") or {}
            assert shared.get("datasetsIds"), f"{chart['title']} is not a wizard chart"
            print(f"[chart] {chart['kind']:7} | {chart['title'][:50]} | dataset ✓")

        url = payload["result"]["embed_url"]
        print("\n[dashboard]", url)
        print("[charts total]", len(charts))
    finally:
        server.should_exit = True
        thread.join(timeout=10)
