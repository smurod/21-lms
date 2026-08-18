"""
Build QL chart JSON for DataLens using saved real examples.

Supported QL chart types:
- line / area: time dynamics;
- column / bar: category comparisons;
- pie: compact category share;
- table: detailed rows.
"""

from __future__ import annotations

import copy
import json
from pathlib import Path
from typing import Literal

from chart_contract import ql_contract_error

SAMPLES_DIR = Path(__file__).resolve().parent / "samples"

ChartType = Literal["line", "area", "column", "bar", "pie", "table"]


def normalize_chart_type(value: str) -> ChartType:
    """Map DataLens internal chart IDs to our supported template names."""
    v = (value or "").strip().lower()
    if v in {"table", "flattable", "flat-table", "flat_table", "pivot_table"}:
        return "table"
    if v in {"line", "lines", "trend"}:
        return "line"
    if v in {"area", "normalized_area"}:
        return "area"
    if v in {"column", "verticalcolumn", "column_chart"}:
        return "column"
    if v in {"bar", "bars", "horizontal_bar", "normalized_bar"}:
        return "bar"
    if v in {"pie", "donut"}:
        return "pie"
    # Default to table for unknown types: it is the safest visualization.
    return "table"

# File containing a known-good full chart config for each type.
_TEMPLATES: dict[str, str] = {
    "line": "chart_xp_dynamics_by_day.json",
    "area": "chart_xp_dynamics_by_day.json",
    "column": "chart_submissions_by_status.json",
    "bar": "chart_submissions_by_status.json",
    "pie": "chart_submissions_by_status.json",
    "table": "chart_top10_users_xp.json",
}


def _load_shared_template(chart_type: ChartType) -> dict:
    file_name = _TEMPLATES[chart_type]
    path = SAMPLES_DIR / file_name
    record = json.loads(path.read_text(encoding="utf-8"))
    shared = record["data"]["shared"]
    if isinstance(shared, str):
        shared = json.loads(shared)
    return copy.deepcopy(shared)


def _field(guid: str, data_type: str) -> dict:
    # data_type examples from real QL charts: string, integer, date, float.
    # Existing legacy chart metadata can mark a PostgreSQL numeric score as
    # string when its initial sample had no non-null values. Restore the
    # unambiguous analytical type before sending the QL placeholder.
    normalized_type = data_type
    lower_guid = guid.lower()
    if normalized_type == "string" and any(token in lower_guid for token in ("score", "rate", "percent", "xp")):
        normalized_type = "float"
    return {
        "guid": guid,
        "title": guid,
        "datasetId": "ql-mocked-dataset",
        "data_type": normalized_type,
        "cast": normalized_type,
        "type": "DIMENSION",
        "calc_mode": "direct",
        "inspectHidden": True,
        "formulaHidden": True,
        "noEdit": True,
    }


def _set_placeholder_items(shared: dict, placeholder_id: str, fields: list[dict]) -> None:
    for placeholder in shared["visualization"]["placeholders"]:
        if placeholder["id"] == placeholder_id:
            placeholder["items"] = fields
            return
    raise ValueError(f"Placeholder {placeholder_id!r} not found")


