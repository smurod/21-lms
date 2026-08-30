"""Tests for chart_sanity.chart_shape_error."""

import pytest
from chart_sanity import chart_shape_error


class TestLineArea:
    def test_valid_line(self):
        assert chart_shape_error("line", [("day", "date"), ("cnt", "integer")]) is None

    def test_valid_area(self):
        assert chart_shape_error("area", [("day", "date"), ("xp", "float")]) is None

    def test_first_col_not_date(self):
        assert chart_shape_error("line", [("status", "string"), ("cnt", "integer")]) is not None

    def test_no_numeric_metric(self):
        assert chart_shape_error("line", [("day", "date"), ("label", "string")]) is not None

    def test_single_column(self):
        assert chart_shape_error("line", [("day", "date")]) is not None


class TestColumnBarPie:
    def test_valid_column(self):
        assert chart_shape_error("column", [("status", "string"), ("cnt", "integer")]) is None

    def test_valid_bar(self):
        assert chart_shape_error("bar", [("name", "string"), ("score", "float")]) is None

    def test_valid_pie(self):
        assert chart_shape_error("pie", [("level", "string"), ("cnt", "integer")]) is None

    def test_last_col_not_numeric(self):
        assert chart_shape_error("column", [("status", "string"), ("label", "string")]) is not None

    def test_single_column(self):
        assert chart_shape_error("bar", [("cnt", "integer")]) is not None


class TestTable:
    def test_all_numeric_ok(self):
        """table_ql_node accepts any column combination including all-numeric."""
        assert chart_shape_error("table", [("cnt", "integer"), ("score", "float")]) is None

    def test_mixed_columns_ok(self):
        assert chart_shape_error("table", [("name", "string"), ("score", "integer")]) is None
