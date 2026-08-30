"""DataLens AI Service — FastAPI application."""

from __future__ import annotations

import asyncio
import json
import logging
import uuid
from inspect import signature
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Any, Literal

from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, StreamingResponse
import httpx
from pydantic import BaseModel, Field

from ai_editor import edit_dashboard
from config import get_settings
from dashboard_service import create_ai_dashboard
from datalens_client import DataLensClient, DataLensError
from entity_resolver import EntityDataUnavailableError, EntityNotFoundError
from llm_client import ChatMessage, get_llm_client
from schema_analyzer import analyze_schema
from sql_validator import validate_sql

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
)
logger = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Job registry — persisted to disk so jobs survive uvicorn restarts.
# ---------------------------------------------------------------------------

DASHBOARD_JOBS: dict[str, dict[str, Any]] = {}
_JOBS_FILE = Path(__file__).parent / "jobs.json"


def _load_jobs() -> None:
    """Load jobs from disk on startup. Mark any unfinished jobs as failed."""
    if not _JOBS_FILE.exists():
        return
    try:
        saved: dict[str, dict[str, Any]] = json.loads(_JOBS_FILE.read_text(encoding="utf-8"))
        for job in saved.values():
            if job.get("status") in ("queued", "running"):
                job["status"] = "failed"
                job["stage"] = "failed"
                job["error"] = "Service was restarted while the job was running."
                job["message"] = "Задача прервана перезапуском сервиса."
        DASHBOARD_JOBS.update(saved)
        logger.info("Loaded %d jobs from %s", len(saved), _JOBS_FILE)
    except Exception as exc:
        logger.warning("Could not load jobs from disk: %s", exc)


def _save_jobs() -> None:
    """Dump current jobs to disk on shutdown."""
    try:
        _JOBS_FILE.write_text(
            json.dumps(DASHBOARD_JOBS, ensure_ascii=False, default=str, indent=2),
            encoding="utf-8",
        )
        logger.info("Saved %d jobs to %s", len(DASHBOARD_JOBS), _JOBS_FILE)
    except Exception as exc:
        logger.warning("Could not save jobs to disk: %s", exc)


class GenerateDashboardRequest(BaseModel):
    message: str = Field(default="Визуализируй эту БД")
    db_url: str | None = Field(default=None)
    chart_count: int | None = Field(default=None, ge=1, le=8)
    workbook_id: str | None = None


class EditDashboardRequest(BaseModel):
    message: str
    db_url: str | None = None
    connection_id: str | None = None


class ValidateSQLRequest(BaseModel):
    db_url: str
    sql: str


class DashboardJobRequest(BaseModel):
    operation: Literal["generate", "edit"] = "generate"
    message: str = Field(min_length=5, max_length=2000)
    dashboard_id: str | None = None
    db_url: str | None = None
    workbook_id: str | None = None
    connection_id: str | None = None
    chart_count: int | None = Field(default=None, ge=1, le=8)


class AgentMessageRequest(BaseModel):
    message: str = Field(min_length=1, max_length=2000)
    dashboard_context: str | None = Field(default=None, max_length=4000)
    has_dashboard: bool = False


class AgentToolDecision(BaseModel):
    tool: Literal["chat", "generate_dashboard", "edit_dashboard", "inspect_dashboard", "clear_dashboard", "replace_dashboard"]
    reply: str = Field(min_length=1, max_length=2000)


class ChartSummary(BaseModel):
    id: str
    title: str
    kind: str
    section: str
    sql: str | None = None


class DashboardSummary(BaseModel):
    dashboard_id: str
    dashboard_url: str
    embed_url: str
    workbook_id: str
    connection_id: str
    charts: list[ChartSummary]
    title: str | None = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.info("Starting DataLens AI Service...")
    # Fail at startup rather than accepting jobs with a partially copied source
    # tree (for example new main.py paired with an old dashboard_service.py).
    if "chart_count" not in signature(create_ai_dashboard).parameters:
        raise RuntimeError(
            "Incompatible datalens-ai files: dashboard_service.py is missing "
            "the chart_count parameter. Deploy the matching source files."
        )

    settings = get_settings()
    if not settings.openai_api_key:
        raise RuntimeError("OPENAI_API_KEY is required in the local datalens-ai .env file.")

    _load_jobs()

    client = get_llm_client()
    llm_ok = await client.health_check()
    logger.info("%s OpenAI model %s", "connected to" if llm_ok else "cannot reach", settings.openai_model)
    yield
    _save_jobs()
    await client.close()
    logger.info("DataLens AI Service stopped")


app = FastAPI(
    title="DataLens AI Service",
    description="AI agent for automated dashboard creation and editing in DataLens.",
    version="0.2.0",
    lifespan=lifespan,
)

