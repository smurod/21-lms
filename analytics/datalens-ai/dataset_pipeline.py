"""
Dataset pipeline for DataLens wizard charts.

Creates a DataLens Dataset with a Custom SQL source from a validated SQL query,
then builds the result_schema from the actual PostgreSQL column types.

Used by wizard_chart_builder to power metric / pie / flatTable charts.
"""

from __future__ import annotations

import asyncio
import logging
import uuid
from typing import Any

import psycopg

logger = logging.getLogger(__name__)

# Map PostgreSQL native types → DataLens data_type
_PG_TO_DL: dict[str, str] = {
    "integer":          "integer",
    "bigint":           "integer",
    "smallint":         "integer",
    "int4":             "integer",
    "int8":             "integer",
    "int2":             "integer",
    "serial":           "integer",
    "bigserial":        "integer",
    "numeric":          "float",
    "decimal":          "float",
    "real":             "float",
    "double precision": "float",
    "float4":           "float",
    "float8":           "float",
    "money":            "float",
    "boolean":          "boolean",
    "bool":             "boolean",
    "date":             "date",
    "timestamp":        "genericdatetime",
    "timestamp without time zone": "genericdatetime",
    "timestamp with time zone":    "genericdatetime",
    "timestamptz":      "genericdatetime",
    "time":             "string",
    "text":             "string",
    "varchar":          "string",
    "character varying": "string",
    "char":             "string",
    "uuid":             "string",
    "json":             "string",
    "jsonb":            "string",
}

# DataLens type → field type (DIMENSION or MEASURE)
_MEASURE_TYPES = {"integer", "float"}
_DIMENSION_TYPES = {"string", "date", "genericdatetime", "boolean"}


def _pg_type_to_dl(pg_type: str) -> str:
    """Convert PostgreSQL column type to DataLens data_type."""
    lower = pg_type.lower().strip()
    if lower in _PG_TO_DL:
        return _PG_TO_DL[lower]
    for key, val in _PG_TO_DL.items():
        if key in lower:
            return val
    return "string"


def _dl_field_type(dl_type: str) -> str:
    return "MEASURE" if dl_type in _MEASURE_TYPES else "DIMENSION"


def _dl_aggregation(dl_type: str) -> str:
    """Default aggregation for a field type."""
    return "sum" if dl_type in _MEASURE_TYPES else "none"


def _safe_name(title: str) -> str:
    """Strip spaces for DataLens field source names."""
    return title.strip()


def _introspect_sql_sync(db_url: str, sql: str) -> list[dict[str, Any]]:
    """Execute SQL with LIMIT 0 and return column metadata (name + pg_type)."""
    conn = None
    try:
        conn = psycopg.connect(db_url, autocommit=True)
        with conn.cursor() as cur:
            # Use LIMIT 0 to get column types without fetching rows
            wrapped = f"SELECT * FROM ({sql.rstrip(';')}) AS _ds_introspect LIMIT 0"
            cur.execute(wrapped)
            if cur.description is None:
                return []
            cols = []
            for desc in cur.description:
                # desc.type_code → oid → pg type name
                type_name = "text"
                try:
                    cur.execute(
                        "SELECT typname FROM pg_type WHERE oid = %s",
                        (desc.type_code,),
                    )
                    row = cur.fetchone()
                    if row:
                        type_name = row[0]
                except Exception:
                    pass
                cols.append({"name": desc.name, "pg_type": type_name})
            return cols
    except Exception as exc:
        logger.warning("SQL introspection failed: %s", exc)
        return []
    finally:
        if conn:
            conn.close()


async def introspect_sql(db_url: str, sql: str) -> list[dict[str, Any]]:
    """Non-blocking SQL introspection."""
    return await asyncio.to_thread(_introspect_sql_sync, db_url, sql)


def build_result_schema(
    columns: list[dict[str, Any]],
    avatar_id: str,
) -> list[dict[str, Any]]:
    """Build DataLens result_schema from introspected columns.

    Each field gets a stable UUID guid derived from the column name,
    which is used in wizard chart placeholders.
    """
    schema = []
    for col in columns:
        name = col["name"]
        dl_type = _pg_type_to_dl(col["pg_type"])
        field_type = _dl_field_type(dl_type)
        guid = str(uuid.uuid5(uuid.NAMESPACE_OID, name))
        schema.append(
            {
                "guid": guid,
                "title": name,
                "source": name,
                "type": field_type,
                "data_type": dl_type,
                "cast": dl_type,
                "calc_mode": "direct",
                "aggregation": _dl_aggregation(dl_type),
                "avatar_id": avatar_id,
                "managed_by": "user",
                "valid": True,
                "hidden": False,
                "lock_aggregation": False,
                "has_auto_aggregation": field_type == "MEASURE",
                "description": "",
                "default_value": None,
                "value_constraint": None,
                "formula": "",
                "guid_formula": "",
                "initial_data_type": dl_type,
            }
        )
    return schema


def build_raw_schema(columns: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """Build raw_schema for the SQL source (what PostgreSQL actually returns).

    user_type is required by DataLens control-api marshmallow schema
    (dl_api_connector.api_schema.source_base.SchemaColumnSchema).
    Its values match dl_constants.enums.UserDataType names.
    """
    result = []
    for col in columns:
        name = col["name"]
        dl_type = _pg_type_to_dl(col["pg_type"])
        result.append(
            {
                "name": name,
                "title": name,
                "user_type": dl_type,          # ← required by DataLens schema
                "type": dl_type,
                "nullable": True,
                "description": "",
                "native_type": {
                    "name": col["pg_type"],
                    "nullable": True,
                    "conn_type": "postgres",
                    "native_type_class_name": "common_native_type",
                },
                "lock_aggregation": False,
                "has_auto_aggregation": False,
            }
        )
    return result


class DatasetSpec:
    """Holds everything needed to create a DataLens dataset and its charts."""

    def __init__(
        self,
        sql: str,
        columns: list[dict[str, Any]],
        dataset_id: str | None = None,
    ):
        self.sql = sql
        self.columns = columns            # [{"name": ..., "pg_type": ...}]
        self.dataset_id = dataset_id      # filled after creation
        self.source_id = str(uuid.uuid4())
        self.avatar_id = str(uuid.uuid4())
        self.result_schema = build_result_schema(columns, self.avatar_id)
        self.raw_schema = build_raw_schema(columns)

    def field_by_name(self, name: str) -> dict[str, Any] | None:
        """Return the result_schema field descriptor for a column name."""
        for f in self.result_schema:
            if f["title"] == name:
                return f
        return None

    def fields_partial(self) -> list[dict[str, Any]]:
        """datasetsPartialFields entry — lightweight list for wizard chart shared."""
        return [
            {"guid": f["guid"], "title": f["title"], "calc_mode": f["calc_mode"]}
            for f in self.result_schema
        ]

    def dimension_fields(self) -> list[dict[str, Any]]:
        return [f for f in self.result_schema if f["type"] == "DIMENSION"]

    def measure_fields(self) -> list[dict[str, Any]]:
        return [f for f in self.result_schema if f["type"] == "MEASURE"]
