"""Tests for utils._extract_json_from_response."""

import pytest
from utils import _extract_json_from_response


def test_plain_json():
    assert _extract_json_from_response('{"a": 1}') == '{"a": 1}'


def test_markdown_json_block():
    raw = '```json\n{"a": 1}\n```'
    assert _extract_json_from_response(raw) == '{"a": 1}'


def test_markdown_plain_block():
    raw = '```\n{"a": 1}\n```'
    assert _extract_json_from_response(raw) == '{"a": 1}'


def test_extra_text_before_and_after():
    raw = 'Here is the result:\n{"a": 1}\nDone.'
    assert _extract_json_from_response(raw) == '{"a": 1}'


def test_empty_string():
    result = _extract_json_from_response("")
    assert result == ""


def test_no_json_returns_as_is():
    raw = "SELECT 1 AS n"
    assert _extract_json_from_response(raw) == raw
