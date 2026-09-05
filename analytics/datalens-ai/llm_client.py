"""Provider-agnostic LLM client used by the DataLens AI agent.

One public class, three wire protocols:

- ``openai``    — Chat Completions: the official API plus any OpenAI-compatible
                  endpoint (OpenRouter, DeepSeek, Ollama, vLLM, LM Studio, ...)
                  selected via ``LLM_BASE_URL``.
- ``anthropic`` — Claude Messages API; structured output is forced with
                  ``tool_choice`` and read back from the tool_use block.
- ``google``    — Gemini ``generateContent``; structured output is forced with
                  ``responseMimeType``/``responseSchema``.

The public surface (``chat``, ``chat_json``, ``chat_stream``, ``health_check``,
``close``) is identical for every provider, so callers stay provider-agnostic.
"""

from __future__ import annotations

import asyncio
import json
import logging
from typing import AsyncIterator, Literal

import httpx
from pydantic import BaseModel, Field

from config import get_settings
from utils import _extract_json_from_response

logger = logging.getLogger(__name__)

# Statuses that are usually transient and worth a retry.
_RETRYABLE_STATUSES = {429, 500, 502, 503, 504}
_MAX_RETRIES = 3

Provider = Literal["openai", "anthropic", "google"]


class ChatMessage(BaseModel):
    role: str
    content: str


class LLMResponse(BaseModel):
    content: str
    model: str
    usage: dict | None = None


class LLMConfig(BaseModel):
    """Connection settings for one LLM provider."""

    provider: Provider = "openai"
    base_url: str = Field(default="https://api.openai.com/v1")
    api_key: str = Field(default="", repr=False)
    model: str = Field(default="gpt-5.6-luna")
    timeout: float = Field(default=180.0)
    max_tokens: int = Field(default=4096)
    reasoning_effort: str | None = None
    # Provider-native parameters merged into every OpenAI-family request body
    # (core fields always win over these values).
    extra_body: dict = Field(default_factory=dict)


class LLMResponseError(RuntimeError):
    """The provider answered, but the payload does not satisfy the contract."""


def _split_system(messages: list[ChatMessage]) -> tuple[str, list[ChatMessage]]:
    """Anthropic and Gemini take the system prompt as a dedicated field."""
    system = "\n\n".join(m.content for m in messages if m.role == "system")
    rest = [m for m in messages if m.role != "system"]
    return system, rest


def _loads_flexible(content: str) -> dict | None:
    """json.loads with a markdown-wrapped / noisy-output fallback."""
    for candidate in (content, _extract_json_from_response(content) or ""):
        if not candidate:
            continue
        try:
            parsed = json.loads(candidate)
        except (json.JSONDecodeError, TypeError):
            continue
        if isinstance(parsed, dict):
            return parsed
    return None


def _to_gemini_schema(node):
    """Reduce a JSON Schema to the OpenAPI subset Gemini accepts."""
    if isinstance(node, dict):
        reduced: dict = {}
        for key, value in node.items():
            if key in ("$schema", "additionalProperties", "strict"):
                continue
            if key == "type" and isinstance(value, str):
                reduced[key] = value.lower()
            else:
                reduced[key] = _to_gemini_schema(value)
        return reduced
    if isinstance(node, list):
        return [_to_gemini_schema(item) for item in node]
    return node


