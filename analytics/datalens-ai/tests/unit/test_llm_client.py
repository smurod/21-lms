"""Unit tests for the provider-agnostic LLM client.

Every test runs against ``httpx.MockTransport``, so no network access and no
real provider keys are involved.
"""

from __future__ import annotations

import asyncio
import json

import httpx
import pytest
from pydantic import ValidationError

from config import Settings
from llm_client import ChatMessage, LLMClient, LLMConfig, LLMResponseError


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------


def make_config(provider: str = "openai", **overrides) -> LLMConfig:
    values = {
        "provider": provider,
        "base_url": {
            "openai": "https://api.openai.com/v1",
            "anthropic": "https://api.anthropic.com",
            "google": "https://generativelanguage.googleapis.com/v1beta",
        }[provider],
        "api_key": "test-key",
        "model": "test-model",
        "timeout": 5.0,
        "max_tokens": 256,
    }
    values.update(overrides)
    return LLMConfig(**values)


def make_client(provider: str, handler) -> LLMClient:
    return LLMClient(make_config(provider), transport=httpx.MockTransport(handler))


MESSAGES = [
    ChatMessage(role="system", content="Возвращай только JSON."),
    ChatMessage(role="user", content="Сделай чарт."),
]


# ---------------------------------------------------------------------------
# OpenAI family
# ---------------------------------------------------------------------------


def test_openai_chat_returns_content():
    def handler(request: httpx.Request) -> httpx.Response:
        assert request.url.path.endswith("/chat/completions")
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "hello"}}],
            "usage": {"total_tokens": 3},
        })

    client = make_client("openai", handler)
    response = asyncio.run(client.chat(MESSAGES))

    assert response.content == "hello"
    assert response.model == "test-model"
    assert response.usage == {"total_tokens": 3}


def test_openai_chat_json_sends_schema_contract():
    captured: dict = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["body"] = json.loads(request.content)
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": '{"answer": 1}'}}],
        })

    client = make_client("openai", handler)
    result = asyncio.run(client.chat_json(
        MESSAGES, schema_name="probe", schema={"type": "object", "properties": {}},
    ))

    assert result == {"answer": 1}
    contract = captured["body"]["response_format"]["json_schema"]
    assert contract["name"] == "probe"
    assert contract["strict"] is True


def test_openai_chat_json_falls_back_when_response_format_rejected():
    calls: list[dict] = []

    def handler(request: httpx.Request) -> httpx.Response:
        calls.append(json.loads(request.content))
        if len(calls) == 1:
            return httpx.Response(400, json={"error": {"message": "response_format unsupported"}})
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "```json\n{\"answer\": 2}\n```"}}],
        })

    client = make_client("openai", handler)
    result = asyncio.run(client.chat_json(
        MESSAGES, schema_name="probe", schema={"type": "object"},
    ))

    assert result == {"answer": 2}
    assert "response_format" not in calls[1]
    # The official endpoint must use max_completion_tokens.
    assert "max_completion_tokens" in calls[0]


def test_openai_compatible_endpoint_uses_max_tokens():
    captured: dict = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["body"] = json.loads(request.content)
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "ok"}}],
        })

    client = LLMClient(
        make_config("openai", base_url="https://openrouter.ai/api/v1"),
        transport=httpx.MockTransport(handler),
    )
    asyncio.run(client.chat(MESSAGES))

    assert "max_tokens" in captured["body"]
    assert "max_completion_tokens" not in captured["body"]


def test_openai_retries_on_500():
    calls: list[int] = []

    def handler(request: httpx.Request) -> httpx.Response:
        calls.append(1)
        if len(calls) == 1:
            return httpx.Response(500, json={"error": "boom"})
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "recovered"}}],
        })

    client = make_client("openai", handler)
    response = asyncio.run(client.chat(MESSAGES))

    assert response.content == "recovered"
    assert len(calls) == 2


def test_chat_json_raises_llm_response_error_on_non_json():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "совсем не JSON"}}],
        })

    client = make_client("openai", handler)
    with pytest.raises(LLMResponseError):
        asyncio.run(client.chat_json(
            MESSAGES, schema_name="probe", schema={"type": "object"},
        ))


