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
from fastapi.exceptions import RequestValidationError
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse, StreamingResponse
import httpx
from pydantic import BaseModel, Field

from ai_editor import edit_dashboard
from config import get_settings
from dashboard_service import JobCancelled, create_ai_dashboard
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


class HistoryMessage(BaseModel):
    role: Literal["user", "assistant"]
    content: str = Field(min_length=1, max_length=1500)
    time: str = Field(default="", max_length=32)


class KnownDashboard(BaseModel):
    dashboard_id: str = Field(min_length=1, max_length=64)
    title: str = Field(max_length=200)
    prompt: str = Field(default="", max_length=300)
    charts: list[str] = Field(default_factory=list, max_length=10)


class AgentMessageRequest(BaseModel):
    message: str = Field(min_length=1, max_length=2000)
    dashboard_context: str | None = Field(default=None, max_length=12000)
    has_dashboard: bool = False
    history: list[HistoryMessage] = Field(default_factory=list, max_length=10)
    known_dashboards: list[KnownDashboard] = Field(default_factory=list, max_length=10)


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
    if settings.llm_key_required and not settings.llm_api_key:
        raise RuntimeError(
            f"LLM_API_KEY is required for provider '{settings.llm_provider}' "
            "in the local datalens-ai .env file."
        )

    _load_jobs()

    client = get_llm_client()
    llm_ok = await client.health_check()
    logger.info(
        "%s %s model %s",
        "connected to" if llm_ok else "cannot reach",
        settings.llm_provider,
        settings.llm_model_effective,
    )
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


def _fallback_job_reply(result: dict[str, Any], operation: str) -> str:
    """Deterministic report built from the real outcome; no canned greetings."""
    charts = result.get("charts") or []
    title = result.get("title") or "без названия"
    listing = ", ".join(f"«{chart.get('title', 'график')}»" for chart in charts[:4])
    hidden = len(charts) - min(len(charts), 4)
    if hidden > 0:
        listing += f" и ещё {hidden}"
    if operation == "edit" or "added" in result or "updated" in result:
        changed = len(result.get("added") or []) + len(result.get("updated") or [])
        removed = len(result.get("deleted") or []) + int(result.get("cleared") or 0)
        if not changed and not removed:
            return f"Обновил dashboard «{title}», состав графиков не изменился."
        parts: list[str] = []
        if changed:
            parts.append(f"изменений: {changed}")
        if removed:
            parts.append(f"убрано старых виджетов: {removed}")
        tail = f" Графики теперь: {listing}." if listing else ""
        return f"Обновил dashboard «{title}» ({', '.join(parts)}).{tail}"
    if not charts:
        return "Готово, но построить графики не удалось."
    return f"Построил dashboard «{title}» из {len(charts)} графиков: {listing}."


async def _summarize_job_result(result: dict[str, Any], operation: str) -> str:
    """Short AI-written reply describing what was actually built or changed."""
    fallback = _fallback_job_reply(result, operation)
    charts = result.get("charts") or []
    chart_lines = "\n".join(
        f"- {chart.get('title', 'график')} — тип {chart.get('type', '?')}"
        for chart in charts[:8]
    )
    actions = "; ".join(
        f"{label}: {len(result.get(key) or [])}"
        for key, label in (
            ("added", "добавлено"), ("updated", "изменено"), ("deleted", "удалено"),
        )
        if result.get(key)
    )
    cleared = int(result.get("cleared") or 0)
    if cleared:
        actions = (actions + "; " if actions else "") + f"убрано при замене: {cleared}"
    if not charts and not actions:
        return fallback

    system = ChatMessage(
        role="system",
        content=(
            "Ты AI-агент аналитической LMS. Напиши 2–3 коротких предложения по-русски "
            "о том, что именно ты сделал с dashboard. Перечисли графики строго по списку "
            "ниже: не выдумывай названия, типы и количество карточек, которых нет в "
            "списке. Без markdown, без приветствий и без вопросов."
        ),
    )
    user = ChatMessage(
        role="user",
        content=(
            f"Операция: {operation}.\n"
            f"Dashboard: {str(result.get('title') or 'без названия')[:80]}.\n"
            "ВНИМАНИЕ: название может содержать исходный запрос пользователя — "
            "факты бери ТОЛЬКО из списка графиков и действий ниже.\n"
            + (f"Действия: {actions}.\n" if actions else "")
            + (f"Графики:\n{chart_lines}" if chart_lines else "")
        ),
    )
    try:
        response = await get_llm_client().chat([system, user], max_tokens=1024)
        reply = response.content.strip()
        return reply or fallback
    except Exception as exc:
        logger.warning("job summary LLM call failed: %s", exc)
        return fallback