def build_ql_chart(
    *,
    connection_id: str,
    sql: str,
    chart_type: ChartType,
    columns: list[tuple[str, str]],
    connection_type: str = "postgres",
    category_values: list[object] | None = None,
) -> dict:
    """
    Create full QL chart payload.

    columns: list of (alias, type), where alias must match SQL SELECT aliases.
    """
    chart_type = normalize_chart_type(chart_type)
    contract_error = ql_contract_error(chart_type, columns)
    if contract_error:
        raise ValueError(contract_error)
    shared = _load_shared_template(chart_type)
    shared["visualization"]["id"] = chart_type

    resolved_connection_type = "postgres" if connection_type.lower() == "postgresql" else connection_type.lower()
    shared["connection"] = {
        "entryId": connection_id,
        "type": resolved_connection_type,
        "dataExportForbidden": False,
    }
    shared["queryValue"] = sql.strip()

    field_by_name = {name: _field(name, data_type) for name, data_type in columns}

    if chart_type in {"line", "area"}:
        if len(columns) < 2:
            raise ValueError("line chart needs at least x and one y column")
        x_name = columns[0][0]
        y_names = [name for name, _ in columns[1:]]
        _set_placeholder_items(shared, "x", [field_by_name[x_name]])
        _set_placeholder_items(shared, "y", [field_by_name[name] for name in y_names])
        # In real config, axis mode is stored per first X field name.
        x_settings = next(
            p for p in shared["visualization"]["placeholders"] if p["id"] == "x"
        )
        x_settings["settings"]["axisModeMap"] = {x_name: "discrete"}

    elif chart_type in {"column", "bar"}:
        if len(columns) < 2:
            raise ValueError("column chart needs x and y columns")
        # For an ordinary categorical query the final numeric SELECT field is
        # the metric; e.g. score, COUNT(*) or level, user_count. Aggregated
        # table-to-bar conversion has already reduced its fields to one metric.
        numeric_types = {"integer", "float"}
        y_index = next(
            (
                i for i in range(len(columns) - 1, -1, -1)
                if columns[i][1] in numeric_types
            ),
            len(columns) - 1,
        )
        y_name = columns[y_index][0]
        x_names = [name for i, (name, _) in enumerate(columns) if i != y_index]

        _set_placeholder_items(
            shared, "x", [field_by_name[name] for name in x_names]
        )
        _set_placeholder_items(shared, "y", [field_by_name[y_name]])
        x_settings = next(
            p for p in shared["visualization"]["placeholders"] if p["id"] == "x"
        )
        # Each categorical X field needs its own axis-mode entry.
        x_settings["settings"]["axisModeMap"] = {
            name: "discrete" for name in x_names
        }

    elif chart_type == "pie":
        if len(columns) < 2:
            raise ValueError("pie chart needs a category and a numeric measure")
        numeric_types = {"integer", "float"}
        measure_index = next(
            (i for i in range(len(columns) - 1, -1, -1) if columns[i][1] in numeric_types),
            len(columns) - 1,
        )
        measure_name = columns[measure_index][0]
        dimension_names = [name for i, (name, _) in enumerate(columns) if i != measure_index]
        placeholders = shared["visualization"]["placeholders"]
        for placeholder in placeholders:
            if placeholder["id"] == "x":
                placeholder["id"] = "dimensions"
                placeholder["items"] = [field_by_name[name] for name in dimension_names]
            elif placeholder["id"] == "y":
                placeholder["id"] = "measures"
                placeholder["items"] = [field_by_name[measure_name]]

        # Bind the category to DataLens' categorical palette. `colors` alone is
        # insufficient in the QL renderer: colorsConfig must identify the field
        # that owns category values, otherwise every sector uses one series blue.
        category_name = dimension_names[0]
        shared["colors"] = [field_by_name[category_name]]
        # Use visibly separated palette slots. Sequential 0/1 values in the
        # Neo palette can be adjacent blue shades in the local DataLens theme.
        palette_slots = (6, 3, 14, 2, 15, 10, 19, 4, 12, 8)
        mounted_colors = {
            str(value): str(palette_slots[index % len(palette_slots)])
            for index, value in enumerate(dict.fromkeys(category_values or []))
            if value is not None
        }
        shared["colorsConfig"] = {
            "palette": "datalens-neo-20-palette",
            "fieldGuid": category_name,
            "coloredByMeasure": False,
            "polygonBorders": "show",
            # DataLens QL needs explicit value-to-palette bindings. Without
            # these bindings it treats a pie as one measure series and paints
            # every sector with the same default blue.
            "mountedColors": mounted_colors,
        }

    elif chart_type == "table":
        _set_placeholder_items(
            shared,
            "flat-table-columns",
            [field_by_name[name] for name, _ in columns],
        )

    else:
        raise ValueError(f"Unsupported chart type: {chart_type}")

    return shared
