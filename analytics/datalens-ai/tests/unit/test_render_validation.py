"""Unit tests for the pre-publication render validation (P3-9)."""

from __future__ import annotations

import asyncio

from dashboard_service import _render_problem, _validate_and_repair_render


def test_render_problem_on_error():
    assert _render_problem({"error": {"message": "boom"}}) is not None


def test_render_problem_on_empty_data():
    assert _render_problem({}) is not None
    assert _render_problem({"data": {}}) is not None


def test_render_problem_on_empty_series():
    assert _render_problem({"data": {"series": {"data": []}}}) is not None


def test_render_problem_none_for_healthy():
    assert _render_problem({"data": {"series": {"data": [{"name": "x"}]}}}) is None


def test_render_problem_none_for_metric_kpi_list():
    """Metric charts render `data` as a LIST of KPI cards — healthy."""
    metric = [{"content": {"current": {"value": 128}}, "size": "m", "color": "#54A520"}]
    assert _render_problem({"data": metric}) is None
    assert _render_problem({"data": []}) is not None


def test_render_problem_none_for_metric_like_payload():
    """A payload without a series key (metric shape) is not flagged."""
    assert _render_problem({"data": {"extra": {"value": 1}}}) is None


class _FakeClient:
    def __init__(self):
        self.updated: list[str] = []
        self.deleted_ql: list[str] = []
        self.deleted_wizard: list[str] = []

    async def update_ql_chart(self, chart_id, shared):
        self.updated.append(chart_id)

    async def delete_ql_chart(self, chart_id):
        self.deleted_ql.append(chart_id)

    async def delete_wizard_chart(self, chart_id):
        self.deleted_wizard.append(chart_id)


def _chart(chart_id: str, kind: str = "bar") -> dict:
    return {"id": chart_id, "title": f"t-{chart_id}", "kind": kind, "sql": "SELECT 1", "section": "S", "note": ""}


def test_all_healthy_skips_repair():
    client = _FakeClient()
    created = [_chart("a"), _chart("b", kind="metric")]
    renders = {"a": {"data": {"series": {"data": [1]}}}, "b": {"data": {"value": 1}}}
    fix_calls: list[str] = []

    async def fixer(*args, **kwargs):
        fix_calls.append(args[1]["id"])
        return "SELECT 2", [("x", "string")]

    async def render(cid):
        return renders[cid]

    asyncio.run(_validate_and_repair_render(
        client=client, created=created, connection_id="c", connection_type="postgres",
        schema_text="", db_url="postgresql://", message="m",
        render_chart=render, fix_sql=fixer,
    ))

    assert len(created) == 2
    assert fix_calls == []
    assert client.updated == []


def test_batch_repairs_ql_and_drops_wizard_in_one_round():
    client = _FakeClient()
    created = [_chart("ok"), _chart("broken-ql"), _chart("broken-metric", kind="metric")]
    renders = {
        "ok": {"data": {"series": {"data": [1]}}},
        "broken-ql": {"error": "boom"},
        "broken-metric": {},
    }
    fixed_renders = {"broken-ql": {"data": {"series": {"data": [1]}}}}
    fix_calls: list[str] = []
    rendered_once: set[str] = set()

    async def fixer(_client, info, error, **kwargs):
        fix_calls.append(info["id"])
        return "SELECT status, COUNT(*) AS cnt FROM t GROUP BY status", [
            ("status", "string"), ("cnt", "integer"),
        ]

    async def render(cid):
        # First render is broken; the post-fix recheck returns the repaired one.
        if cid in fixed_renders and cid in rendered_once:
            return fixed_renders[cid]
        rendered_once.add(cid)
        return renders[cid]

    asyncio.run(_validate_and_repair_render(
        client=client, created=created, connection_id="c", connection_type="postgres",
        schema_text="", db_url="postgresql://", message="m",
        render_chart=render, fix_sql=fixer,
    ))

    # One batch round: the QL chart got exactly one fix attempt and survived.
    assert fix_calls == ["broken-ql"]
    assert client.updated == ["broken-ql"]
    assert [c["id"] for c in created] == ["ok", "broken-ql"]
    assert created[1]["sql"].startswith("SELECT status")
    # The unfixable wizard chart was removed from the dashboard and DataLens.
    assert client.deleted_wizard == ["broken-metric"]
    assert client.deleted_ql == []


def test_unfixable_chart_is_deleted():
    client = _FakeClient()
    created = [_chart("bad")]

    async def fixer(*args, **kwargs):
        return None, []

    async def render(cid):
        return {}

    asyncio.run(_validate_and_repair_render(
        client=client, created=created, connection_id="c", connection_type="postgres",
        schema_text="", db_url="postgresql://", message="m",
        render_chart=render, fix_sql=fixer,
    ))

    assert created == []
    assert client.deleted_ql == ["bad"]


def test_async_bridge_delegates_to_sync_client():
    from datalens_client import AsyncDataLensClient

    class _Dummy:
        calls: list[int] = []

        def add(self, value: int) -> int:
            self.calls.append(value)
            return value * 2

    bridge = AsyncDataLensClient(_Dummy())
    assert asyncio.run(bridge.add(4)) == 8
    assert bridge._sync.calls == [4]
