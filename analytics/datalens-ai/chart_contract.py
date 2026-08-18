"""Deterministic QL chart contracts checked before DataLens publication."""

from __future__ import annotations


def ql_contract_error(chart_type: str, columns: list[tuple[str, str]]) -> str | None:
    """Validate the exact field order that QL placeholders will receive."""
    kind = (chart_type or "").lower()
    if not columns:
        return "Chart has no output columns."
    names = [name for name, _type in columns]
    if len(names) != len(set(names)):
        return "Chart contains duplicate output column aliases."

    numeric = {"integer", "float"}
    if kind in {"line", "area"}:
        if len(columns) < 2 or columns[0][1] != "date":
            return "Line/area requires date as first field and a numeric metric after it."
        if not any(data_type in numeric for _name, data_type in columns[1:]):
            return "Line/area requires a numeric metric."
    elif kind in {"column", "bar", "pie"}:
        if len(columns) < 2:
            return "Comparison chart requires category and numeric metric."
        if columns[-1][1] not in numeric:
            return "Comparison chart metric must be the final numeric field."
    elif kind == "table":
        return "QL flatTable is disabled until the wizard/dataset table pipeline is implemented."
    return None
