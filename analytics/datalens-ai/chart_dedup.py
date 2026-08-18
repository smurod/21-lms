"""Deterministic duplicate detection for AI-generated chart SQL."""

from __future__ import annotations

import re

_LINE_COMMENT = re.compile(r"--[^\n]*")
_BLOCK_COMMENT = re.compile(r"/\*.*?\*/", re.DOTALL)
_WHITESPACE = re.compile(r"\s+")


def sql_fingerprint(sql: str) -> str:
    """Return a stable identity for semantically identical generated SQL text.

    This deliberately does not try to rewrite SQL. It removes formatting and
    comments only, so different queries are never falsely merged by an unsafe
    heuristic.
    """
    value = _LINE_COMMENT.sub(" ", sql or "")
    value = _BLOCK_COMMENT.sub(" ", value)
    value = _WHITESPACE.sub(" ", value).strip().rstrip(";").strip()
    return value.casefold()


def title_fingerprint(title: str) -> str:
    """Normalize a chart title to prevent visibly duplicated dashboard cards."""
    return _WHITESPACE.sub(" ", title or "").strip().casefold()
