"""
SQL Validator — validates SQL queries before execution.
"""

from __future__ import annotations

import asyncio
import logging
import re

import psycopg

logger = logging.getLogger(__name__)

_FORBIDDEN_SQL = re.compile(
    r"\b(insert|update|delete|drop|alter|truncate|copy|grant|revoke|create)\b",
    re.IGNORECASE,
)


def read_only_sql_error(sql: str) -> str | None:
    """Reject any non-SELECT SQL before it reaches PostgreSQL or DataLens."""
    statement = (sql or "").strip().rstrip(";").strip()
    if not statement:
        return "SQL is empty."
    if ";" in statement:
        return "Only one SQL statement is allowed."
    if not re.match(r"^(select|with)\b", statement, re.IGNORECASE):
        return "Only SELECT queries are allowed."
    if _FORBIDDEN_SQL.search(statement):
        return "SQL contains a forbidden write or DDL operation."
    return None


def _validate_syntax_sync(db_url: str, sql: str) -> tuple[bool, str | None]:
    """Synchronous EXPLAIN check — runs in a thread pool via asyncio.to_thread."""
    conn = None
    try:
        conn = psycopg.connect(db_url, autocommit=True)
        with conn.cursor() as cur:
            cur.execute(f"EXPLAIN {sql}")
        return True, None
    except psycopg.errors.SyntaxError as exc:
        logger.warning("SQL syntax error: %s", exc)
        return False, f"Syntax error: {exc}"
    except psycopg.errors.UndefinedTable as exc:
        logger.warning("Table not found: %s", exc)
        return False, f"Table not found: {exc}"
    except psycopg.errors.UndefinedColumn as exc:
        logger.warning("Column not found: %s", exc)
        return False, f"Column not found: {exc}"
    except Exception as exc:
        logger.warning("Validation error: %s", exc)
        return False, f"Validation error: {exc}"
    finally:
        if conn:
            conn.close()


def _dry_run_sync(db_url: str, sql: str, limit: int) -> tuple[bool, list | None, str | None]:
    """Synchronous LIMIT-5 execution — runs in a thread pool via asyncio.to_thread."""
    conn = None
    try:
        safe_sql = sql if "limit" in sql.lower() else f"{sql.rstrip(';')} LIMIT {limit}"
        conn = psycopg.connect(db_url, autocommit=True)
        with conn.cursor() as cur:
            cur.execute(safe_sql)
            columns = [desc[0] for desc in cur.description] if cur.description else []
            rows = cur.fetchall()
        return True, [dict(zip(columns, row)) for row in rows], None
    except Exception as exc:
        logger.warning("Dry run failed: %s", exc)
        return False, None, str(exc)
    finally:
        if conn:
            conn.close()


def _count_rows_sync(db_url: str, sql: str) -> tuple[int | None, str | None]:
    """Synchronous row count — runs in a thread pool via asyncio.to_thread."""
    conn = None
    try:
        conn = psycopg.connect(db_url, autocommit=True)
        with conn.cursor() as cur:
            cur.execute(f"SELECT COUNT(*) FROM ({sql.rstrip(';')}) AS ai_chart_result")
            return int(cur.fetchone()[0]), None
    except Exception as exc:
        logger.warning("Result cardinality check failed: %s", exc)
        return None, str(exc)
    finally:
        if conn:
            conn.close()


class SQLValidator:
    """Validates SQL syntax and safety before execution."""

    def __init__(self, db_url: str):
        self.db_url = db_url

    async def validate_syntax(self, sql: str) -> tuple[bool, str | None]:
        """Validate SQL syntax using EXPLAIN (non-blocking)."""
        return await asyncio.to_thread(_validate_syntax_sync, self.db_url, sql)

    async def dry_run(self, sql: str, limit: int = 5) -> tuple[bool, list | None, str | None]:
        """Execute SQL with LIMIT to verify it works and return sample data (non-blocking)."""
        return await asyncio.to_thread(_dry_run_sync, self.db_url, sql, limit)

    async def validate_and_execute(
        self, sql: str
    ) -> tuple[bool, list[dict] | None, str | None]:
        """Full validation: policy check → syntax check → dry run."""
        policy_error = read_only_sql_error(sql)
        if policy_error:
            return False, None, policy_error

        valid, error = await self.validate_syntax(sql)
        if not valid:
            return False, None, error

        success, data, error = await self.dry_run(sql, limit=5)
        if not success:
            return False, None, error

        return True, data, None


async def count_query_rows(db_url: str, sql: str) -> tuple[int | None, str | None]:
    """Count chart result rows without blocking the event loop."""
    return await asyncio.to_thread(_count_rows_sync, db_url, sql)


async def validate_sql(
    db_url: str, sql: str, limit: int = 5
) -> tuple[bool, list[dict] | None, str | None]:
    """Convenience function for SQL validation."""
    validator = SQLValidator(db_url)
    policy_error = read_only_sql_error(sql)
    if policy_error:
        return False, None, policy_error
    valid, error = await validator.validate_syntax(sql)
    if not valid:
        return False, None, error
    success, data, error = await validator.dry_run(sql, limit=limit)
    if not success:
        return False, None, error
    return True, data, None
