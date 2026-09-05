"""Unit tests for the composite data profile (full-DB auto analysis)."""

from __future__ import annotations

from types import SimpleNamespace

from data_profile import build_data_profile, format_data_profile


def _col(data_type: str, is_fk: bool = False, foreign_table: str | None = None):
    return SimpleNamespace(
        name="c", data_type=data_type, is_foreign_key=is_fk, foreign_table=foreign_table,
    )


def _table(name: str, row_count: int, columns: list, fks: list[tuple[str, str]] | None = None):
    for fk_from, fk_to in fks or []:
        columns.append(_col("integer", is_fk=True, foreign_table=fk_to))
        fk_from  # noqa: B018 - kept for symmetry with relationships below
    return SimpleNamespace(name=name, row_count=row_count, columns=columns)


def _schema(tables: list, relationships: list[dict]):
    return SimpleNamespace(tables=tables, relationships=relationships)


def test_data_hub_ranks_above_dictionary():
    schema = _schema(
        tables=[
            _table("statuses", 5, [_col("character varying")]),
            _table("submissions", 543, [
                _col("integer"), _col("timestamp"), _col("numeric"),
            ], fks=[("submissions", "users"), ("submissions", "statuses")]),
            _table("users", 120, [_col("integer"), _col("timestamp"), _col("character varying")]),
        ],
        relationships=[
            {"from_table": "submissions", "to_table": "users"},
            {"from_table": "submissions", "to_table": "statuses"},
        ],
    )

    profile = build_data_profile(schema)

    assert profile.top_tables[0] == "submissions"
    assert profile.top_tables[-1] == "statuses"


def test_links_count_in_both_directions():
    schema = _schema(
        tables=[
            _table("reviews", 50, [_col("integer")], fks=[("reviews", "users")]),
            _table("users", 100, [_col("integer")]),
        ],
        relationships=[{"from_table": "reviews", "to_table": "users"}],
    )

    scores = {item.table: item.links for item in build_data_profile(schema).ranked}

    # reviews has 1 out-FK, users receives 1 in-FK — both counted.
    assert scores["reviews"] == 1
    assert scores["users"] == 1


def test_density_penalises_text_only_tables():
    schema = _schema(
        tables=[
            _table("logs", 1000, [_col("text"), _col("text"), _col("text")]),
            _table("events", 1000, [_col("integer"), _col("timestamp"), _col("numeric")]),
        ],
        relationships=[],
    )

    scores = {item.table: item for item in build_data_profile(schema).ranked}

    assert scores["events"].density == 1.0
    assert scores["logs"].density == 0.0
    assert scores["events"].score > scores["logs"].score


def test_empty_tables_are_skipped():
    schema = _schema(
        tables=[_table("empty", 0, [_col("integer")]), _table("real", 10, [_col("integer")])],
        relationships=[],
    )

    profile = build_data_profile(schema)

    assert profile.top_tables == ["real"]


def test_format_includes_numbers_and_ranking():
    schema = _schema(
        tables=[_table("submissions", 543, [_col("integer"), _col("timestamp")])],
        relationships=[],
    )

    text = format_data_profile(build_data_profile(schema))

    assert "1. submissions" in text
    assert "строк: 543" in text
    assert "итоговый скор:" in text
    assert "0.4×объём" in text


def test_degenerate_column_is_rejected():
    """A single-category comparison chart has nothing to compare — it must be
    rejected with a hint towards a KPI metric (user-reported case)."""
    from sql_pipeline import chart_density_error

    error = chart_density_error("column", 1)
    assert "минимум 2 категории" in error
    assert "metric" in error
    assert chart_density_error("bar", 1) is not None
    assert chart_density_error("column", 2) is None


def test_degenerate_line_is_rejected():
    from sql_pipeline import chart_density_error

    assert "минимум 2 точки" in chart_density_error("line", 1)
    assert chart_density_error("area", 1) is not None
    assert chart_density_error("line", 2) is None
    assert chart_density_error("metric", 1) is None


def test_wizard_column_binds_category_colors():
    """Wizard column/bar must match the correctly rendering QL reference:
    category dimension in colors[] + palette mountedColors → one series per
    category, every bar its own colour."""
    from dataset_pipeline import DatasetSpec
    from wizard_chart_builder import build_column_shared

    spec = DatasetSpec(
        sql="SELECT project, COUNT(*) AS cnt FROM submissions GROUP BY project",
        columns=[{"name": "project", "pg_type": "varchar"}, {"name": "cnt", "pg_type": "int8"}],
    )

    shared = build_column_shared(spec, ["project"], ["cnt"], chart_type="bar")

    assert [p["id"] for p in shared["visualization"]["placeholders"]] == ["x", "y", "y2"]
    assert shared["colors"][0]["title"] == "project"
    config = shared["colorsConfig"]
    # Default palette (no "palette" key — neutral20 resolves to GRAYSCALE).
    assert "palette" not in config
    assert config["fieldGuid"]  # bound to the category dimension