class LLMClient:
    """Async LLM client that dispatches to the configured provider protocol."""

    def __init__(
        self,
        config: LLMConfig | None = None,
        *,
        transport: httpx.AsyncBaseTransport | None = None,
    ):
        if config is None:
            settings = get_settings()
            config = LLMConfig(
                provider=settings.llm_provider,  # type: ignore[arg-type]
                base_url=settings.llm_base_url_effective,
                api_key=settings.llm_api_key,
                model=settings.llm_model_effective,
                timeout=settings.llm_timeout,
                max_tokens=settings.llm_max_output_tokens,
                reasoning_effort=settings.llm_reasoning_effort_value,
                extra_body=settings.llm_extra_body_value,
            )
        self.config = config
        self.client = httpx.AsyncClient(
            base_url=config.base_url.rstrip("/"),
            timeout=config.timeout,
            headers={"Content-Type": "application/json", **self._auth_headers()},
            transport=transport,
        )

    # ------------------------------------------------------------------
    # Auth
    # ------------------------------------------------------------------

    def _auth_headers(self) -> dict[str, str]:
        provider = self.config.provider
        if provider == "anthropic":
            headers = {"anthropic-version": "2023-06-01"}
            if self.config.api_key:
                headers["x-api-key"] = self.config.api_key
            return headers
        if provider == "google":
            return {"x-goog-api-key": self.config.api_key} if self.config.api_key else {}
        return {"Authorization": f"Bearer {self.config.api_key}"} if self.config.api_key else {}

    # ------------------------------------------------------------------
    # Health
    # ------------------------------------------------------------------

    async def health_check(self) -> bool:
        """Validate that the configured model is reachable with these credentials."""
        try:
            if self.config.provider == "anthropic":
                return await self._health_anthropic()
            if self.config.provider == "google":
                return await self._health_google()
            return await self._health_openai()
        except httpx.HTTPError as exc:
            logger.warning("LLM health check failed (%s): %s", self.config.provider, exc)
            return False

    async def _health_openai(self) -> bool:
        response = await self.client.get(f"/models/{self.config.model}", timeout=10.0)
        if response.status_code == 200:
            return True
        # Some newer models may not appear in the list — try a minimal call.
        response2 = await self.client.post(
            "/chat/completions",
            json={
                "model": self.config.model,
                "messages": [{"role": "user", "content": "hi"}],
                "max_completion_tokens": 5,
            },
            timeout=15.0,
        )
        return response2.status_code == 200

    async def _health_anthropic(self) -> bool:
        response = await self.client.get(f"/v1/models/{self.config.model}", timeout=10.0)
        if response.status_code == 200:
            return True
        response2 = await self.client.post(
            "/v1/messages",
            json={
                "model": self.config.model,
                "max_tokens": 1,
                "messages": [{"role": "user", "content": "hi"}],
            },
            timeout=15.0,
        )
        return response2.status_code == 200

    async def _health_google(self) -> bool:
        response = await self.client.get(f"/models/{self.config.model}", timeout=10.0)
        if response.status_code == 200:
            return True
        response2 = await self.client.post(
            f"/models/{self.config.model}:generateContent",
            json={
                "contents": [{"role": "user", "parts": [{"text": "hi"}]}],
                "generationConfig": {"maxOutputTokens": 5},
            },
            timeout=15.0,
        )
        return response2.status_code == 200

    # ------------------------------------------------------------------
    # Retry
    # ------------------------------------------------------------------

    async def _post_with_retry(
        self, path: str, payload: dict, max_retries: int = _MAX_RETRIES
    ) -> httpx.Response:
        """POST with backoff on rate limits, provider 5xx and transport errors."""
        last_response: httpx.Response | None = None
        for attempt in range(max_retries):
            try:
                response = await self.client.post(path, json=payload)
            except httpx.HTTPError as exc:
                if attempt == max_retries - 1:
                    raise
                logger.warning(
                    "LLM %s transport error (attempt %d/%d): %s",
                    self.config.provider, attempt + 1, max_retries, exc,
                )
            else:
                last_response = response
                if response.status_code not in _RETRYABLE_STATUSES or attempt == max_retries - 1:
                    return response
                logger.warning(
                    "LLM %s HTTP %s (attempt %d/%d)",
                    self.config.provider, response.status_code, attempt + 1, max_retries,
                )
            await asyncio.sleep(2**attempt)
        return last_response  # pragma: no cover - loop always returns or raises

    # ------------------------------------------------------------------
    # Completion dispatch
    # ------------------------------------------------------------------

    async def chat(
        self, messages: list[ChatMessage], stream: bool = False, max_tokens: int | None = None
    ) -> LLMResponse:
        if stream:
            raise ValueError("Use chat_stream() for streamed responses.")
        content, model, usage = await self._complete(messages, max_tokens=max_tokens)
        return LLMResponse(content=content, model=model, usage=usage)

    async def chat_json(
        self,
        messages: list[ChatMessage],
        *,
        schema_name: str,
        schema: dict,
        max_tokens: int | None = None,
    ) -> dict:
        """Request schema-constrained JSON from the configured provider."""
        content, model, usage = await self._complete(
            messages, max_tokens=max_tokens, json_contract=(schema_name, schema)
        )
        parsed = _loads_flexible(content)
        if parsed is None:
            logger.error(
                "LLM %s returned non-JSON content for %s: %.200s",
                self.config.provider, schema_name, content,
            )
            raise LLMResponseError(f"LLM response for '{schema_name}' is not valid JSON.")
        logger.info(
            "LLM structured response provider=%s model=%s usage=%s",
            self.config.provider, model, usage,
        )
        return parsed

    async def _complete(
        self,
        messages: list[ChatMessage],
        *,
        max_tokens: int | None = None,
        json_contract: tuple[str, dict] | None = None,
        _allow_empty_retry: bool = True,
    ) -> tuple[str, str, dict | None]:
        """Return (content_text, model, usage); structured text is raw JSON.

        Reasoning models (GLM, DeepSeek-R1, Qwen3, o-series) spend tokens on
        internal thinking before emitting content. When a small token budget
        runs out on reasoning, providers return an empty content with HTTP 200.
        One automatic retry with a 4x budget fixes that for every provider.
        """
        if self.config.provider == "anthropic":
            result = await self._complete_anthropic(
                messages, max_tokens=max_tokens, json_contract=json_contract
            )
        elif self.config.provider == "google":
            result = await self._complete_google(
                messages, max_tokens=max_tokens, json_contract=json_contract
            )
        else:
            result = await self._complete_openai(
                messages, max_tokens=max_tokens, json_contract=json_contract
            )

        if _allow_empty_retry and not result[0].strip():
            budget = (max_tokens or self.config.max_tokens) * 4
            logger.warning(
                "LLM %s returned empty content (reasoning budget exhausted?); "
                "retrying once with %d token budget",
                self.config.provider, budget,
            )
            return await self._complete(
                messages,
                max_tokens=budget,
                json_contract=json_contract,
                _allow_empty_retry=False,
            )
        return result

    # ------------------------------------------------------------------
    # OpenAI / OpenAI-compatible
    # ------------------------------------------------------------------

    def _is_official_openai(self) -> bool:
        return str(self.client.base_url).startswith("https://api.openai.com")

    def _openai_payload(
        self,
        messages: list[ChatMessage],
        *,
        stream: bool = False,
        max_tokens: int | None = None,
    ) -> dict:
        # Official OpenAI reasoning models require max_completion_tokens, while
        # most third-party compatible servers only understand max_tokens.
        token_key = (
            "max_completion_tokens" if self._is_official_openai() else "max_tokens"
        )
        payload: dict = {
            "model": self.config.model,
            "messages": [message.model_dump() for message in messages],
            token_key: max_tokens or self.config.max_tokens,
            "stream": stream,
        }
        if self.config.reasoning_effort and not stream:
            payload["reasoning_effort"] = self.config.reasoning_effort
        if self.config.extra_body:
            # Core fields win: the operator cannot break model/messages/tokens.
            payload = {**self.config.extra_body, **payload}
        return payload

    async def _complete_openai(
        self,
        messages: list[ChatMessage],
        *,
        max_tokens: int | None = None,
        json_contract: tuple[str, dict] | None = None,
    ) -> tuple[str, str, dict | None]:
        payload = self._openai_payload(messages, max_tokens=max_tokens)
        if json_contract:
            name, schema = json_contract
            payload["response_format"] = {
                "type": "json_schema",
                "json_schema": {"name": name, "strict": True, "schema": schema},
            }
        response = await self._post_with_retry("/chat/completions", payload)
        if json_contract and response.status_code == 400:
            # Weak OpenAI-compatible servers reject response_format — retry as
            # plain text and recover the JSON from the content afterwards.
            logger.info("Provider rejected json_schema response_format; retrying without it")
            payload.pop("response_format")
            response = await self._post_with_retry("/chat/completions", payload)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError:
            logger.error(
                "LLM openai request failed: HTTP %s body=%.500s",
                response.status_code, response.text,
            )
            raise
        data = response.json()
        logger.info(
            "LLM response provider=openai model=%s usage=%s",
            data.get("model", self.config.model), data.get("usage", {}),
        )
        content = data["choices"][0]["message"].get("content") or ""
        return content, data.get("model", self.config.model), data.get("usage")

    # ------------------------------------------------------------------
    # Anthropic
    # ------------------------------------------------------------------

    async def _complete_anthropic(
        self,
        messages: list[ChatMessage],
        *,
        max_tokens: int | None = None,
        json_contract: tuple[str, dict] | None = None,
    ) -> tuple[str, str, dict | None]:
        system, rest = _split_system(messages)
        payload: dict = {
            "model": self.config.model,
            "max_tokens": max_tokens or self.config.max_tokens,
            "messages": [{"role": m.role, "content": m.content} for m in rest],
        }
        if system:
            payload["system"] = system
        if json_contract:
            name, schema = json_contract
            payload["tools"] = [{
                "name": name,
                "description": f"Return the {name} result.",
                "input_schema": schema,
            }]
            payload["tool_choice"] = {"type": "tool", "name": name}
        response = await self._post_with_retry("/v1/messages", payload)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError:
            logger.error(
                "LLM anthropic request failed: HTTP %s body=%.500s",
                response.status_code, response.text,
            )
            raise
        data = response.json()
        usage = data.get("usage")
        model = data.get("model", self.config.model)
        texts: list[str] = []
        tool_inputs: list[dict] = []
        for block in data.get("content", []):
            if block.get("type") == "text":
                texts.append(block.get("text", ""))
            elif block.get("type") == "tool_use" and isinstance(block.get("input"), dict):
                tool_inputs.append(block["input"])
        if json_contract:
            if not tool_inputs:
                raise LLMResponseError(
                    "Anthropic did not return the expected tool_use block."
                )
            return json.dumps(tool_inputs[-1]), model, usage
        return "\n".join(texts), model, usage

    # ------------------------------------------------------------------
    # Google Gemini
    # ------------------------------------------------------------------

    async def _complete_google(
        self,
        messages: list[ChatMessage],
        *,
        max_tokens: int | None = None,
        json_contract: tuple[str, dict] | None = None,
    ) -> tuple[str, str, dict | None]:
        system, rest = _split_system(messages)
        generation_config: dict = {
            "maxOutputTokens": max_tokens or self.config.max_tokens,
        }
        payload: dict = {
            "contents": [
                {
                    "role": "user" if m.role == "user" else "model",
                    "parts": [{"text": m.content}],
                }
                for m in rest
            ],
            "generationConfig": generation_config,
        }
        if system:
            payload["systemInstruction"] = {"parts": [{"text": system}]}
        if json_contract:
            _, schema = json_contract
            generation_config["responseMimeType"] = "application/json"
            generation_config["responseSchema"] = _to_gemini_schema(schema)
        path = f"/models/{self.config.model}:generateContent"
        response = await self._post_with_retry(path, payload)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError:
            logger.error(
                "LLM google request failed: HTTP %s body=%.500s",
                response.status_code, response.text,
            )
            raise
        data = response.json()
        candidates = data.get("candidates") or []
        parts = candidates[0].get("content", {}).get("parts", []) if candidates else []
        content = "".join(part.get("text", "") for part in parts)
        return content, self.config.model, data.get("usageMetadata")

    # ------------------------------------------------------------------
    # Streaming (OpenAI-compatible family only)
    # ------------------------------------------------------------------

    async def chat_stream(self, messages: list[ChatMessage], max_tokens: int | None = None) -> AsyncIterator[str]:
        if self.config.provider != "openai":
            raise NotImplementedError(
                "Streaming is implemented for the OpenAI-compatible family only."
            )
        async with self.client.stream(
            "POST",
            "/chat/completions",
            json=self._openai_payload(messages, stream=True, max_tokens=max_tokens),
        ) as response:
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


__all__ = [
    "ChatMessage",
    "LLMConfig",
    "LLMResponse",
    "LLMResponseError",
    "LLMClient",
    "get_llm_client",
]
