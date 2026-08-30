"""Shared utilities for datalens-ai."""

from __future__ import annotations

import re


def _extract_json_from_response(content: str) -> str:
    """Extract JSON from an LLM response that may contain markdown or extra text.

    Handles:
    - Markdown code blocks: ```json ... ``` or ``` ... ```
    - Extra text before/after the JSON object
    - Plain JSON with no wrapping
    """
    content = content.strip()

    # Strategy 1: Remove markdown code blocks
    markdown_match = re.search(r"```(?:json)?\s*\n?(.*?)\n?```", content, re.DOTALL)
    if markdown_match:
        return markdown_match.group(1).strip()

    # Strategy 2: Find JSON object by matching braces
    first_brace = content.find("{")
    last_brace = content.rfind("}")
    if first_brace != -1 and last_brace != -1 and last_brace > first_brace:
        return content[first_brace : last_brace + 1]

    # Strategy 3: Return as-is (might be plain SQL or already-clean JSON)
    return content