def test_openai_health_check_uses_models_endpoint():
    def handler(request: httpx.Request) -> httpx.Response:
        assert request.method == "GET"
        assert request.url.path.endswith("/models/test-model")
        return httpx.Response(200, json={"id": "test-model"})

    client = make_client("openai", handler)
    assert asyncio.run(client.health_check()) is True


# ---------------------------------------------------------------------------
# Anthropic family
# ---------------------------------------------------------------------------


def test_anthropic_chat_json_uses_tool_choice():
    captured: dict = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["body"] = json.loads(request.content)
        assert request.url.path == "/v1/messages"
        return httpx.Response(200, json={
            "model": "test-model",
            "content": [{"type": "tool_use", "id": "t1", "name": "probe", "input": {"plan": []}}],
            "usage": {"input_tokens": 10, "output_tokens": 5},
        })

    client = make_client("anthropic", handler)
    result = asyncio.run(client.chat_json(
        MESSAGES, schema_name="probe", schema={"type": "object", "properties": {}},
    ))

    assert result == {"plan": []}
    body = captured["body"]
    # System prompt must move to the dedicated field, not stay in messages.
    assert body["system"] == "Возвращай только JSON."
    assert [m["role"] for m in body["messages"]] == ["user"]
    assert body["max_tokens"] == 256
    assert body["tool_choice"] == {"type": "tool", "name": "probe"}
    assert body["tools"][0]["name"] == "probe"


def test_anthropic_chat_without_tools_returns_text():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(200, json={
            "model": "test-model",
            "content": [{"type": "text", "text": "привет"}],
        })

    client = make_client("anthropic", handler)
    response = asyncio.run(client.chat(MESSAGES))

    assert response.content == "привет"


def test_anthropic_chat_json_raises_without_tool_use():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(200, json={
            "model": "test-model",
            "content": [{"type": "text", "text": "{}"}],
        })

    client = make_client("anthropic", handler)
    with pytest.raises(LLMResponseError):
        asyncio.run(client.chat_json(
            MESSAGES, schema_name="probe", schema={"type": "object"},
        ))


# ---------------------------------------------------------------------------
# Google Gemini family
# ---------------------------------------------------------------------------


def test_google_chat_json_sends_response_schema():
    captured: dict = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["body"] = json.loads(request.content)
        assert ":generateContent" in request.url.path
        return httpx.Response(200, json={
            "candidates": [{"content": {"parts": [{"text": '{"metric": 42}'}]}}],
            "usageMetadata": {"totalTokenCount": 7},
        })

    client = make_client("google", handler)
    result = asyncio.run(client.chat_json(
        MESSAGES, schema_name="probe",
        schema={"type": "object", "properties": {}, "additionalProperties": False},
    ))

    assert result == {"metric": 42}
    config = captured["body"]["generationConfig"]
    assert config["responseMimeType"] == "application/json"
    # OpenAPI subset: unsupported JSON-Schema keys are stripped.
    assert "additionalProperties" not in config["responseSchema"]
    assert captured["body"]["systemInstruction"]["parts"][0]["text"] == "Возвращай только JSON."


def test_google_health_check_uses_model_endpoint():
    def handler(request: httpx.Request) -> httpx.Response:
        assert request.method == "GET"
        assert request.url.path.endswith("/models/test-model")
        return httpx.Response(200, json={"name": "models/test-model"})

    client = make_client("google", handler)
    assert asyncio.run(client.health_check()) is True


# ---------------------------------------------------------------------------
# Reasoning models and extra body
# ---------------------------------------------------------------------------


def test_empty_content_retried_with_larger_budget():
    """Reasoning models can burn the whole budget on thinking and return ''."""
    budgets: list[int] = []

    def handler(request: httpx.Request) -> httpx.Response:
        body = json.loads(request.content)
        budgets.append(body.get("max_completion_tokens"))
        if len(budgets) == 1:
            return httpx.Response(200, json={
                "model": "test-model",
                "choices": [{"message": {"content": ""}}],
                "usage": {"completion_tokens_details": {"reasoning_tokens": 254}},
            })
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": '{"chart_count": 3}'}}],
        })

    client = make_client("openai", handler)
    result = asyncio.run(client.chat_json(
        MESSAGES, schema_name="probe", schema={"type": "object"},
    ))

    assert result == {"chart_count": 3}
    assert budgets == [256, 1024]  # one automatic retry with a 4x budget


