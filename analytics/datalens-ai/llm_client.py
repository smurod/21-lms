"""Official OpenAI API client used by the DataLens AI agent."""

from __future__ import annotations

import json
import logging
from typing import AsyncIterator

import httpx
from pydantic import BaseModel, Field

from config import get_settings

logger = logging.getLogger(__name__)


class ChatMessage(BaseModel):
    role: str
    content: str


class LLMResponse(BaseModel):
    content: str
    model: str
    usage: dict | None = None


class LLMConfig(BaseModel):
    """OpenAI connection settings retained under the existing client API."""

    base_url: str = Field(default="https://api.openai.com/v1")
    api_key: str = Field(min_length=1)
    model: str = Field(default="gpt-5.6-luna")
    timeout: float = Field(default=180.0)
    max_tokens: int = Field(default=8192)
    reasoning_effort: str = Field(default="low")


class LLMClient:
    """Async client for the official OpenAI Chat Completions API."""

    def __init__(self, config: LLMConfig | None = None):
        settings = get_settings()
        self.config = config or LLMConfig(
            base_url=settings.openai_base_url,
            api_key=settings.openai_api_key,
            model=settings.openai_model,
            timeout=settings.openai_timeout,
            max_tokens=settings.openai_max_output_tokens,
            reasoning_effort=settings.openai_reasoning_effort,
        )
        self.client = httpx.AsyncClient(
            base_url=self.config.base_url.rstrip("/"),
            timeout=self.config.timeout,
            headers={
                "Authorization": f"Bearer {self.config.api_key}",
                "Content-Type": "application/json",
            },
        )

    async def health_check(self) -> bool:
        """Validate that the configured OpenAI model is available to this key."""
        try:
            response = await self.client.get(f"/models/{self.config.model}")
            return response.status_code == 200
        except httpx.HTTPError as exc:
            logger.warning("OpenAI health check failed: %s", exc)
            return False

    def _payload(self, messages: list[ChatMessage], *, stream: bool = False, max_tokens: int | None = None) -> dict:
        return {
            "model": self.config.model,
            "messages": [message.model_dump() for message in messages],
            "max_completion_tokens": max_tokens or self.config.max_tokens,
            "reasoning_effort": self.config.reasoning_effort,
            "stream": stream,
        }

    async def chat_json(
        self,
        messages: list[ChatMessage],
        *,
        schema_name: str,
        schema: dict,
        max_tokens: int | None = None,
    ) -> dict:
        """Request schema-constrained JSON from OpenAI for agent tool contracts."""
        payload = self._payload(messages, max_tokens=max_tokens)
        payload["response_format"] = {
            "type": "json_schema",
            "json_schema": {
                "name": schema_name,
                "strict": True,
                "schema": schema,
            },
        }
        response = await self.client.post("/chat/completions", json=payload)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError:
            logger.error("OpenAI structured request failed: HTTP %s", response.status_code)
            raise
        data = response.json()
        logger.info("OpenAI structured response model=%s usage=%s", data.get("model", self.config.model), data.get("usage", {}))
        content = data["choices"][0]["message"].get("content") or "{}"
        return json.loads(content)

    async def chat(self, messages: list[ChatMessage], stream: bool = False, max_tokens: int | None = None) -> LLMResponse:
        if stream:
            raise ValueError("Use chat_stream() for streamed OpenAI responses.")
        response = await self.client.post("/chat/completions", json=self._payload(messages, max_tokens=max_tokens))
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError:
            logger.error("OpenAI request failed: HTTP %s", response.status_code)
            raise

        data = response.json()
        logger.info("OpenAI response model=%s usage=%s", data.get("model", self.config.model), data.get("usage", {}))
        content = data["choices"][0]["message"].get("content") or ""
        return LLMResponse(
            content=content,
            model=data.get("model", self.config.model),
            usage=data.get("usage"),
        )

    async def chat_stream(self, messages: list[ChatMessage]) -> AsyncIterator[str]:
        async with self.client.stream("POST", "/chat/completions", json=self._payload(messages, stream=True)) as response:
            response.raise_for_status()
            async for line in response.aiter_lines():
                if not line.startswith("data: "):
                    continue
                value = line[6:]
                if value.strip() == "[DONE]":
                    return
                try:
                    chunk = json.loads(value)
                    delta = chunk.get("choices", [{}])[0].get("delta", {})
                    content = delta.get("content")
                    if content:
                        yield content
                except json.JSONDecodeError:
                    continue

    async def close(self) -> None:
        await self.client.aclose()


_llm_client: LLMClient | None = None


def get_llm_client(config: LLMConfig | None = None) -> LLMClient:
    global _llm_client
    if _llm_client is None:
        _llm_client = LLMClient(config)
    return _llm_client
