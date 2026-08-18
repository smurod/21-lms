"""
Schema Analyzer — analyzes database schema via information_schema.
Supports PostgreSQL, MySQL.
"""

from __future__ import annotations

import logging
import math
from typing import Any

import psycopg
from pydantic import BaseModel

from models.schema import ColumnInfo, SchemaAnalysis, TableInfo

logger = logging.getLogger(__name__)

# SQL query to get table metadata from PostgreSQL
POSTGRES_SCHEMA_QUERY = """
SELECT
    t.table_schema,
    t.table_name,
    c.column_name,
    c.data_type,
    c.is_nullable,
    COALESCE(kcu.column_name IS NOT NULL, FALSE) AS is_primary_key,
    fk_data.foreign_table,
    fk_data.foreign_column
FROM information_schema.tables t
JOIN information_schema.columns c
    ON t.table_schema = c.table_schema AND t.table_name = c.table_name
LEFT JOIN (
    SELECT tc.table_schema, tc.table_name, kcu.column_name
    FROM information_schema.table_constraints tc
    JOIN information_schema.key_column_usage kcu
        ON tc.constraint_name = kcu.constraint_name
        AND tc.table_schema = kcu.table_schema
    WHERE tc.constraint_type = 'PRIMARY KEY'
) kcu ON t.table_schema = kcu.table_schema
    AND t.table_name = kcu.table_name
    AND c.column_name = kcu.column_name
LEFT JOIN (
    SELECT
        kcu.table_schema,
        kcu.table_name,
        kcu.column_name,
        ccu.table_name AS foreign_table,
        ccu.column_name AS foreign_column
    FROM information_schema.table_constraints tc
    JOIN information_schema.key_column_usage kcu
        ON tc.constraint_name = kcu.constraint_name
        AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage ccu
        ON tc.constraint_name = ccu.constraint_name
        AND tc.table_schema = ccu.table_schema
    WHERE tc.constraint_type = 'FOREIGN KEY'
) fk_data ON t.table_schema = fk_data.table_schema
    AND t.table_name = fk_data.table_name
    AND c.column_name = fk_data.column_name
WHERE t.table_schema NOT IN ('pg_catalog', 'information_schema')
    AND t.table_type = 'BASE TABLE'
ORDER BY t.table_schema, t.table_name, c.ordinal_position;
"""

# Tables to skip (system/technical tables)
SKIP_TABLES = {
    'migrations', 'cache', 'cache_locks', 'failed_jobs',
    'sessions', 'password_reset_tokens', 'personal_access_tokens',
    'passkeys', 'jobs', 'job_batches',
}


class SchemaAnalyzer:
    """Analyzes database schema and identifies key tables."""

    def __init__(self, db_url: str):
        self.db_url = db_url

    async def analyze(self) -> SchemaAnalysis:
        """Analyze database schema and return structured metadata."""
        conn = None
        try:
            conn = psycopg.connect(self.db_url, autocommit=True)

            with conn.cursor() as cur:
                cur.execute(POSTGRES_SCHEMA_QUERY)
                rows = cur.fetchall()

            # Group columns by table
            tables_map: dict[str, TableInfo] = {}
            for row in rows:
                schema, table, col_name, col_type, nullable, is_pk, fk_table, fk_col = row

                key = f"{schema}.{table}"
                if key not in tables_map:
                    tables_map[key] = TableInfo(
                        name=table,
                        schema=schema,
                    )

                tables_map[key].columns.append(
                    ColumnInfo(
                        name=col_name,
                        data_type=col_type,
                        is_nullable=nullable == 'YES',
                        is_primary_key=is_pk,
                        is_foreign_key=fk_table is not None,
                        foreign_table=fk_table,
                        foreign_column=fk_col,
                    )
                )

            tables = list(tables_map.values())

            # Count rows so LLM does not choose empty tables.
            self._populate_row_counts(conn, tables)

            # Identify key tables using heuristics.
            # Keep only a small top list. Otherwise LLM gets too much context
            # and starts producing weak or non-JSON answers.
            key_tables = self._identify_key_tables(tables, top_n=10)

            # Build relationships
            relationships = self._extract_relationships(tables)

            return SchemaAnalysis(
                tables=tables,
                key_tables=key_tables,
                relationships=relationships,
            )

        except Exception as e:
            logger.error(f"Schema analysis failed: {e}")
            raise
        finally:
            if conn:
                conn.close()

    def _populate_row_counts(self, conn, tables: list[TableInfo]) -> None:
        """Add approximate row counts to tables."""
        with conn.cursor() as cur:
            for table in tables:
                try:
                    cur.execute(
                        "SELECT COUNT(*) FROM "
                        + psycopg.sql.Identifier(table.db_schema, table.name).as_string(conn)
                    )
                    table.row_count = int(cur.fetchone()[0])
                except Exception:
                    table.row_count = 0

    def _identify_key_tables(self, tables: list[TableInfo], top_n: int = 10) -> list[str]:
        """Identify key tables using heuristics and return top N by score."""
        scored: list[tuple[str, int]] = []

        for table in tables:
            if table.name.lower() in SKIP_TABLES:
                continue

            # Empty tables cannot produce useful charts.
            if table.row_count <= 0:
                continue

            score = 0
            fk_cols = [c for c in table.columns if c.is_foreign_key]
            score += len(fk_cols) * 2

            if table.has_timestamp:
                score += 3

            metrics = table.has_metrics
            score += len(metrics) * 2

            entities = table.has_entities
            score += len(entities)

            pk_cols = [c for c in table.columns if c.is_primary_key]
            if pk_cols:
                score += 1

            if score > 0:
                # Big tables with real rows should always come first.
                row_bonus = math.log10(max(table.row_count, 1)) * 5
                scored.append((table.name, int(score + row_bonus)))

        scored.sort(key=lambda item: item[1], reverse=True)
        return [name for name, _score in scored[:top_n]]

    def _extract_relationships(self, tables: list[TableInfo]) -> list[dict[str, Any]]:
        """Extract foreign key relationships between tables."""
        relationships = []
        for table in tables:
            for col in table.columns:
                if col.is_foreign_key and col.foreign_table:
                    relationships.append({
                        "from_table": table.name,
                        "from_column": col.name,
                        "to_table": col.foreign_table,
                        "to_column": col.foreign_column,
                    })
        return relationships


async def analyze_schema(db_url: str) -> SchemaAnalysis:
    """Convenience function to analyze schema."""
    analyzer = SchemaAnalyzer(db_url)
    return await analyzer.analyze()

