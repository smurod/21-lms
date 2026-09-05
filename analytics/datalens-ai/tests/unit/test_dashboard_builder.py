"""Tests for dashboard_builder.build_dashboard_data."""

import pytest
from dashboard_builder import build_dashboard_data


def _make_section(title: str, charts: list[dict]) -> dict:
    return {"title": title, "note": "Test note", "charts": charts}


def _make_chart(chart_id: str, kind: str = "line") -> dict:
    return {"id": chart_id, "title": f"Chart {chart_id}", "kind": kind}


class TestBuildDashboardData:
    def test_returns_dict_with_tabs(self):
        data = build_dashboard_data(title="Test", sections=[], tab_title="Overview")
        assert "tabs" in data
        assert len(data["tabs"]) == 1

    def test_empty_sections_produces_empty_layout(self):
        data = build_dashboard_data(title="T", sections=[], tab_title="Overview")
        assert data["tabs"][0]["layout"] == []
        assert data["tabs"][0]["items"] == []

    def test_single_line_chart_full_width(self):
        section = _make_section("Активность", [_make_chart("c1", "line")])
        data = build_dashboard_data(title="T", sections=[section])
        layout = data["tabs"][0]["layout"]
        assert len(layout) == 1
        # line chart should be full-width (w >= 30)
        assert layout[0]["w"] >= 30

    def test_paired_column_charts_half_width(self):
        charts = [_make_chart("c1", "column"), _make_chart("c2", "column")]
        section = _make_section("Сравнение", charts)
        data = build_dashboard_data(title="T", sections=[section])
        layout = data["tabs"][0]["layout"]
        # Two paired column charts — both should be half-width (w <= 20)
        chart_layouts = [l for l in layout if l["w"] <= 20]
        assert len(chart_layouts) == 2

    def test_no_duplicate_item_ids(self):
        charts = [_make_chart(f"c{i}", "bar") for i in range(4)]
        section = _make_section("Test", charts)
        data = build_dashboard_data(title="T", sections=[section])
        ids = [item["id"] for item in data["tabs"][0]["items"]]
        assert len(ids) == len(set(ids)), "Duplicate item IDs detected"

    def test_layout_ids_match_items(self):
        charts = [_make_chart("c1", "line"), _make_chart("c2", "bar")]
        section = _make_section("Test", charts)
        data = build_dashboard_data(title="T", sections=[section])
        tab = data["tabs"][0]
        item_ids = {item["id"] for item in tab["items"]}
        layout_ids = {row["i"] for row in tab["layout"]}
        assert item_ids == layout_ids

    def test_hide_dash_title_setting(self):
        data = build_dashboard_data(title="T", sections=[])
        assert data["settings"]["hideDashTitle"] is True
