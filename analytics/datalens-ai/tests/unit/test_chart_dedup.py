"""Tests for chart_dedup — SQL and title fingerprinting."""

import pytest
from chart_dedup import sql_fingerprint, title_fingerprint


class TestSqlFingerprint:
    def test_removes_line_comments(self):
        a = "SELECT id -- comment\nFROM users"
        b = "SELECT id FROM users"
        assert sql_fingerprint(a) == sql_fingerprint(b)

    def test_removes_block_comments(self):
        a = "SELECT /* test */ id FROM users"
        b = "SELECT id FROM users"
        assert sql_fingerprint(a) == sql_fingerprint(b)

    def test_case_insensitive(self):
        assert sql_fingerprint("SELECT 1") == sql_fingerprint("select 1")

    def test_trailing_semicolon_stripped(self):
        assert sql_fingerprint("SELECT 1;") == sql_fingerprint("SELECT 1")

    def test_whitespace_normalized(self):
        assert sql_fingerprint("SELECT  1") == sql_fingerprint("SELECT 1")

    def test_different_sql_not_equal(self):
        assert sql_fingerprint("SELECT id FROM users") != sql_fingerprint("SELECT id FROM projects")


class TestTitleFingerprint:
    def test_case_insensitive(self):
        assert title_fingerprint("Топ пользователей") == title_fingerprint("топ пользователей")

    def test_extra_spaces_normalized(self):
        assert title_fingerprint("  топ  ") == title_fingerprint("топ")

    def test_different_titles_not_equal(self):
        assert title_fingerprint("Активность") != title_fingerprint("Пользователи")
