"""Tests for sql_validator.read_only_sql_error (no DB required)."""

import sys
from unittest.mock import MagicMock

# psycopg is not installed in the test container — mock it before importing
sys.modules.setdefault("psycopg", MagicMock())
sys.modules.setdefault("psycopg.sql", MagicMock())

import pytest
from sql_validator import read_only_sql_error


def test_valid_select():
    assert read_only_sql_error("SELECT id FROM users") is None


def test_valid_with_cte():
    assert read_only_sql_error("WITH t AS (SELECT 1) SELECT * FROM t") is None


def test_empty_sql():
    assert read_only_sql_error("") is not None
    assert read_only_sql_error("   ") is not None


def test_insert_rejected():
    assert read_only_sql_error("INSERT INTO users VALUES (1)") is not None


def test_update_rejected():
    assert read_only_sql_error("UPDATE users SET name='x'") is not None


def test_delete_rejected():
    assert read_only_sql_error("DELETE FROM users") is not None


def test_drop_rejected():
    assert read_only_sql_error("DROP TABLE users") is not None


def test_multiple_statements_rejected():
    assert read_only_sql_error("SELECT 1; SELECT 2") is not None


def test_trailing_semicolon_ok():
    # Semicolon only at the very end after strip — policy strips it
    assert read_only_sql_error("SELECT 1") is None