settings = get_settings()
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origin_list,
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["Content-Type", "X-Request-ID", "X-API-Key"],
)


_OPEN_PATHS = {"/health", "/", "/docs", "/openapi.json", "/redoc"}


@app.middleware("http")
async def verify_api_key(request: Request, call_next):
    """Reject requests without a valid X-API-Key header (when key is configured)."""
    s = get_settings()
    if s.service_api_key and request.url.path not in _OPEN_PATHS:
        provided = request.headers.get("X-API-Key", "")
        if provided != s.service_api_key:
            return JSONResponse(
                status_code=403,
                content={"detail": "Invalid or missing API key."},
            )
    return await call_next(request)


@app.middleware("http")
async def request_context(request: Request, call_next):
    """Attach a traceable request ID without exposing internal exception data."""
    request_id = request.headers.get("X-Request-ID") or uuid.uuid4().hex
    try:
        response = await call_next(request)
    except Exception:
        logger.exception("request_id=%s method=%s path=%s unhandled error", request_id, request.method, request.url.path)
        return JSONResponse(
            status_code=500,
            content={"detail": "Internal service error.", "request_id": request_id},
            headers={"X-Request-ID": request_id},
        )

    response.headers["X-Request-ID"] = request_id
    logger.info(
        "request_id=%s method=%s path=%s status=%s",
        request_id,
        request.method,
        request.url.path,
        response.status_code,
    )
    return response


def _safe_error_detail(exception: Exception) -> tuple[int, str]:
    """Map upstream failures to stable public errors; details remain in logs."""
    if "ENTRY_IS_LOCKED" in str(exception):
        return 409, "ENTRY_IS_LOCKED"
    if isinstance(exception, EntityNotFoundError):
        return 404, "ENTITY_NOT_FOUND"
    if isinstance(exception, EntityDataUnavailableError):
        return 422, "ENTITY_NO_DATA"
    if isinstance(exception, (DataLensError, httpx.HTTPError)):
        return 502, "DataLens service is unavailable or rejected the request."
    if isinstance(exception, TimeoutError):
        return 504, "External service request timed out."
    return 500, "Internal service error."


def _public_job(job: dict[str, Any]) -> dict[str, Any]:
    return {
        "job_id": job["job_id"],
        "operation": job["operation"],
        "status": job["status"],
        "stage": job["stage"],
        "message": job["message"],
        "step": job["step"],
        "error": job.get("error"),
        "result": job.get("result") if job["status"] == "completed" else None,
    }


def _report_progress(job_id: str, stage: str, message: str, step: int) -> None:
    job = DASHBOARD_JOBS.get(job_id)
    if not job:
        return
    job.update({
        "status": "running",
        "stage": stage,
        "message": message,
        "step": step,
    })
    logger.info("job=%s step=%s stage=%s message=%s", job_id, step, stage, message)


async def _run_dashboard_job(job_id: str, request: DashboardJobRequest) -> None:
    job = DASHBOARD_JOBS[job_id]
    settings = get_settings()
    db_url = request.db_url or settings.db_url

    try:
        if request.operation == "generate":
            result = await create_ai_dashboard(
                db_url=db_url,
                message=request.message,
                workbook_id=request.workbook_id,
                chart_count=request.chart_count,
                progress_callback=lambda stage, message, step: _report_progress(job_id, stage, message, step),
            )
        else:
            if not request.dashboard_id:
                raise RuntimeError("dashboard_id is required for an edit job.")
            if not request.connection_id:
                raise RuntimeError("connection_id is required for an edit job.")
            result = await edit_dashboard(
                dashboard_id=request.dashboard_id,
                instruction=request.message,
                db_url=db_url,
                connection_id=request.connection_id,
                progress_callback=lambda stage, message, step: _report_progress(job_id, stage, message, step),
            )

        job.update({
            "status": "completed",
            "stage": "completed",
            "message": "Dashboard готов.",
            "step": 9,
            "result": result,
        })
    except Exception as exc:
        status_code, detail = _safe_error_detail(exc)
        logger.exception("job=%s failed status=%s detail=%s", job_id, status_code, detail)
        job.update({
            "status": "failed",
            "stage": "failed",
            "message": "Не удалось завершить задачу.",
            "error": detail,
        })


@app.get("/health")
async def health() -> dict[str, Any]:
    llm = get_llm_client()
    llm_ok = await llm.health_check()
    settings = get_settings()

    datalens_ok = False
    try:
        with DataLensClient(settings) as client:
            client.login()
            datalens_ok = True
    except Exception as exc:
        logger.warning("DataLens health check failed: %s", exc)

    return {"status": "ok", "llm_server": llm_ok, "datalens": datalens_ok}


@app.get("/")
async def root() -> dict[str, str]:
    return {"service": "DataLens AI", "version": "0.2.0", "docs": "/docs", "health": "/health"}


