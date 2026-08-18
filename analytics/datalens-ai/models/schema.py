"""
Pydantic models for datalens-ai API.
"""

from __future__ import annotations

from enum import Enum
from typing import Any

from pydantic import BaseModel, Field


# ============================================================
# Database & Schema
# ============================================================


class DatabaseType(str, Enum):
    POSTGRESQL = "postgresql"
    MYSQL = "mysql"
    CLICKHOUSE = "clickhouse"


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
# SQL Generation
# ============================================================


class ChartType(str, Enum):
    LINE = "line"
    AREA = "area"
    COLUMN = "column"
    BAR = "bar"
    PIE = "pie"
    TABLE = "table"
    INDICATOR = "indicator"


class SQLQuery(BaseModel):
    """Generated SQL query with metadata."""
    sql: str
    chart_type: ChartType
    table: str
    title: str
    x_field: str | None = None
    y_field: str | None = None
    business_question: str | None = None


# ============================================================
# Dashboard Planning
# ============================================================


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


# ============================================================
# API Request/Response Models
# ============================================================


class HealthResponse(BaseModel):
    status: str = "ok"
    llm_server: bool = False
    version: str = "0.1.0"


class AnalyzeRequest(BaseModel):
    """Request to analyze database schema."""
    db_url: str = Field(..., description="Database connection URL")


class AnalyzeResponse(BaseModel):
    """Response with schema analysis."""
    tables_count: int
    key_tables: list[str]
    db_schema: SchemaAnalysis = Field(alias="schema")


class GenerateSQLRequest(BaseModel):
    """Request to generate SQL for visualization."""
    db_url: str
    table: str
    goal: str
    chart_type: ChartType | None = None


class GenerateSQLResponse(BaseModel):
    """Response with generated SQL."""
    sql: str
    chart_type: ChartType
    explanation: str | None = None


class CreateDashboardRequest(BaseModel):
    """Request to create dashboard via AI."""
    db_url: str
    datalens_url: str = Field(default="http://localhost:8085")
    connection_id: str | None = None
    message: str = Field(default="Визуализируй эту БД")


class CreateDashboardResponse(BaseModel):
    """Response with created dashboard info."""
    dashboard_id: str | None = None
    dashboard_url: str | None = None
    charts_created: int = 0
    plan: DashboardPlan | None = None
    error: str | None = None


class ChatRequest(BaseModel):
    """Chat message request."""
    message: str
    db_url: str | None = None
    connection_id: str | None = None
    history: list[dict[str, str]] = Field(default_factory=list)


class ChatResponse(BaseModel):
    """Chat response."""
    response: str
    dashboard_id: str | None = None
    charts: list[dict[str, Any]] = Field(default_factory=list)

