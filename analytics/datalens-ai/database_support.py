"""Database-type capability checks for the AI analysis pipeline.

DataLens connection creation and Python schema/SQL analysis are separate
concerns. DataLens can support more databases than the current AI pipeline.
This module prevents creating a partially working dashboard when a configured
connection type has no analyzer/validator implementation yet.
"""

from __future__ import annotations

from urllib.parse import urlparse

from config import Settings

POSTGRES_TYPES = {"postgres", "postgresql"}
POSTGRES_URL_SCHEMES = {"postgres", "postgresql", "postgresql+psycopg"}


def normalized_connection_type(value: str) -> str:
    """Normalize common DataLens connection type aliases."""
    normalized = value.strip().lower()
    return "postgres" if normalized == "postgresql" else normalized


def ensure_ai_database_supported(settings: Settings, db_url: str) -> str:
    """Return the normalized type or explain why the AI pipeline cannot use it.

    The DataLens API may accept other connection types, but schema_analyzer and
    sql_validator currently use psycopg and therefore need PostgreSQL.
    """
    connection_type = normalized_connection_type(settings.datalens_db_type)
    if connection_type not in POSTGRES_TYPES:
        raise RuntimeError(
            "AI-анализ для типа базы "
            f"'{settings.datalens_db_type}' пока не реализован. "
            "Сейчас datalens-ai поддерживает PostgreSQL (DATALENS_DB_TYPE=postgres)."
        )

    scheme = urlparse(db_url).scheme.lower()
    if scheme not in POSTGRES_URL_SCHEMES:
        raise RuntimeError(
            "DB_URL должен указывать на PostgreSQL для текущего AI-пайплайна. "
            f"Получена схема URL: '{scheme or 'не указана'}'."
        )

    return connection_type