@app.post("/api/agent/respond")
async def agent_respond(request: AgentMessageRequest) -> dict[str, str]:
    """Route a chat turn either to a conversational reply or a dashboard tool."""
    system = ChatMessage(
        role="system",
        content=(
            "Ты AI-агент аналитической LMS. Верни только JSON: "
            "{\"tool\":\"chat|generate_dashboard|edit_dashboard|inspect_dashboard|clear_dashboard|replace_dashboard\",\"reply\":\"...\"}. "
            "tool=chat для приветствий, объяснений и обычного разговора. "
            "tool=inspect_dashboard когда пользователь просит объяснить текущие charts, метрики или структуру dashboard без изменений. "
            "tool=clear_dashboard когда пользователь явно просит только очистить текущий dashboard. "
            "tool=replace_dashboard когда пользователь просит очистить/заменить dashboard и затем построить новый анализ в одном сообщении. "
            "Очистка и замена удаляют только виджеты dashboard, но не DataLens charts и не данные БД. "
            "tool=generate_dashboard только когда текущего dashboard нет и пользователь "
            "просит визуализировать, построить или проанализировать данные. "
            "tool=edit_dashboard только когда dashboard уже есть и пользователь просит "
            "создать, добавить, удалить, изменить, перестроить или проанализировать визуализации. "
            "Для chat ответь кратко и дружелюбно по-русски. Для tool вызова кратко "
            "подтверди, что начинаешь работу. Поддерживаемые визуализации: "
            "line, area, column, bar, pie, table (с пагинацией). "
            "Не заявляй поддержку KPI, funnel, radar, heatmap, pivot, selectors или dataset charts."
        ),
    )
    context = request.dashboard_context or ""
    user = ChatMessage(
        role="user",
        content=(
            f"Есть текущий dashboard: {'да' if request.has_dashboard else 'нет'}\n"
            f"Контекст dashboard:\n{context}\n\nСообщение: {request.message}"
        ),
    )
    try:
        payload = await get_llm_client().chat_json(
            [system, user],
            schema_name="agent_tool_decision",
            schema={
                "type": "object",
                "additionalProperties": False,
                "properties": {
                    "tool": {"type": "string", "enum": ["chat", "generate_dashboard", "edit_dashboard", "inspect_dashboard", "clear_dashboard", "replace_dashboard"]},
                    "reply": {"type": "string", "minLength": 1, "maxLength": 2000},
                },
                "required": ["tool", "reply"],
            },
            max_tokens=512,
        )
    except Exception:
        # A simple greeting must not fail because an API response is unavailable.
        return {"tool": "chat", "reply": "Я на связи. Чем помочь с dashboard или данными LMS?"}

    try:
        decision = AgentToolDecision.model_validate(payload)
    except Exception:
        return {"tool": "chat", "reply": "Я на связи. Чем помочь с dashboard или данными LMS?"}
    return decision.model_dump()


@app.post("/api/dashboards/generate", response_model=DashboardSummary)
async def generate_dashboard(request: GenerateDashboardRequest) -> DashboardSummary:
    settings = get_settings()
    db_url = request.db_url or settings.db_url

    try:
        result = await create_ai_dashboard(
            db_url=db_url,
            message=request.message,
            workbook_id=request.workbook_id,
            chart_count=request.chart_count,
        )
    except Exception as exc:
        status_code, detail = _safe_error_detail(exc)
        logger.exception("Dashboard generation failed status=%s detail=%s", status_code, detail)
        raise HTTPException(status_code=status_code, detail=detail)

    return DashboardSummary(
        dashboard_id=result["dashboard_id"],
        dashboard_url=result["dashboard_url"],
        embed_url=result["embed_url"],
        workbook_id=result["workbook_id"],
        connection_id=result["connection_id"],
        title=result.get("title"),
        charts=[
            ChartSummary(
                id=chart["id"],
                title=chart["title"],
                kind=chart["kind"],
                section=chart.get("section", "Overview"),
                sql=chart.get("sql"),
            )
            for chart in result["charts"]
        ],
    )


@app.post("/api/dashboard-jobs")
async def create_dashboard_job(request: DashboardJobRequest) -> dict[str, Any]:
    if request.operation == "edit" and not request.dashboard_id:
        raise HTTPException(status_code=400, detail="dashboard_id is required for edit jobs.")

    job_id = uuid.uuid4().hex
    DASHBOARD_JOBS[job_id] = {
        "job_id": job_id,
        "operation": request.operation,
        "status": "queued",
        "stage": "queued",
        "message": "Задача поставлена в очередь…",
        "step": 0,
        "error": None,
        "result": None,
    }
    asyncio.create_task(_run_dashboard_job(job_id, request))
    return _public_job(DASHBOARD_JOBS[job_id])


