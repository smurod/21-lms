"""
Build DataLens wizard chart shared configs (metric, pie, flatTable).

Wizard charts use datasetsIds + datasetsPartialFields instead of queryValue.
They support full colouring, aggregations, and all DataLens visualizations.
"""

from __future__ import annotations

from typing import Any

from dataset_pipeline import DatasetSpec


# ---------------------------------------------------------------------------
# Common helpers
# ---------------------------------------------------------------------------

_WIZARD_BASE = {
    "type": "datalens",
    "colors": [],
    "colorsConfig": {},
    "extraSettings": {},
    "filters": [],
    "geopointsConfig": {},
    "hierarchies": [],
    "labels": [],
    "links": [],
    "segments": [],
    "shapes": [],
    "shapesConfig": {},
    "sort": [],
    "tooltips": [],
    "updates": [],
    "version": "4",
}

# Palette slots used for category colouring (well-separated colours)
_PALETTE_SLOTS = (0, 6, 3, 14, 2, 15, 10, 19, 4, 12, 8, 1, 5, 7, 9, 11, 13, 16, 17, 18)


def _placeholder_item(field: dict[str, Any]) -> dict[str, Any]:
    """Build a wizard placeholder item from a result_schema field."""
    return {
        "guid": field["guid"],
        "title": field["title"],
        "datasetId": "{{DATASET_ID}}",   # replaced after dataset creation
        "type": field["type"],
        "data_type": field["data_type"],
        "cast": field["cast"],
        "calc_mode": field["calc_mode"],
        "aggregation": field["aggregation"],
        "avatar_id": field.get("avatar_id"),
        "managed_by": "user",
        "hidden": False,
        "valid": True,
        "lock_aggregation": False,
        "has_auto_aggregation": field.get("has_auto_aggregation", False),
        "description": "",
        "formula": field.get("formula", ""),
        "guid_formula": field.get("guid_formula", ""),
        "initial_data_type": field.get("initial_data_type", field["data_type"]),
        "default_value": None,
        "value_constraint": None,
    }


def _inject_dataset_id(shared: dict[str, Any], dataset_id: str) -> dict[str, Any]:
    """Replace {{DATASET_ID}} placeholders with the real dataset ID."""
    import json
    text = json.dumps(shared)
    text = text.replace("{{DATASET_ID}}", dataset_id)
    import json as _json
    return _json.loads(text)


def _base_shared(spec: DatasetSpec) -> dict[str, Any]:
    """Start with common wizard fields."""
    import copy
    shared = copy.deepcopy(_WIZARD_BASE)
    shared["datasetsIds"] = ["{{DATASET_ID}}"]
    shared["datasetsPartialFields"] = [spec.fields_partial()]
    return shared


# ---------------------------------------------------------------------------
# Metric / KPI card
# ---------------------------------------------------------------------------

def build_metric_shared(
    spec: DatasetSpec,
    measure_name: str,
    *,
    font_size: str = "l",
    font_color: str = "",
) -> dict[str, Any]:
    """Build shared config for a metric_wizard_node (KPI card).

    measure_name: column name of the numeric metric to display.
    font_size: 's' | 'm' | 'l'
    font_color: hex colour string (empty = DataLens default).
    """
    field = spec.field_by_name(measure_name)
    if field is None:
        raise ValueError(f"Metric field '{measure_name}' not found in dataset")

    shared = _base_shared(spec)
    shared["visualization"] = {
        "id": "metric",
        "type": "metric",
        "placeholders": [
            {
                "id": "measures",
                "type": "measures",
                "items": [_placeholder_item(field)],
            }
        ],
    }
    shared["extraSettings"] = {
        "metricFontSize": font_size,
        "metricFontColor": font_color,
    }
    return shared


# ---------------------------------------------------------------------------
# Pie chart
# ---------------------------------------------------------------------------

def build_pie_shared(
    spec: DatasetSpec,
    dimension_name: str,
    measure_name: str,
    category_values: list[str] | None = None,
) -> dict[str, Any]:
    """Build shared config for a pie graph_wizard_node.

    dimension_name: column used as pie sector labels.
    measure_name:   numeric column for sector sizes.
    category_values: actual values from the data — used for mountedColors.
    """
    dim_field = spec.field_by_name(dimension_name)
    msr_field = spec.field_by_name(measure_name)
    if dim_field is None:
        raise ValueError(f"Pie dimension '{dimension_name}' not found")
    if msr_field is None:
        raise ValueError(f"Pie measure '{measure_name}' not found")

    shared = _base_shared(spec)

    # Bind category to palette
    mounted_colors: dict[str, str] = {}
    if category_values:
        seen = list(dict.fromkeys(str(v) for v in category_values if v is not None))
        for i, val in enumerate(seen):
            mounted_colors[val] = str(_PALETTE_SLOTS[i % len(_PALETTE_SLOTS)])

    shared["colors"] = [_placeholder_item(dim_field)]
    shared["colorsConfig"] = {
        "palette": "datalens-neo-20-palette",
        "fieldGuid": dim_field["guid"],
        "coloredByMeasure": False,
        "polygonBorders": "show",
        "mountedColors": mounted_colors,
    }

    shared["visualization"] = {
        "id": "pie",
        "type": "pie",
        "placeholders": [
            {
                "id": "dimensions",
                "type": "dimensions",
                "items": [],              # wizard pie: dimensions = empty
            },
            {
                "id": "colors",
                "type": "colors",
                "items": [_placeholder_item(dim_field)],
            },
            {
                "id": "measures",
                "type": "measures",
                "items": [_placeholder_item(msr_field)],
            },
        ],
    }
    shared["extraSettings"] = {
        "legendMode": "show",
        "tooltipSum": "on",
    }
    return shared


