"""Build DataLens dashboard JSON from sections (36-column grid)."""

from __future__ import annotations

import random
import string
from typing import Any, Literal

ChartKind = Literal["line", "area", "column", "bar", "pie", "table", "metric"]
Chart = dict[str, Any]
Section = dict[str, Any]


def _id(length: int = 2) -> str:
    alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789"
    base = "".join(random.choice(alphabet) for _ in range(length))
    suffix = "".join(random.choice(string.ascii_letters + string.digits) for _ in range(6))
    return f"{base}{suffix}"


def _text_widget(text: str, *, size: str = "m", title: bool = False, align_right: bool = False) -> dict[str, Any]:
    return {
        "id": _id(),
        "data": {
            "size": "l" if title else size,
            "text": text,
            "showInTOC": title,
            "textColor": "var(--g-color-text-primary)" if title else "var(--g-color-text-secondary)",
            "autoHeight": False,
            "background": {"color": "transparent"},
            "align": "right" if align_right else "left",
        },
        "type": "title" if title else "text",
        "namespace": "default",
    }


def _chart_widget(chart_id: str, title: str) -> dict[str, Any]:
    return {
        "id": _id(),
        "data": {
            "tabs": [
                {
                    "id": _id(),
                    "hint": "",
                    "title": title,
                    "params": {},
                    "chartId": chart_id,
                    "isDefault": True,
                    "autoHeight": False,
                    "background": {"color": "like-chart-bg"},
                    "enableHint": False,
                    "description": "",
                    "enableDescription": False,
                }
            ],
            "hideTitle": False,
        },
        "type": "widget",
        "namespace": "default",
    }


def _layout(item_id: str, *, x: int, y: int, w: int, h: int) -> dict[str, Any]:
    return {"i": item_id, "x": x, "y": y, "w": w, "h": h}


def build_dashboard_data(*, title: str, sections: list[Section], tab_title: str = "Overview") -> dict[str, Any]:
    items: list[dict[str, Any]] = []
    layout: list[dict[str, Any]] = []
    # DataLens uses a 36-column layout. Keep horizontal and vertical spacing
    # separate: chart cards need a pronounced empty band between columns, while
    # row spacing can stay compact. One grid column on each outer side and four
    # in the centre keep widget backgrounds from visually merging.
    outer_gutter = 1
    horizontal_gutter = 2
    vertical_gap = 1
    column_width = 15  # 1 + 15 + 4 empty columns + 15 + 1 = 36
    # `hideDashTitle` deliberately removes DataLens' own entry title. Reserve
    # breathing room so the first section never appears clipped under Laravel's
    # external dashboard header.
    y = 2

    rendered_sections = 0
    for section in sections:
        charts = section.get("charts", [])
        if not charts:
            continue

        # The Laravel card already supplies the dashboard title. Hiding only the
        # first DataLens section header avoids a clipped/wrapped duplicate title
        # at the iframe boundary; later sections retain their useful grouping.
        if rendered_sections > 0:
            header_title = _text_widget(section.get("title", ""), size="l", title=True)
            items.append(header_title)
            layout.append(_layout(header_title["id"], x=outer_gutter, y=y, w=12, h=2))

            note = section.get("note")
            if note:
                note_widget = _text_widget(note, size="s", align_right=True)
                items.append(note_widget)
                layout.append(_layout(note_widget["id"], x=13, y=y, w=22, h=2))

            y += 2 + vertical_gap
        left_pending = None
        paired_compact_indices: set[int] = set()
        pending_compact_index: int | None = None
        for index, chart in enumerate(charts):
            if chart.get("kind", "table") not in {"column", "bar", "pie"}:
                pending_compact_index = None
                continue
            if pending_compact_index is None:
                pending_compact_index = index
            else:
                paired_compact_indices.update({pending_compact_index, index})
                pending_compact_index = None
        # Only adjacent complete pairs get the half-width layout. A lone or
        # third compact chart becomes full-width instead of leaving an empty row.

        for chart_index, chart in enumerate(charts):
            kind = chart.get("kind", "table")
            widget = _chart_widget(chart["id"], chart["title"])
            items.append(widget)

            # Time series and tables are full-width. Comparison/share charts use
            # two cards per row only when a matching neighbour exists.
            is_column = chart_index in paired_compact_indices
            w = column_width if is_column else 36 - (outer_gutter * 2)
            h = 16 if is_column else 20

            if is_column:
                if left_pending is None:
                    layout.append(_layout(widget["id"], x=outer_gutter, y=y, w=w, h=h))
                    left_pending = {"top_y": y, "h": h}
                else:
                    layout.append(_layout(widget["id"], x=outer_gutter + column_width + (horizontal_gutter * 2), y=left_pending["top_y"], w=w, h=h))
                    y = max(y, left_pending["top_y"] + max(h, left_pending["h"]) + vertical_gap)
                    left_pending = None
            else:
                if left_pending is not None:
                    y = left_pending["top_y"] + left_pending["h"] + vertical_gap
                    left_pending = None
                layout.append(_layout(widget["id"], x=outer_gutter, y=y, w=w, h=h))
                y += h + vertical_gap

        if left_pending is not None:
            y = left_pending["top_y"] + left_pending["h"] + vertical_gap
        rendered_sections += 1
        # A larger visual break before the next section title.
        y += 2

    return {
        "salt": str(random.random()),
        "tabs": [
            {
                "id": _id(4),
                "items": items,
                "title": tab_title,
                "layout": layout,
                "aliases": {},
                "connections": [],
            }
        ],
        "counter": len(items),
        "settings": {
            "hideTabs": False,
            "expandTOC": False,
            "globalParams": {},
            "loadPriority": "charts",
            # Laravel renders the dashboard title in its own admin header.
            # Avoid repeating the generated DataLens entry name inside iframe.
            "hideDashTitle": True,
            "silentLoading": False,
            "autoupdateInterval": None,
            "dependentSelectors": True,
            "loadOnlyVisibleCharts": True,
            "maxConcurrentRequests": None,
        },
    }
