"""Resolve user/project mentions to real database entities before AI planning."""

from __future__ import annotations

import asyncio
import re
from dataclasses import dataclass
from typing import Any

import psycopg
from psycopg import sql

EMAIL_RE = re.compile(r"\b[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}\b")
PROJECT_RE = re.compile(
    r"(?:проект(?:а|у|е|ом|ы|ов)?|project)\s+[«\"']?([A-Za-zА-Яа-яЁё0-9][\w.-]*)",
    re.IGNORECASE,
)
PROJECT_STOP_WORDS = {"по", "для", "у", "на", "в", "с", "из", "и", "пользователя", "работ"}


class EntityNotFoundError(RuntimeError):
    """A requested concrete entity does not exist in the analytics database."""


class EntityDataUnavailableError(RuntimeError):
    """The entity exists but the requested analytics slice has no rows."""


@dataclass(frozen=True)
class ResolvedEntity:
    kind: str
    table: str
    values: dict[str, Any]


def _tables_with_column(schema, column_name: str):
    """Return matching tables, prioritising the real users table."""
    matches = [table for table in schema.tables if any(c.name == column_name for c in table.columns)]
    return sorted(matches, key=lambda table: (table.name.lower() != "users", table.name.lower()))


def _project_table(schema):
    return next((table for table in schema.tables if table.name.lower() == "projects"), None)


def _columns(table, preferred: list[str]) -> list[str]:
    available = {column.name for column in table.columns}
    return [name for name in preferred if name in available]


def _fetch_one(conn, table, columns: list[str], where: sql.Composed, params: tuple[Any, ...]) -> dict[str, Any] | None:
    if not columns:
        return None
    query = sql.SQL("SELECT {fields} FROM {table} WHERE ").format(
        fields=sql.SQL(", ").join(sql.Identifier(column) for column in columns),
        table=sql.Identifier(table.db_schema, table.name),
    ) + where + sql.SQL(" LIMIT 2")
    with conn.cursor() as cursor:
        cursor.execute(query, params)
        rows = cursor.fetchall()
        if len(rows) != 1:
            return None
        return dict(zip(columns, rows[0]))


def _resolve_entities_sync(*, db_url: str, schema, message: str) -> list[ResolvedEntity]:
    """Resolve explicit emails and project names with parameterized SQL only."""
    emails = sorted(set(EMAIL_RE.findall(message)))
    project_matches = [
        match.strip()
        for match in PROJECT_RE.findall(message)
        if match.strip() and match.strip().casefold() not in PROJECT_STOP_WORDS
    ]
    if not emails and not project_matches:
        return []

    resolved: list[ResolvedEntity] = []
    with psycopg.connect(db_url, autocommit=True) as conn:
        if emails:
            user_tables = _tables_with_column(schema, "email")
            if not user_tables:
                raise EntityNotFoundError("В аналитической схеме нет таблицы пользователей с полем email.")
            for email in emails:
                found = None
                source_table = None
                for users in user_tables:
                    columns = _columns(users, ["id", "email", "username", "name", "level"])
                    found = _fetch_one(
                        conn,
                        users,
                        columns,
                        sql.SQL("LOWER({}) = LOWER(%s)").format(sql.Identifier("email")),
                        (email,),
                    )
                    if found:
                        source_table = users
                        break
                if not found or source_table is None:
                    raise EntityNotFoundError(f"Пользователь с email {email} не найден в аналитической БД.")
                resolved.append(ResolvedEntity("user", source_table.name, found))

        if project_matches:
            projects = _project_table(schema)
            if not projects:
                raise EntityNotFoundError("В аналитической схеме нет таблицы projects.")
            title_column = next((name for name in ("title", "name", "slug") if name in {c.name for c in projects.columns}), None)
            if not title_column:
                raise EntityNotFoundError("В таблице projects нет поля title, name или slug для поиска проекта.")
            columns = _columns(projects, ["id", title_column, "user_id", "status", "created_at"])
            for project_name in project_matches:
                found = _fetch_one(
                    conn,
                    projects,
                    columns,
                    sql.SQL("LOWER({}) = LOWER(%s)").format(sql.Identifier(title_column)),
                    (project_name,),
                )
                if not found:
                    raise EntityNotFoundError(f"Проект {project_name} не найден в аналитической БД.")
                resolved.append(ResolvedEntity("project", projects.name, found))

    return resolved


def resolve_entities(*, db_url: str, schema, message: str) -> list[ResolvedEntity]:
    """Synchronous wrapper kept for callers that run outside async context (tests, scripts)."""
    return _resolve_entities_sync(db_url=db_url, schema=schema, message=message)


async def resolve_entities_async(*, db_url: str, schema, message: str) -> list[ResolvedEntity]:
    """Non-blocking entity resolution — runs in a thread pool via asyncio.to_thread."""
    return await asyncio.to_thread(
        _resolve_entities_sync, db_url=db_url, schema=schema, message=message
    )


def entity_context(entities: list[ResolvedEntity]) -> str:
    if not entities:
        return "Нет явного пользователя или проекта: выбери общую аналитику."
    lines = ["Пользователь запросил точечный анализ. Используй только следующие подтверждённые сущности и их реальные ID:"]
    for entity in entities:
        values = ", ".join(f"{key}={value}" for key, value in entity.values.items())
        lines.append(f"- {entity.kind} из таблицы {entity.table}: {values}")
    lines.append("Не заменяй подтверждённые значения похожими email/названиями и не строь общую аналитику вместо точечной.")
    return "\n".join(lines)
