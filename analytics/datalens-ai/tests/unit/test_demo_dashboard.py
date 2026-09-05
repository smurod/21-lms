"""Unit tests for the demo-like dashboard: wizard routing, colors, KPI row."""

from __future__ import annotations

from ai_editor import ChartState, _apply_actions, _charts_payload
from dashboard_builder import build_dashboard_data
from dataset_pipeline import DatasetSpec
from ql_chart_builder import build_ql_chart
from sql_pipeline import is_wizard_chart
from wizard_chart_builder import build_metric_shared


def test_metric_card_gets_demo_accent_color():
    spec = DatasetSpec(
        sql="SELECT AVG(score) AS avg_score FROM reviews",
        columns=[{"name": "avg_score", "pg_type": "numeric"}],
    )

    shared = build_metric_shared(spec, "avg_score", font_size="m", font_color="#FFC636")

    settings = shared["extraSettings"]
    assert settings["metricFontSize"] == "m"
    assert settings["metricFontColor"] == "#FFC636"


# ---------------------------------------------------------------------------
# Wizard vs QL routing
# ---------------------------------------------------------------------------


def test_pie_and_table_use_ql_pipeline():
    assert not is_wizard_chart("pie")
    assert not is_wizard_chart("table")


def test_metric_still_requires_wizard_pipeline():
    assert is_wizard_chart("metric")
    assert is_wizard_chart("flatTable")
    assert is_wizard_chart("flattable")


# ---------------------------------------------------------------------------
# Demo-like colours for QL charts
# ---------------------------------------------------------------------------


def test_single_measure_column_binds_category_colors():
    """Reference look: the category dimension goes into colors[] — DataLens
    splits it into one series per category, every bar its own colour."""
    shared = build_ql_chart(
        connection_id="conn",
        sql="SELECT status, COUNT(*) AS cnt FROM submissions GROUP BY status",
        chart_type="column",
        columns=[("status", "string"), ("cnt", "integer")],
        category_values=["passed", "failed", "review"],
    )

    assert shared["colors"][0]["guid"] == "status"
    config = shared["colorsConfig"]
    assert config["fieldGuid"] == "status"
    assert set(config["mountedColors"]) == {"passed", "failed", "review"}


def test_multi_measure_column_colors_each_series():
    """Several numeric columns = several series; each series gets its own
    palette colour via mountedColors keyed by the series title."""
    shared = build_ql_chart(
        connection_id="conn",
        sql="SELECT week, COUNT(*) AS cnt, AVG(score) AS avg_score FROM submissions GROUP BY week",
        chart_type="column",
        columns=[("week", "date"), ("cnt", "integer"), ("avg_score", "float")],
    )

    y_items = next(p for p in shared["visualization"]["placeholders"] if p["id"] == "y")
    assert [i["title"] for i in y_items["items"]] == ["cnt", "avg_score"]
    # only ONE x dimension — a second one would split the chart into junk series
    x_items = next(p for p in shared["visualization"]["placeholders"] if p["id"] == "x")
    assert [i["title"] for i in x_items["items"]] == ["week"]
    mounted = shared["colorsConfig"]["mountedColors"]
    assert set(mounted) == {"cnt", "avg_score"}
    assert len(set(mounted.values())) == 2
    assert "palette" not in shared["colorsConfig"]


def test_multi_measure_line_colors_each_series():
    shared = build_ql_chart(
        connection_id="conn",
        sql="SELECT week, COUNT(*) AS cnt, AVG(score) AS avg_score FROM submissions GROUP BY week",
        chart_type="line",
        columns=[("week", "date"), ("cnt", "integer"), ("avg_score", "float")],
    )

    mounted = shared["colorsConfig"]["mountedColors"]
    assert set(mounted) == {"cnt", "avg_score"}
    assert len(set(mounted.values())) == 2


def test_table_chart_uses_flattable_viz_id():
    """The server preparer is keyed FlatTable; viz.id "table" 500s."""
    shared = build_ql_chart(
        connection_id="conn",
        sql="SELECT project, COUNT(*) AS submissions FROM submissions GROUP BY project",
        chart_type="table",
        columns=[("project", "string"), ("submissions", "integer")],
    )

    assert shared["visualization"]["id"] == "flatTable"
    assert shared["visualization"]["type"] == "table"


# ---------------------------------------------------------------------------
# KPI row layout
# ---------------------------------------------------------------------------


def _widgets(data):
    return [item for item in data["tabs"][0]["items"] if item["type"] == "widget"]


def _layout_by_title(data):
    layout = {row["i"]: row for row in data["tabs"][0]["layout"]}
    result = {}
    for widget in _widgets(data):
        title = widget["data"]["tabs"][0]["title"]
        result[title] = layout[widget["id"]]
    return result


def test_metrics_form_compact_first_row_without_duplicates():
    data = build_dashboard_data(
        title="T",
        sections=[{
            "title": "S",
            "charts": [
                {"id": "m1", "title": "Средний балл ревью", "kind": "metric"},
                {"id": "m2", "title": "Всего проектов", "kind": "metric"},
                {"id": "c1", "title": "Динамика", "kind": "column"},
            ],
        }],
    )

    layout = _layout_by_title(data)
    assert len(_widgets(data)) == 3  # metrics are not placed twice

    # Wide cards: title and the metric number fit without truncation.
    assert layout["Средний балл ревью"]["w"] == 10
    assert layout["Средний балл ревью"]["y"] == 0
    assert layout["Всего проектов"]["w"] == 10
    assert layout["Всего проектов"]["x"] == 13

    # The regular chart starts below the KPI band and stays full-width.
    assert layout["Динамика"]["y"] >= 7
    assert layout["Динамика"]["w"] == 34


def test_four_metrics_wrap_to_second_row():
    data = build_dashboard_data(
        title="T",
        sections=[{
            "title": "S",
            "charts": [
                {"id": f"m{i}", "title": f"KPI {i}", "kind": "metric"} for i in range(1, 5)
            ],
        }],
    )

    layout = _layout_by_title(data)
    assert layout["KPI 4"]["y"] > layout["KPI 1"]["y"]  # wrapped row


def test_dashboard_without_metrics_keeps_previous_layout():
    data = build_dashboard_data(
        title="T",
        sections=[{"title": "S", "charts": [{"id": "c1", "title": "Динамика", "kind": "line"}]}],
    )

    layout = _layout_by_title(data)
    assert layout["Динамика"]["y"] == 0
    assert layout["Динамика"]["w"] == 34


# ---------------------------------------------------------------------------
# Editor: wizard chart guards and charts payload
# ---------------------------------------------------------------------------


def _metric_state() -> ChartState:
    return ChartState(
        entry_id="m1",
        title="Всего студентов",
        chart_type="metric",
        sql="",
        columns=[],
        section="KPI",
    )


def test_editor_never_rewrites_metric_charts():
    charts = [_metric_state()]
    _apply_actions(charts, [{"action": "update", "chart_id": "m1", "title": "Hacked"}])

    assert charts[0].title == "Всего студентов"
    assert charts[0].changed is False


def test_editor_rejects_metric_additions():
    charts: list[ChartState] = []
    _apply_actions(charts, [{
        "action": "add",
        "title": "New KPI",
        "chart_type": "metric",
        "sql": "SELECT COUNT(*) AS total FROM users",
        "columns": [("total", "integer")],
    }])

    assert charts == []


def test_charts_payload_exposes_real_titles():
    payload = _charts_payload([_metric_state()])

    assert payload == [{
        "id": "m1",
        "title": "Всего студентов",
        "kind": "metric",
        "section": "KPI",
        "note": "",
        "sql": "",
    }]
