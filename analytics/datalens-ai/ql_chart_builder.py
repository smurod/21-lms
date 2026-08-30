"""
Build QL chart JSON for DataLens using saved real examples.

Supported QL chart types:
- line / area: time dynamics;
- column / bar: category comparisons;
- pie: compact category share (colours via colorsConfig);
- table: detailed rows (table_ql_node).
"""

from __future__ import annotations

import copy
import json
from pathlib import Path
from typing import Literal

from chart_contract import ql_contract_error

SAMPLES_DIR = Path(__file__).resolve().parent / "samples"

ChartType = Literal["line", "area", "column", "bar", "pie", "table"]

# extraSettings applied to every d3 chart — shows value labels outside bars/columns.
# Matches the production dashboard format from data.json.
_D3_EXTRA_SETTINGS = {
    "title": "",
    "titleMode": "show",
    "labelsPosition": "outside",
}

# extraSettings for table charts.
_TABLE_EXTRA_SETTINGS = {
    "pagination": "on",
    "limit": 100,
}


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
    # Default to bar: safer than table which has a known renderer issue.
    return "bar"


# File containing a known-good full chart config for each type.
_TEMPLATES: dict[str, str] = {
    "line":   "chart_xp_dynamics_by_day.json",
    "area":   "chart_xp_dynamics_by_day.json",
    "column": "chart_submissions_by_status.json",
    "bar":    "chart_submissions_by_status.json",
    "pie":    "chart_pie_template.json",
    "table":  "chart_top10_users_xp.json",
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
    """Build a QL placeholder field descriptor."""
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
    Create full QL chart payload matching the production DataLens format.

    columns: list of (alias, type), where alias must match SQL SELECT aliases.

    Returns the shared object that must be wrapped as:
        {"template": "ql", "data": <returned dict>}
    before POSTing to /api/charts/v1/charts.
    """
    chart_type = normalize_chart_type(chart_type)
    contract_error = ql_contract_error(chart_type, columns)
    if contract_error:
        raise ValueError(contract_error)

    shared = _load_shared_template(chart_type)

    # --- connection & SQL ---
    resolved_connection_type = (
        "postgres" if connection_type.lower() == "postgresql" else connection_type.lower()
    )
    shared["connection"] = {
        "entryId": connection_id,
        "type": resolved_connection_type,
        "dataExportForbidden": False,
    }
    shared["queryValue"] = sql.strip()

    # --- extraSettings: labels outside for d3 charts ---
    if chart_type == "table":
        shared["extraSettings"] = _TABLE_EXTRA_SETTINGS.copy()
    else:
        shared["extraSettings"] = _D3_EXTRA_SETTINGS.copy()

    field_by_name = {name: _field(name, data_type) for name, data_type in columns}

    # ------------------------------------------------------------------ line / area
    if chart_type in {"line", "area"}:
        if len(columns) < 2:
            raise ValueError("line chart needs at least x and one y column")
        shared["visualization"]["id"] = chart_type
        x_name = columns[0][0]
        y_names = [name for name, _ in columns[1:]]
        _set_placeholder_items(shared, "x", [field_by_name[x_name]])
        _set_placeholder_items(shared, "y", [field_by_name[name] for name in y_names])
        x_settings = next(
            p for p in shared["visualization"]["placeholders"] if p["id"] == "x"
        )
        x_settings["settings"]["axisModeMap"] = {x_name: "discrete"}

    # ------------------------------------------------------------------ column / bar
    elif chart_type in {"column", "bar"}:
        if len(columns) < 2:
            raise ValueError("column chart needs x and y columns")
        # Production format: viz.id="bar" for bar charts, "column" for column.
        shared["visualization"]["id"] = chart_type
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
        _set_placeholder_items(shared, "x", [field_by_name[name] for name in x_names])
        _set_placeholder_items(shared, "y", [field_by_name[y_name]])
        x_settings = next(
            p for p in shared["visualization"]["placeholders"] if p["id"] == "x"
        )
        x_settings["settings"]["axisModeMap"] = {name: "discrete" for name in x_names}

    # ------------------------------------------------------------------ pie
    elif chart_type == "pie":
        if len(columns) < 2:
            raise ValueError("pie chart needs a category and a numeric measure")

        # CRITICAL: Production DataLens pie uses viz.id="pie", viz.type="column".
        # The pie template already has this set correctly.
        # Placeholders have id="dimensions"/"measures" but type="x"/"y" — as in production.
        numeric_types = {"integer", "float"}
        measure_index = next(
            (i for i in range(len(columns) - 1, -1, -1) if columns[i][1] in numeric_types),
            len(columns) - 1,
        )
        measure_name = columns[measure_index][0]
        dimension_names = [name for i, (name, _) in enumerate(columns) if i != measure_index]

        _set_placeholder_items(shared, "dimensions", [field_by_name[name] for name in dimension_names])
        _set_placeholder_items(shared, "measures", [field_by_name[measure_name]])

        # Colour binding — field in colors[] + colorsConfig with mountedColors.
        category_name = dimension_names[0]
        shared["colors"] = [field_by_name[category_name]]
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
            "mountedColors": mounted_colors,
        }

    # ------------------------------------------------------------------ table
    elif chart_type == "table":
        # table_ql_node uses visualization.id="table", viz.type="table".
        shared["visualization"]["id"] = "table"
        _set_placeholder_items(
            shared,
            "flat-table-columns",
            [field_by_name[name] for name, _ in columns],
        )

    else:
        raise ValueError(f"Unsupported chart type: {chart_type}")

    return shared
