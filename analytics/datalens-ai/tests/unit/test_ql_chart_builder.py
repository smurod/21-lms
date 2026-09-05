"""Tests for ql_chart_builder.build_ql_chart — all supported chart types."""

import pytest
from ql_chart_builder import build_ql_chart, normalize_chart_type


CONNECTION_ID = "test_connection_id"
SQL = "SELECT day, cnt FROM test_table"


# ---------------------------------------------------------------------------
# normalize_chart_type
# ---------------------------------------------------------------------------

class TestNormalizeChartType:
    def test_line_aliases(self):
        assert normalize_chart_type("line") == "line"
        assert normalize_chart_type("lines") == "line"
        assert normalize_chart_type("trend") == "line"

    def test_area_aliases(self):
        assert normalize_chart_type("area") == "area"
        assert normalize_chart_type("normalized_area") == "area"

    def test_column_aliases(self):
        assert normalize_chart_type("column") == "column"
        assert normalize_chart_type("verticalcolumn") == "column"

    def test_bar_aliases(self):
        assert normalize_chart_type("bar") == "bar"
        assert normalize_chart_type("bars") == "bar"
        assert normalize_chart_type("horizontal_bar") == "bar"

    def test_pie_aliases(self):
        assert normalize_chart_type("pie") == "pie"
        assert normalize_chart_type("donut") == "pie"

    def test_table_aliases(self):
        assert normalize_chart_type("table") == "table"
        assert normalize_chart_type("flattable") == "table"
        assert normalize_chart_type("flat-table") == "table"

    def test_unknown_defaults_to_bar(self):
        """Unknown types fall back to bar (safer than table for aggregated data)."""
        assert normalize_chart_type("radar") == "bar"
        assert normalize_chart_type("") == "bar"


# ---------------------------------------------------------------------------
# Common assertions
# ---------------------------------------------------------------------------

def _assert_common(result: dict, expected_viz_id: str) -> None:
    """Check fields present in every QL chart."""
    assert result["type"] == "ql"
    assert result["chartType"] == "sql"
    assert result["visualization"]["id"] == expected_viz_id
    assert result["connection"]["entryId"] == CONNECTION_ID
    assert result["connection"]["type"] == "postgres"
    assert result["queryValue"] == SQL.strip()


# ---------------------------------------------------------------------------
# line
# ---------------------------------------------------------------------------

class TestLineChart:
    COLS = [("day", "date"), ("cnt", "integer")]

    def test_viz_id(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="line", columns=self.COLS)
        _assert_common(r, "line")

    def test_x_placeholder_has_date_field(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="line", columns=self.COLS)
        x_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "x")
        assert x_items[0]["guid"] == "day"
        assert x_items[0]["data_type"] == "date"

    def test_y_placeholder_has_metric(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="line", columns=self.COLS)
        y_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "y")
        assert y_items[0]["guid"] == "cnt"

    def test_multi_y(self):
        cols = [("day", "date"), ("cnt", "integer"), ("score", "float")]
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="line", columns=cols)
        y_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "y")
        assert len(y_items) == 2

    def test_too_few_columns_raises(self):
        with pytest.raises(ValueError):
            build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="line", columns=[("day", "date")])


# ---------------------------------------------------------------------------
# area
# ---------------------------------------------------------------------------

class TestAreaChart:
    COLS = [("day", "date"), ("events", "integer")]

    def test_viz_id(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="area", columns=self.COLS)
        _assert_common(r, "area")

    def test_x_is_date(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="area", columns=self.COLS)
        x_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "x")
        assert x_items[0]["data_type"] == "date"


# ---------------------------------------------------------------------------
# column
# ---------------------------------------------------------------------------

class TestColumnChart:
    COLS = [("status", "string"), ("cnt", "integer")]

    def test_viz_id(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="column", columns=self.COLS)
        _assert_common(r, "column")

    def test_y_is_last_numeric(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="column", columns=self.COLS)
        y_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "y")
        assert y_items[0]["guid"] == "cnt"

    def test_x_is_category(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="column", columns=self.COLS)
        x_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "x")
        assert x_items[0]["guid"] == "status"

    def test_axis_mode_map_set(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="column", columns=self.COLS)
        x_placeholder = next(p for p in r["visualization"]["placeholders"] if p["id"] == "x")
        assert "status" in x_placeholder["settings"]["axisModeMap"]


# ---------------------------------------------------------------------------
# bar
# ---------------------------------------------------------------------------

class TestBarChart:
    COLS = [("username", "string"), ("score", "float")]

    def test_viz_id(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="bar", columns=self.COLS)
        _assert_common(r, "bar")

    def test_y_is_float_metric(self):
        r = build_ql_chart(connection_id=CONNECTION_ID, sql=SQL, chart_type="bar", columns=self.COLS)
        y_items = next(p["items"] for p in r["visualization"]["placeholders"] if p["id"] == "y")
        assert y_items[0]["guid"] == "score"
        assert y_items[0]["data_type"] == "float"


# ---------------------------------------------------------------------------
# pie
# ---------------------------------------------------------------------------

class TestPieChart:
    COLS = [("category", "string"), ("cnt", "integer")]
    CATEGORIES = ["High", "Medium", "Low"]

    def test_viz_id(self):
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
            columns=self.COLS, category_values=self.CATEGORIES,
        )
        _assert_common(r, "pie")

    def test_dimensions_placeholder(self):
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
            columns=self.COLS, category_values=self.CATEGORIES,
        )
        dim = next(p for p in r["visualization"]["placeholders"] if p["id"] == "dimensions")
        assert dim["items"][0]["guid"] == "category"

    def test_measures_placeholder(self):
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
            columns=self.COLS, category_values=self.CATEGORIES,
        )
        meas = next(p for p in r["visualization"]["placeholders"] if p["id"] == "measures")
        assert meas["items"][0]["guid"] == "cnt"

    def test_pie_binds_color_placeholder(self):
        """The server pie preparer reads the colour field from the "colors"
        placeholder; without it all sectors render a single default colour."""
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
            columns=self.COLS, category_values=self.CATEGORIES,
        )
        colors_placeholder = next(
            p for p in r["visualization"]["placeholders"] if p["id"] == "colors"
        )
        assert colors_placeholder["items"][0]["guid"] == "category"
        assert r["colors"] == []  # top-level binding stays empty (ignored)

    def test_empty_category_values_ok(self):
        """No category_values → no crash."""
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
            columns=self.COLS, category_values=[],
        )
        assert r["colorsConfig"] == {}

    def test_too_few_columns_raises(self):
        with pytest.raises(ValueError):
            build_ql_chart(
                connection_id=CONNECTION_ID, sql=SQL, chart_type="pie",
                columns=[("category", "string")],
            )


# ---------------------------------------------------------------------------
# connection_type normalization
# ---------------------------------------------------------------------------

class TestConnectionType:
    def test_postgresql_normalized_to_postgres(self):
        cols = [("day", "date"), ("cnt", "integer")]
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="line",
            columns=cols, connection_type="postgresql",
        )
        assert r["connection"]["type"] == "postgres"

    def test_postgres_kept(self):
        cols = [("day", "date"), ("cnt", "integer")]
        r = build_ql_chart(
            connection_id=CONNECTION_ID, sql=SQL, chart_type="line",
            columns=cols, connection_type="postgres",
        )
        assert r["connection"]["type"] == "postgres"
