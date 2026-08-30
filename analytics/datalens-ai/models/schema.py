"""
Pydantic models for datalens-ai.

Only models actively used by the current pipeline are kept here.
Legacy Day-1 models (DatabaseType, ChatRequest, CreateDashboardRequest, etc.)
have been removed — they were never called from main.py or dashboard_service.py.
"""

from __future__ import annotations

from enum import Enum
from typing import Any

from pydantic import BaseModel, Field


# ============================================================
# Database & Schema
# ============================================================


class ColumnInfo(BaseModel):
    """Metadata for a single database column."""
    name: str
    data_type: str
    is_nullable: bool = True
    is_primary_key: bool = False
    is_foreign_key: bool = False
    foreign_table: str | None = None
    foreign_column: str | None = None


class TableInfo(BaseModel):
    """Metadata for a single database table."""
    name: str
    db_schema: str = Field(default="public", alias="schema")
    row_count: int = 0
    columns: list[ColumnInfo] = Field(default_factory=list)
    # 2 sample rows fetched during schema analysis (sensitive columns excluded).
    # Used to show LLM real data values so it can write accurate SQL.
    sample_rows: list[dict[str, Any]] = Field(default_factory=list)

    @property
    def has_timestamp(self) -> bool:
        ts_types = {"timestamp", "timestamptz", "date", "datetime"}
        return any(c.data_type.lower() in ts_types for c in self.columns)

    @property
    def has_metrics(self) -> list[str]:
        metric_keywords = {"amount", "score", "count", "total", "sum", "avg", "value", "price", "quantity"}
        return [c.name for c in self.columns if any(k in c.name.lower() for k in metric_keywords)]

    @property
    def has_entities(self) -> list[str]:
        entity_keywords = {"name", "title", "username", "email", "label", "description"}
        return [c.name for c in self.columns if any(k in c.name.lower() for k in entity_keywords)]


class SchemaAnalysis(BaseModel):
    """Result of schema analysis."""
    tables: list[TableInfo] = Field(default_factory=list)
    key_tables: list[str] = Field(default_factory=list)
    relationships: list[dict[str, Any]] = Field(default_factory=list)


# ============================================================
# Chart & Dashboard Planning
# ============================================================


class ChartType(str, Enum):
    LINE = "line"
    AREA = "area"
    COLUMN = "column"
    BAR = "bar"
    PIE = "pie"
    TABLE = "table"
    METRIC = "metric"


class SQLQuery(BaseModel):
    """Generated SQL query with metadata."""
    sql: str
    chart_type: ChartType
    table: str
    title: str
    x_field: str | None = None
    y_field: str | None = None
    business_question: str | None = None


class ChartPlan(BaseModel):
    """Plan for a single chart in a dashboard."""
    title: str
    chart_type: ChartType
    table: str
    sql: str
    x_field: str | None = None
    y_field: str | None = None
    business_question: str | None = None


class DashboardPlan(BaseModel):
    """Complete dashboard plan."""
    dashboard_title: str
    charts: list[ChartPlan] = Field(default_factory=list)
    layout: dict[str, Any] | None = None