def test_empty_content_retry_exhaustion_raises():
    calls: list[int] = []

    def handler(request: httpx.Request) -> httpx.Response:
        calls.append(1)
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "   "}}],
        })

    client = make_client("openai", handler)
    with pytest.raises(LLMResponseError):
        asyncio.run(client.chat_json(
            MESSAGES, schema_name="probe", schema={"type": "object"},
        ))
    assert len(calls) == 2  # exactly one retry, no loop


def test_extra_body_merged_and_core_fields_win():
    def handler(request: httpx.Request) -> httpx.Response:
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "ok"}}],
        })

    client = LLMClient(
        make_config("openai", extra_body={"thinking": {"type": "disabled"}, "model": "hax"}),
        transport=httpx.MockTransport(handler),
    )
    asyncio.run(client.chat(MESSAGES))

    # extra_body is sent, but cannot override core payload fields.
    assert client.config.extra_body == {"thinking": {"type": "disabled"}, "model": "hax"}


def test_extra_body_reaches_request_body():
    captured: dict = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["body"] = json.loads(request.content)
        return httpx.Response(200, json={
            "model": "test-model",
            "choices": [{"message": {"content": "ok"}}],
        })

    client = LLMClient(
        make_config("openai", extra_body={"thinking": {"type": "disabled"}}),
        transport=httpx.MockTransport(handler),
    )
    asyncio.run(client.chat(MESSAGES))

    assert captured["body"]["thinking"] == {"type": "disabled"}
    assert captured["body"]["model"] == "test-model"


# ---------------------------------------------------------------------------
# Config: universal settings and legacy fallback
# ---------------------------------------------------------------------------


def test_settings_legacy_openai_fallback():
    settings = Settings(
        _env_file=None,
        llm_provider="openai",
        openai_api_key="legacy-key",
        openai_model="legacy-model",
    )

    assert settings.llm_api_key == "legacy-key"
    assert settings.llm_model == "legacy-model"
    assert settings.llm_base_url_effective == "https://api.openai.com/v1"
    assert settings.llm_model_effective == "legacy-model"
    assert settings.llm_key_required is True


def test_settings_provider_defaults_fill_empty_values():
    settings = Settings(_env_file=None, llm_provider="anthropic")

    assert settings.llm_base_url_effective == "https://api.anthropic.com"
    assert settings.llm_model_effective == "claude-sonnet-4-6"
    assert settings.llm_key_required is True


def test_settings_keyless_local_endpoint_is_allowed():
    settings = Settings(
        _env_file=None,
        llm_provider="openai",
        llm_base_url="http://127.0.0.1:11434/v1",
    )

    assert settings.llm_key_required is False


def test_settings_rejects_unknown_provider():
    with pytest.raises(ValidationError):
        Settings(_env_file=None, llm_provider="mistral")


def test_settings_reasoning_effort_empty_is_none():
    settings = Settings(_env_file=None, llm_provider="openai", llm_reasoning_effort="")

    assert settings.llm_reasoning_effort_value is None


def test_settings_extra_body_parses_json():
    settings = Settings(_env_file=None, llm_extra_body='{"thinking":{"type":"disabled"}}')

    assert settings.llm_extra_body_value == {"thinking": {"type": "disabled"}}


def test_settings_extra_body_empty_is_empty_dict():
    settings = Settings(_env_file=None, llm_extra_body="")

    assert settings.llm_extra_body_value == {}


def test_settings_extra_body_rejects_invalid_json():
    settings = Settings(_env_file=None, llm_extra_body="{not json")

    with pytest.raises(ValueError):
        settings.llm_extra_body_value  # noqa: B018 - fails fast on service startup
