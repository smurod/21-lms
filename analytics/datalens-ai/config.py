from __future__ import annotations

import json
import os
from functools import lru_cache

from pydantic import Field, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

# Supported LLM API families. Single source of truth for config validation,
# LLMClient dispatch and the .env.example documentation.
LLM_PROVIDERS = ("openai", "anthropic", "google")

# Official endpoints used when LLM_BASE_URL is left empty.
LLM_PROVIDER_DEFAULTS: dict[str, dict[str, str]] = {
    "openai": {"base_url": "https://api.openai.com/v1", "model": "gpt-5.6-luna"},
    "anthropic": {"base_url": "https://api.anthropic.com", "model": "claude-sonnet-4-6"},
    "google": {"base_url": "https://generativelanguage.googleapis.com/v1beta", "model": "gemini-2.5-flash"},
}


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
        case_sensitive=False,
    )

    # DataLens UI and API.
    datalens_base_url: str = Field(
        default="http://localhost:8085",
        description="Адрес DataLens UI, который открыт в браузере.",
    )

    # Auth-сервис обычно доступен только внутри Docker.
    # Его нужно опубликовать через docker-compose.override.yml на 127.0.0.1:8088.
    datalens_auth_url: str = Field(
        default="http://127.0.0.1:8088",
        description="Адрес auth-сервиса DataLens.",
    )
    datalens_username: str = Field(default="admin")
    datalens_password: str = Field(default="admin")
    datalens_workbook_title: str = Field(default="AI Generated")

    # United Storage is published by the local DataLens dev compose on 3030.
    # It is used for explicit dashboard locks during AI edits.
    datalens_us_url: str = Field(default="http://localhost:3030")
    datalens_timeout: float = Field(default=60.0, gt=0)

    # Browser origins allowed to call the FastAPI service (including SSE).
    # A comma-separated value is convenient in .env and avoids a permissive
    # wildcard together with credentials.
    cors_origins: str = Field(default="http://127.0.0.1:8000,http://localhost:8000")

    @property
    def cors_origin_list(self) -> list[str]:
        return [origin.strip() for origin in self.cors_origins.split(",") if origin.strip()]

    # DataLens connection type, e.g. postgres, mysql, clickhouse. The AI
    # analyzer currently validates PostgreSQL explicitly before work begins.
    datalens_db_type: str = Field(default="postgres")

    # Адрес базы данных, который видит DataLens из Docker.
    # Внутри контейнера localhost — это сам контейнер, поэтому для базы на хосте
    # обычно используется 172.17.0.1.
    datalens_db_host: str = Field(default="172.17.0.1")
    datalens_db_port: int = Field(default=5432)
    datalens_db_name: str = Field(default="21_lms")
    datalens_db_user: str = Field(default="wren_user")
    datalens_db_password: str = Field(default="wren_password")

    # Адрес базы данных для самого AI-сервиса.
    db_url: str = Field(
        default="postgresql://wren_user:wren_password@127.0.0.1:5432/21_lms"
    )

    # Shared secret between Laravel and datalens-ai.
    # All endpoints except /health and / require X-API-Key header matching this value.
    # Leave empty to disable auth (local development without Laravel).
    service_api_key: str = Field(default="", repr=False)

    # Official OpenAI API (legacy variables, kept as fallback for LLM_*).
    openai_api_key: str = Field(default="", repr=False)
    openai_base_url: str = Field(default="https://api.openai.com/v1")
    openai_model: str = Field(default="gpt-5.6-luna")
    openai_timeout: float = Field(default=180.0, gt=0)
    # gpt-5.6-luna editor plans can be long — keep budget sufficient for all tasks.
    # Individual structured calls (chart_one, fix, count) override with smaller values.
    openai_max_output_tokens: int = Field(default=4096, ge=256, le=32768)

    # Universal LLM provider settings (preferred over the legacy OPENAI_* block).
    # provider `openai` also covers any OpenAI-compatible endpoint — set
    # LLM_BASE_URL for OpenRouter, DeepSeek, Ollama, vLLM, LM Studio, etc.
    llm_provider: str = Field(default="openai")
    llm_api_key: str = Field(default="", repr=False)
    llm_base_url: str = Field(
        default="",
        description="Base URL of the LLM API. Empty = official endpoint of the provider.",
    )
    llm_model: str = Field(
        default="",
        description="Model name. Empty = per-provider default.",
    )
    llm_timeout: float = Field(default=180.0, gt=0)
    llm_max_output_tokens: int = Field(default=4096, ge=256, le=32768)
    # Reasoning effort for OpenAI-family reasoning models: minimal|low|medium|high.
    # Empty = omit the parameter entirely (required for non-OpenAI providers).
    llm_reasoning_effort: str = Field(default="")
    # Escape hatch for provider-native parameters, merged verbatim into every
    # OpenAI-family request body. Example for GLM: {"thinking":{"type":"disabled"}}
    llm_extra_body: str = Field(
        default="",
        description='Raw JSON merged into request payloads, e.g. {"thinking":{"type":"disabled"}}.',
    )

    @model_validator(mode="after")
    def _apply_legacy_openai_fallback(self) -> "Settings":
        """Fill empty LLM_* values from the legacy OPENAI_* block.

        OPENAI_TIMEOUT / OPENAI_MAX_OUTPUT_TOKENS / OPENAI_REASONING_EFFORT are
        taken when the corresponding LLM_* variable is absent from the process
        environment, because the LLM_* defaults are indistinguishable from an
        unset variable here.
        """
        if self.llm_provider not in LLM_PROVIDERS:
            raise ValueError(
                f"LLM_PROVIDER must be one of {', '.join(LLM_PROVIDERS)}, got '{self.llm_provider}'."
            )
        if not self.llm_api_key and self.openai_api_key:
            self.llm_api_key = self.openai_api_key
        if self.llm_provider == "openai":
            if not self.llm_base_url:
                self.llm_base_url = self.openai_base_url
            if not self.llm_model:
                self.llm_model = self.openai_model
        if "LLM_TIMEOUT" not in os.environ and "OPENAI_TIMEOUT" in os.environ:
            self.llm_timeout = self.openai_timeout
        if "LLM_MAX_OUTPUT_TOKENS" not in os.environ and "OPENAI_MAX_OUTPUT_TOKENS" in os.environ:
            self.llm_max_output_tokens = self.openai_max_output_tokens
        if "LLM_REASONING_EFFORT" not in os.environ and "OPENAI_REASONING_EFFORT" in os.environ:
            self.llm_reasoning_effort = os.environ["OPENAI_REASONING_EFFORT"].strip()
        return self

    @property
    def llm_base_url_effective(self) -> str:
        return self.llm_base_url or LLM_PROVIDER_DEFAULTS[self.llm_provider]["base_url"]

    @property
    def llm_model_effective(self) -> str:
        return self.llm_model or LLM_PROVIDER_DEFAULTS[self.llm_provider]["model"]

    @property
    def llm_key_required(self) -> bool:
        """Self-hosted OpenAI-compatible servers (Ollama, vLLM) may run keyless."""
        if self.llm_provider in ("anthropic", "google"):
            return True
        return self.llm_base_url_effective.startswith("https://api.openai.com")

    @property
    def llm_reasoning_effort_value(self) -> str | None:
        return self.llm_reasoning_effort.strip() or None

    @property
    def llm_extra_body_value(self) -> dict:
        raw = self.llm_extra_body.strip()
        if not raw:
            return {}
        try:
            parsed = json.loads(raw)
        except json.JSONDecodeError as exc:
            raise ValueError(
                f"LLM_EXTRA_BODY must be valid JSON, got: {raw[:120]} ({exc})."
            ) from exc
        if not isinstance(parsed, dict):
            raise ValueError("LLM_EXTRA_BODY must be a JSON object, e.g. {\"thinking\":{\"type\":\"disabled\"}}.")
        return parsed



@lru_cache
def get_settings() -> Settings:
    return Settings()
