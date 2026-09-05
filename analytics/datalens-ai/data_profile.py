"""Composite data profile for the full-DB auto analysis.

Ranks tables so the agent can "decide on its own" in a defensible way:

    score = 0.4 × log-norm(rows) + 0.3 × log-norm(links) + 0.3 × density

- rows     — amount of data (log-normalised so 100k rows does not drown 1k);
- links    — FK relations in + out (hub tables of the schema);
- density  — share of numeric/date/timestamp columns, i.e. what can actually
             be visualised (a 3-row lookup table scores low automatically).
"""

from __future__ import annotations

import math
from dataclasses import dataclass
from typing import Any

from schema_analyzer import SKIP_TABLES, SchemaAnalysis

PROFILE_WEIGHTS = {"rows": 0.4, "links": 0.3, "density": 0.3}

_ANALYTIC_TYPES = ("int", "serial", "numeric", "float", "double", "real", "date", "timestamp")


@dataclass(frozen=True)
class TableScore:
    table: str
    rows: int
    links: int        # FK relations in + out
    density: float    # 0..1 — share of numeric/date columns
    score: float      # 0..100


@dataclass(frozen=True)
class DataProfile:
    ranked: list[TableScore]

    @property
    def top_tables(self) -> list[str]:
        return [item.table for item in self.ranked]


def build_data_profile(schema: SchemaAnalysis) -> DataProfile:
    """Score every non-empty table of the schema and rank it."""
    links: dict[str, int] = {}
    for rel in schema.relationships:
        links[rel["from_table"]] = links.get(rel["from_table"], 0) + 1
        links[rel["to_table"]] = links.get(rel["to_table"], 0) + 1

    candidates: list[tuple[Any, int, float]] = []
    for table in schema.tables:
        if table.name.lower() in SKIP_TABLES or table.row_count <= 0:
            continue
        cols = table.columns or []
        analytic = sum(
            1 for c in cols if any(t in (c.data_type or "").lower() for t in _ANALYTIC_TYPES)
        )
        density = analytic / len(cols) if cols else 0.0
        candidates.append((table, links.get(table.name, 0), density))

    if not candidates:
        return DataProfile(ranked=[])

    max_rows_log = max(math.log1p(c[0].row_count) for c in candidates)
    max_links_log = max((math.log1p(c[1]) for c in candidates), default=0.0) or 1.0

    scored: list[TableScore] = []
    for table, link_count, density in candidates:
        n_rows = math.log1p(table.row_count) / max_rows_log if max_rows_log else 0.0
        n_links = math.log1p(link_count) / max_links_log
        score = (
            PROFILE_WEIGHTS["rows"] * n_rows
            + PROFILE_WEIGHTS["links"] * n_links
            + PROFILE_WEIGHTS["density"] * density
        ) * 100
        scored.append(
            TableScore(table.name, table.row_count, link_count, round(density, 2), round(score, 1))
        )

    scored.sort(key=lambda item: item.score, reverse=True)
    return DataProfile(ranked=scored)


def format_data_profile(profile: DataProfile, top_n: int = 8) -> str:
    """Text block for LLM prompts: the ranking with the numbers behind it."""
    if not profile.ranked:
        return ""
    lines = [
        "Профиль данных — рейтинг таблиц (объём данных, связи, аналитическая ценность):"
    ]
    for rank, item in enumerate(profile.ranked[:top_n], start=1):
        lines.append(
            f"{rank}. {item.table} — строк: {item.rows}, связей: {item.links}, "
            f"аналитическая плотность: {round(item.density * 100)}%, итоговый скор: {item.score}"
        )
    lines.append(
        "Скор = 0.4×объём данных + 0.3×связи + 0.3×аналитическая плотность (лог-нормировка). "
        "Таблицы выше по рейтингу важнее для обзора."
    )
    return "\n".join(lines)
