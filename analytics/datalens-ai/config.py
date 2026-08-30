from __future__ import annotations

from functools import lru_cache

from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


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

    # Official OpenAI API. The key must exist only in the local .env file.
    openai_api_key: str = Field(default="", repr=False)
    openai_base_url: str = Field(default="https://api.openai.com/v1")
    openai_model: str = Field(default="gpt-5.6-luna")
    openai_timeout: float = Field(default=180.0, gt=0)
    # gpt-5.6-luna editor plans can be long — keep budget sufficient for all tasks.
    # Individual structured calls (chart_one, fix, count) override with smaller values.
    openai_max_output_tokens: int = Field(default=4096, ge=256, le=32768)



@lru_cache
def get_settings() -> Settings:
    return Settings()