# ---------------------------------------------------------------------------
# FlatTable
# ---------------------------------------------------------------------------

def build_flat_table_shared(
    spec: DatasetSpec,
    column_names: list[str],
    *,
    pagination: bool = True,
    limit: int = 100,
) -> dict[str, Any]:
    """Build shared config for a table_wizard_node (flat table).

    column_names: ordered list of column names to display.
    """
    items = []
    for name in column_names:
        field = spec.field_by_name(name)
        if field is None:
            continue
        items.append(_placeholder_item(field))

    if not items:
        raise ValueError("No valid columns for flatTable")

    shared = _base_shared(spec)
    shared["visualization"] = {
        "id": "flatTable",
        "type": "table",
        "placeholders": [
            {
                "id": "flat-table-columns",
                "type": "flat-table-columns",
                "items": items,
                "settings": {"groupping": "on"},
            }
        ],
    }
    shared["extraSettings"] = {
        "pagination": "on" if pagination else "off",
        "limit": limit,
        "totals": "off",
    }
    return shared


# ---------------------------------------------------------------------------
# Column / Bar (wizard version — supports multi-series with colours)
# ---------------------------------------------------------------------------

def build_column_shared(
    spec: DatasetSpec,
    x_names: list[str],
    y_names: list[str],
    color_name: str | None = None,
    chart_type: str = "column",
    category_values: list[str] | None = None,
) -> dict[str, Any]:
    """Build shared config for a column/bar graph_wizard_node.

    x_names: dimension columns for X axis.
    y_names: measure columns for Y axis.
    color_name: optional dimension column for series colouring.
    chart_type: 'column' | 'bar'.
    """
    shared = _base_shared(spec)

    x_items = []
    for name in x_names:
        f = spec.field_by_name(name)
        if f:
            x_items.append(_placeholder_item(f))

    y_items = []
    for name in y_names:
        f = spec.field_by_name(name)
        if f:
            y_items.append(_placeholder_item(f))

    if not x_items or not y_items:
        raise ValueError("Column chart requires at least one X and one Y field")

    placeholders = [
        {"id": "x", "type": "x", "items": x_items},
        {"id": "y", "type": "y", "items": y_items},
        {"id": "y2", "type": "y2", "items": []},
    ]

    # Colour series by dimension if requested
    if color_name:
        cf = spec.field_by_name(color_name)
        if cf:
            mounted_colors: dict[str, str] = {}
            if category_values:
                seen = list(dict.fromkeys(str(v) for v in category_values if v is not None))
                for i, val in enumerate(seen):
                    mounted_colors[val] = str(_PALETTE_SLOTS[i % len(_PALETTE_SLOTS)])
            shared["colors"] = [_placeholder_item(cf)]
            shared["colorsConfig"] = {
                "palette": "datalens-neo-20-palette",
                "fieldGuid": cf["guid"],
                "coloredByMeasure": False,
                "polygonBorders": "show",
                "mountedColors": mounted_colors,
            }

    shared["visualization"] = {
        "id": chart_type,
        "type": chart_type,
        "placeholders": placeholders,
    }
    shared["extraSettings"] = {
        "title": "",
        "titleMode": "show",
        "legendMode": "show",
        "tooltipSum": "on",
        "labelsPosition": "outside",
    }
    return shared


# ---------------------------------------------------------------------------
# Line / Area (wizard version)
# ---------------------------------------------------------------------------

def build_line_shared(
    spec: DatasetSpec,
    x_name: str,
    y_names: list[str],
    color_name: str | None = None,
    chart_type: str = "line",
) -> dict[str, Any]:
    """Build shared config for a line/area graph_wizard_node."""
    shared = _base_shared(spec)

    x_field = spec.field_by_name(x_name)
    if x_field is None:
        raise ValueError(f"Line X field '{x_name}' not found")

    y_items = []
    for name in y_names:
        f = spec.field_by_name(name)
        if f:
            y_items.append(_placeholder_item(f))

    if not y_items:
        raise ValueError("Line chart requires at least one Y field")

    placeholders = [
        {"id": "x", "type": "x", "items": [_placeholder_item(x_field)]},
        {"id": "y", "type": "y", "items": y_items},
        {"id": "y2", "type": "y2", "items": []},
    ]

    if color_name:
        cf = spec.field_by_name(color_name)
        if cf:
            shared["colors"] = [_placeholder_item(cf)]

    shared["visualization"] = {
        "id": chart_type,
        "type": chart_type,
        "placeholders": placeholders,
    }
    shared["extraSettings"] = {
        "title": "",
        "titleMode": "show",
        "legendMode": "show",
        "tooltipSum": "on",
        "navigatorSettings": {"isNavigatorAvailable": False, "selectedLines": []},
    }
    return shared


# ---------------------------------------------------------------------------
# Public API: finalise shared after dataset_id is known
# ---------------------------------------------------------------------------

def finalise_shared(shared: dict[str, Any], dataset_id: str) -> dict[str, Any]:
    """Replace {{DATASET_ID}} with the real dataset ID after creation."""
    return _inject_dataset_id(shared, dataset_id)