@app.get("/api/dashboard-jobs/{job_id}")
async def get_dashboard_job(job_id: str) -> dict[str, Any]:
    job = DASHBOARD_JOBS.get(job_id)
    if not job:
        raise HTTPException(status_code=404, detail="Dashboard job not found.")
    return _public_job(job)


@app.get("/api/dashboard-jobs/{job_id}/events")
async def stream_dashboard_job(job_id: str) -> StreamingResponse:
    """Push live job progress to the admin overlay using Server-Sent Events."""
    if job_id not in DASHBOARD_JOBS:
        raise HTTPException(status_code=404, detail="Dashboard job not found.")

    async def events():
        previous: str | None = None
        while True:
            job = DASHBOARD_JOBS.get(job_id)
            if not job:
                payload = {"status": "failed", "error": "Dashboard job not found."}
                yield f"event: failed\ndata: {json.dumps(payload, ensure_ascii=False)}\n\n"
                return

            payload = _public_job(job)
            fingerprint = json.dumps(payload, ensure_ascii=False, sort_keys=True)
            if fingerprint != previous:
                yield f"event: progress\ndata: {fingerprint}\n\n"
                previous = fingerprint

            if payload["status"] == "completed":
                yield f"event: complete\ndata: {fingerprint}\n\n"
                return
            if payload["status"] == "failed":
                yield f"event: failed\ndata: {fingerprint}\n\n"
                return

            await asyncio.sleep(0.5)

    return StreamingResponse(
        events(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
        },
    )


@app.get("/api/dashboards/{dashboard_id}")
async def get_dashboard(dashboard_id: str) -> dict[str, Any]:
    settings = get_settings()
    try:
        with DataLensClient(settings) as client:
            client.login()
            return client.get_dashboard(dashboard_id)
    except Exception as exc:
        status_code, detail = _safe_error_detail(exc)
        logger.exception("Failed to fetch dashboard %s status=%s", dashboard_id, status_code)
        raise HTTPException(status_code=status_code, detail=detail)


@app.post("/api/dashboards/{dashboard_id}/edit", response_model=dict)
async def edit_dashboard_endpoint(dashboard_id: str, request: EditDashboardRequest) -> dict[str, Any]:
    settings = get_settings()
    db_url = request.db_url or settings.db_url
    connection_id = request.connection_id

    if not connection_id:
        try:
            with DataLensClient(settings) as client:
                client.login()
                dashboard = client.get_dashboard(dashboard_id)
                refs = [
                    chart_tab["chartId"]
                    for tab in dashboard["entry"]["data"].get("tabs", [])
                    for item in tab.get("items", [])
                    if item.get("type") == "widget"
                    for chart_tab in item.get("data", {}).get("tabs", [])
                    if chart_tab.get("chartId")
                ]
                if refs:
                    chart = client.get_ql_chart(refs[0])
                    connection_id = (chart.get("_shared") or {}).get("connection", {}).get("entryId")
        except Exception as exc:
            logger.warning("Could not auto-detect connection_id: %s", exc)

    if not connection_id:
        raise HTTPException(status_code=400, detail="connection_id is required and could not be auto-detected.")

    try:
        return await edit_dashboard(
            dashboard_id=dashboard_id,
            instruction=request.message,
            db_url=db_url,
            connection_id=connection_id,
        )
    except Exception as exc:
        status_code, detail = _safe_error_detail(exc)
        logger.exception("Dashboard edit failed status=%s detail=%s", status_code, detail)
        raise HTTPException(status_code=status_code, detail=detail)


@app.get("/api/charts/{chart_id}")
async def get_chart(chart_id: str) -> dict[str, Any]:
    settings = get_settings()
    try:
        with DataLensClient(settings) as client:
            client.login()
            return client.get_ql_chart(chart_id)
    except Exception as exc:
        status_code, detail = _safe_error_detail(exc)
        logger.exception("Failed to fetch chart %s status=%s", chart_id, status_code)
        raise HTTPException(status_code=status_code, detail=detail)


@app.post("/api/validate-sql")
async def validate_sql_endpoint(request: ValidateSQLRequest) -> dict[str, Any]:
    ok, sample, error = await validate_sql(request.db_url, request.sql)
    return {"valid": ok, "sample_data": sample, "error": error}


@app.post("/api/analyze")
async def analyze_db(db_url: str) -> dict[str, Any]:
    schema = await analyze_schema(db_url)
    return {
        "tables_count": len(schema.tables),
        "key_tables": schema.key_tables,
        "tables": [table.model_dump(mode="json") for table in schema.tables],
        "relationships": schema.relationships,
    }


if __name__ == "__main__":
    import uvicorn

    uvicorn.run("main:app", host="0.0.0.0", port=8100, reload=False)
