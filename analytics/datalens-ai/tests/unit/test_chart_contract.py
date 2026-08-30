"""Tests for chart_contract.ql_contract_error."""

import pytest
from chart_contract import ql_contract_error


class TestLineArea:
    def test_valid_line(self):
        assert ql_contract_error("line", [("day", "date"), ("cnt", "integer")]) is None

    def test_valid_area_multi_y(self):
        assert ql_contract_error("area", [("day", "date"), ("xp", "float"), ("cnt", "integer")]) is None

    def test_first_not_date(self):
        assert ql_contract_error("line", [("status", "string"), ("cnt", "integer")]) is not None

    def test_no_numeric(self):
        assert ql_contract_error("line", [("day", "date"), ("label", "string")]) is not None

    def test_too_few_columns(self):
        assert ql_contract_error("line", [("day", "date")]) is not None


class TestColumnBarPie:
    def test_valid_column(self):
        assert ql_contract_error("column", [("status", "string"), ("cnt", "integer")]) is None

    def test_valid_pie(self):
        assert ql_contract_error("pie", [("level", "string"), ("cnt", "float")]) is None

    def test_last_not_numeric(self):
        assert ql_contract_error("bar", [("name", "string"), ("label", "string")]) is not None

    def test_single_column(self):
        assert ql_contract_error("column", [("cnt", "integer")]) is not None


class TestTable:
    def test_table_supported(self):
        """table_ql_node is now supported — no contract error."""
        assert ql_contract_error("table", [("name", "string"), ("cnt", "integer")]) is None

    def test_table_multi_column(self):
        assert ql_contract_error("table", [("name", "string"), ("xp", "integer"), ("score", "float")]) is None


class TestDuplicateColumns:
    def test_duplicate_aliases_rejected(self):
        assert ql_contract_error("bar", [("cnt", "integer"), ("cnt", "float")]) is not None

    def test_empty_columns_rejected(self):
        assert ql_contract_error("bar", []) is not None