async def _run_dashboard_job(job_id: str, request: DashboardJobRequest) -> None:
    job = DASHBOARD_JOBS[job_id]
    settings = get_settings()
    db_url = request.db_url or settings.db_url

    def _cancel_check() -> bool:
        return bool(DASHBOARD_JOBS.get(job_id, {}).get("cancel_requested"))

    try:
        if request.operation == "generate":
            result = await create_ai_dashboard(
                db_url=db_url,
                message=request.message,
                workbook_id=request.workbook_id,
                chart_count=request.chart_count,
                progress_callback=lambda stage, message, step: _report_progress(job_id, stage, message, step),
                cancel_check=_cancel_check,
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
                cancel_check=_cancel_check,
            )

        result["reply"] = await _summarize_job_result(result, request.operation)

        job.update({
            "status": "completed",
            "stage": "completed",
            "message": "Dashboard готов.",
            "step": 9,
            "result": result,
        })
    except JobCancelled:
        logger.info("job=%s canceled by user", job_id)
        job.update({
            "status": "failed",
            "stage": "failed",
            "message": "Остановлено пользователем.",
            "step": 0,
            "error": "CANCELED",
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


def _with_datalens(fn):
    """Run fn(fresh logged-in sync client) — designed for asyncio.to_thread."""
    settings = get_settings()
    with DataLensClient(settings) as client:
        client.login()
        return fn(client)


@app.get("/health")
async def health() -> dict[str, Any]:
    llm = get_llm_client()
    llm_ok = await llm.health_check()

    # The DataLens login probe is blocking HTTP — keep it off the event loop.
    try:
        await asyncio.to_thread(_with_datalens, lambda client: True)
        datalens_ok = True
    except Exception as exc:
        logger.warning("DataLens health check failed: %s", exc)
        datalens_ok = False

    return {
        "status": "ok",
        "llm_server": llm_ok,
        "llm_provider": get_settings().llm_provider,
        "datalens": datalens_ok,
    }


@app.get("/")
async def root() -> dict[str, str]:
    return {"service": "DataLens AI", "version": "0.2.0", "docs": "/docs", "health": "/health"}


def _build_agent_messages(request: AgentMessageRequest) -> list[ChatMessage]:
    """System + user prompt for the router, including history and known dashboards."""
    system = ChatMessage(
        role="system",
        content=(
            "Ты AI-агент аналитической LMS. Верни только JSON: "
            "{\"tool\":\"chat|generate_dashboard|edit_dashboard|inspect_dashboard|clear_dashboard|replace_dashboard\",\"reply\":\"...\"}. "
            "tool=chat ТОЛЬКО для приветствий, объяснений и обычного разговора. "
            "tool=inspect_dashboard когда пользователь просит объяснить текущие charts, метрики или структуру dashboard без изменений. "
            "tool=clear_dashboard когда пользователь явно просит только очистить текущий dashboard. "
            "tool=replace_dashboard когда пользователь просит очистить/заменить dashboard и затем построить новый анализ в одном сообщении. "
            "Очистка и замена удаляют только виджеты dashboard, но не DataLens charts и не данные БД. "
            "tool=generate_dashboard только когда текущего dashboard нет и пользователь "
            "просит визуализировать, построить или проанализировать данные. "
            "tool=edit_dashboard только когда dashboard уже есть и пользователь просит "
            "создать, добавить, удалить, изменить, перестроить или проанализировать визуализации. "
            "Запросы вида «Визуализируй БД», «построй/покажи/проанализируй данные» — это "
            "generate_dashboard (когда dashboard нет) или edit_dashboard (когда есть), но никогда не chat. "
            "Запросы-делегирования вида «Визуализируй БД», «построй всё», «на твоё усмотрение», "
            "«сделай красиво/интересное» в контексте аналитики — это немедленный generate_dashboard "
            "(полный анализ БД: агент сам выбирает топ-таблицы по профилю данных, число и вид чартов). "
            "НЕ задавай уточняющих вопросов на такие запросы — это делегирование. "
            "Уточняющие вопросы уместны только когда непонятно, о чём вообще речь "
            "(например, бессвязный текст без запроса на аналитику). "
            "Повторный запрос: известные дашборды — это дашборды ТОЛЬКО текущего чата. "
            "Если сообщение по смыслу повторяет один из них, "
            "НЕ запускай новую генерацию — верни tool=chat: в reply опиши, что в этом дашборде "
            "уже построено (название и графики), предложи переделать и спроси, что именно "
            "не устраивает. "
            "История диалога: короткий ответ пользователя («да», «давай», «переделай», "
            "«построй заново», «заново с нуля», «пересоздай», «не нравится X») относится "
            "к последнему предложению ассистента. Если ты только что предложил переделать "
            "и пользователь отвечает согласием в любой формулировке — выполняй сразу "
            "(replace_dashboard, если есть текущий dashboard; иначе generate_dashboard) "
            "и НЕ задавай уточняющие вопросы повторно; конкретные пожелания — edit_dashboard. "
            "Для chat ответь кратко и дружелюбно по-русски. Для tool вызова кратко "
            "подтверди, что начинаешь работу. Поддерживаемые визуализации: "
            "line, area, column, bar, pie, table (с пагинацией), metric (KPI-карточка). "
            "Приветствие («Привет», «Здравствуйте») допустимо ТОЛЬКО если это первое "
            "сообщение чата или прошло больше суток с последнего сообщения в истории "
            "(время указано в квадратных скобках у каждой записи). В остальных случаях "
            "отвечай сразу по делу, без приветствий и без «Чем помочь». "
            "Не заявляй поддержку funnel, radar, heatmap, pivot, selectors или dataset charts."
        ),
    )

    known_lines = "\n".join(
        f"- «{d.title}» (запрос: {d.prompt or '—'}; графики: {', '.join(d.charts) if d.charts else 'нет'})"
        for d in request.known_dashboards
    )
    history_lines = "\n".join(
        (f"[{m.time}] " if m.time else "")
        + f"{'Пользователь' if m.role == 'user' else 'Ассистент'}: {m.content}"
        for m in request.history
    )
    context = request.dashboard_context or ""
    user = ChatMessage(
        role="user",
        content=(
            (f"Известные дашборды пользователя:\n{known_lines}\n\n" if known_lines else "")
            + (f"История диалога:\n{history_lines}\n\n" if history_lines else "")
            + f"Есть текущий dashboard: {'да' if request.has_dashboard else 'нет'}\n"
            + f"Контекст dashboard:\n{context}\n\nСообщение: {request.message}"
        ),
    )
    return [system, user]


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    """Log validation failures with the offending body — a bare 422 in the
    access log made chat failures undiagnosable."""
    logger.warning(
        "422 validation failed path=%s errors=%s body=%.600s",
        request.url.path, exc.errors(), str(exc.body)[:600],
    )
    return JSONResponse(status_code=422, content={"detail": exc.errors()})


@app.post("/api/agent/respond")
async def agent_respond(request: AgentMessageRequest) -> dict[str, str]:
    """Route a chat turn either to a conversational reply or a dashboard tool."""
    system, user = _build_agent_messages(request)
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
    except Exception as exc:
        # Never fake a friendly reply when routing fails — surface the outage.
        logger.exception("agent routing failed: %s", exc)
        raise HTTPException(
            status_code=502,
            detail="AI router is temporarily unavailable. Try again shortly.",
        )

    try:
        decision = AgentToolDecision.model_validate(payload)
    except Exception as exc:
        logger.error("agent routing returned an invalid decision: %s", exc)
        raise HTTPException(
            status_code=502,
            detail="AI router returned an invalid decision.",
        )
    return decision.model_dump()


_ROUTER_TOOL_SCHEMA = {
    "type": "object",
    "additionalProperties": False,
    "properties": {
        "tool": {
            "type": "string",
            "enum": ["chat", "generate_dashboard", "edit_dashboard", "inspect_dashboard", "clear_dashboard", "replace_dashboard"],
        },
    },
    "required": ["tool"],
}

_ALLOWED_TOOLS = {"chat", "generate_dashboard", "edit_dashboard", "inspect_dashboard", "clear_dashboard", "replace_dashboard"}


def _reply_system_prompt(tool: str) -> str:
    if tool == "chat":
        return (
            "Ты AI-агент аналитической LMS. Ответь пользователю кратко (1–4 предложения) "
            "по-русски: ответь на вопрос, объясни или задай уточняющий вопрос. "
            "Без приветствий, если в истории уже было общение. Без markdown."
        )
    return (
        f"Ты AI-агент аналитической LMS. Пользователь попросил действие: {tool}. "
        "Напиши ОДНО короткое предложение-подтверждение, что начинаешь работу, по-русски, "
        "без markdown и без перечисления шагов."
    )


def _sse(payload: dict[str, Any]) -> str:
    return f"data: {json.dumps(payload, ensure_ascii=False)}\n\n"


@app.post("/api/agent/respond/stream")
async def agent_respond_stream(request: AgentMessageRequest) -> StreamingResponse:
    """SSE streaming router: meta (tool) → token* → done.

    Two phases keep the UX responsive: the routing decision (fast, structured)
    is emitted first, then the user-facing reply streams token by token. If the
    client disconnects mid-stream the handler task is cancelled and the LLM
    request aborts with it.
    """
    system, user = _build_agent_messages(request)

    async def event_stream():
        try:
            decision = await get_llm_client().chat_json(
                [system, user],
                schema_name="agent_tool",
                schema=_ROUTER_TOOL_SCHEMA,
                max_tokens=256,
            )
        except Exception as exc:
            logger.exception("stream routing failed: %s", exc)
            yield _sse({"type": "error", "detail": "AI router is temporarily unavailable. Try again shortly."})
            return
        tool = decision.get("tool", "chat")
        if tool not in _ALLOWED_TOOLS:
            tool = "chat"
        yield _sse({"type": "meta", "tool": tool})

        reply_system = ChatMessage(role="system", content=_reply_system_prompt(tool))
        prior_turns = [ChatMessage(role=m.role, content=m.content) for m in request.history]
        client = get_llm_client()
        try:
            if client.config.provider == "openai":
                async for chunk in client.chat_stream(
                    [reply_system] + prior_turns + [user], max_tokens=700
                ):
                    if chunk:
                        yield _sse({"type": "token", "text": chunk})
            else:
                response = await client.chat([reply_system] + prior_turns + [user], max_tokens=700)
                if response.content:
                    yield _sse({"type": "token", "text": response.content})
        except Exception as exc:
            logger.warning("stream reply failed: %s", exc)
            yield _sse({"type": "error", "detail": "Генерация ответа прервана."})
            return
        yield _sse({"type": "done"})

    return StreamingResponse(
        event_stream(),
        media_type="text/event-stream",
        headers={"Cache-Control": "no-cache", "X-Accel-Buffering": "no"},
    )


class JobCancelled(RuntimeError):
    """Raised inside the pipeline when the user stops the job."""


@app.post("/api/dashboard-jobs/{job_id}/cancel")
async def cancel_dashboard_job(job_id: str) -> dict[str, Any]:
    """Request cooperative cancellation of a running job."""
    job = DASHBOARD_JOBS.get(job_id)
    if not job:
        raise HTTPException(status_code=404, detail="Not Found")
    if job.get("status") == "running":
        job["cancel_requested"] = True
        logger.info("job=%s cancel requested by client", job_id)
        return {"canceled": True}
    return {"canceled": False}


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
    try:
        return await asyncio.to_thread(
            _with_datalens, lambda client: client.get_dashboard(dashboard_id)
        )
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
            def _detect_connection(client) -> str | None:
                dashboard = client.get_dashboard(dashboard_id)
                refs = [
                    chart_tab["chartId"]
                    for tab in dashboard["entry"]["data"].get("tabs", [])
                    for item in tab.get("items", [])
                    if item.get("type") == "widget"
                    for chart_tab in item.get("data", {}).get("tabs", [])
                    if chart_tab.get("chartId")
                ]
                if not refs:
                    return None
                chart = client.get_ql_chart(refs[0])
                return (chart.get("_shared") or {}).get("connection", {}).get("entryId")

            connection_id = await asyncio.to_thread(_with_datalens, _detect_connection)
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
    try:
        return await asyncio.to_thread(
            _with_datalens, lambda client: client.get_ql_chart(chart_id)
        )
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
