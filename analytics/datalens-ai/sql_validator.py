"""
SQL Validator — validates SQL queries before execution.
"""

from __future__ import annotations

import logging
import re

import psycopg

logger = logging.getLogger(__name__)

_FORBIDDEN_SQL = re.compile(r"\b(insert|update|delete|drop|alter|truncate|copy|grant|revoke|create)\b", re.IGNORECASE)


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


class SQLValidator:
    """Validates SQL syntax and safety before execution."""

    def __init__(self, db_url: str):
        self.db_url = db_url

    async def validate_syntax(self, sql: str) -> tuple[bool, str | None]:
        """Validate SQL syntax using EXPLAIN."""
        conn = None
        try:
            conn = psycopg.connect(self.db_url, autocommit=True)
            with conn.cursor() as cur:
                explain_sql = f"EXPLAIN {sql}"
                cur.execute(explain_sql)
            return True, None
        except psycopg.errors.SyntaxError as e:
            logger.warning(f"SQL syntax error: {e}")
            return False, f"Syntax error: {str(e)}"
        except psycopg.errors.UndefinedTable as e:
            logger.warning(f"Table not found: {e}")
            return False, f"Table not found: {str(e)}"
        except psycopg.errors.UndefinedColumn as e:
            logger.warning(f"Column not found: {e}")
            return False, f"Column not found: {str(e)}"
        except Exception as e:
            logger.warning(f"Validation error: {e}")
            return False, f"Validation error: {str(e)}"
        finally:
            if conn:
                conn.close()

    async def dry_run(self, sql: str, limit: int = 1) -> tuple[bool, list | None, str | None]:
        """Execute SQL with LIMIT to verify it works and return sample data."""
        conn = None
        try:
            # Add LIMIT if not present
            safe_sql = sql
            if "limit" not in sql.lower():
                safe_sql = f"{sql.rstrip(';')} LIMIT {limit}"

            conn = psycopg.connect(self.db_url, autocommit=True)
            with conn.cursor() as cur:
                cur.execute(safe_sql)
                columns = [desc[0] for desc in cur.description] if cur.description else []
                rows = cur.fetchall()

            return True, [dict(zip(columns, row)) for row in rows], None

        except Exception as e:
            logger.warning(f"Dry run failed: {e}")
            return False, None, str(e)
        finally:
            if conn:
                conn.close()

    async def validate_and_execute(
        self, sql: str
    ) -> tuple[bool, list[dict] | None, str | None]:
        """Full validation: syntax check + dry run."""
        policy_error = read_only_sql_error(sql)
        if policy_error:
            return False, None, policy_error

        # Step 1: Syntax validation
        valid, error = await self.validate_syntax(sql)
        if not valid:
            return False, None, error

        # Step 2: Dry run with LIMIT
        success, data, error = await self.dry_run(sql, limit=5)
        if not success:
            return False, None, error

        return True, data, None


async def count_query_rows(db_url: str, sql: str) -> tuple[int | None, str | None]:
    """Count chart result rows without exposing query values to the AI."""
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


async def validate_sql(db_url: str, sql: str) -> tuple[bool, list[dict] | None, str | None]:
    """Convenience function for SQL validation."""
    validator = SQLValidator(db_url)
    return await validator.validate_and_execute(sql)

