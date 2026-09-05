"""Unit tests for the dynamic job completion replies (main._summarize_*)."""

from __future__ import annotations

import asyncio

import main


class _ExplodingClient:
    async def chat(self, *args, **kwargs):
        raise RuntimeError("LLM down")


def test_fallback_reply_lists_charts():
    reply = main._fallback_job_reply(
        {
            "title": "Аналитика LMS",
            "charts": [
                {"title": "Активность за месяц", "type": "line"},
                {"title": "Топ пользователей", "type": "bar"},
            ],
        },
        "generate",
    )

    assert "Аналитика LMS" in reply
    assert "Активность за месяц" in reply
    assert "Топ пользователей" in reply


def test_fallback_reply_reports_edit_actions():
    reply = main._fallback_job_reply(
        {
            "title": "Дашборд",
            "added": [1],
            "updated": [],
            "deleted": [1, 2],
            "charts": [{"title": "Новый график", "kind": "column"}],
        },
        "edit",
    )

    assert "изменений: 1" in reply
    assert "убрано старых виджетов: 2" in reply


def test_fallback_reply_mentions_cleared_widgets_and_titles():
    reply = main._fallback_job_reply(
        {
            "title": "Дашборд",
            "added": [1, 2, 3],
            "cleared": 4,
            "charts": [
                {"title": "Сдачи по проекту SimpleBashUtils по статусам", "kind": "pie"},
            ],
        },
        "edit",
    )

    assert "убрано старых виджетов: 4" in reply
    assert "SimpleBashUtils" in reply


def test_fallback_reply_without_charts_is_honest():
    reply = main._fallback_job_reply({"title": "X", "charts": []}, "generate")

    assert "не удалось" in reply


def test_summarize_handles_int_cleared_count(monkeypatch):
    class _ExplodingClient:
        async def chat(self, *args, **kwargs):
            raise RuntimeError("LLM down")

    monkeypatch.setattr(main, "get_llm_client", lambda: _ExplodingClient())

    reply = asyncio.run(
        main._summarize_job_result(
            {"title": "T", "added": [1], "cleared": 4, "charts": [{"title": "Pie", "kind": "pie"}]},
            "edit",
        )
    )

    assert "убрано старых виджетов: 4" in reply
    assert "Pie" in reply


def test_summarize_falls_back_when_llm_fails(monkeypatch):
    monkeypatch.setattr(main, "get_llm_client", lambda: _ExplodingClient())

    reply = asyncio.run(
        main._summarize_job_result(
            {"title": "T", "charts": [{"title": "Активность", "type": "line"}]},
            "generate",
        )
    )

    assert "Активность" in reply


def test_summarize_uses_llm_text_when_available(monkeypatch):
    class _FakeResponse:
        content = "Построил активность студентов: динамика за месяц и топ проектов."

    class _FakeClient:
        async def chat(self, *args, **kwargs):
            return _FakeResponse()

    monkeypatch.setattr(main, "get_llm_client", lambda: _FakeClient())

    reply = asyncio.run(
        main._summarize_job_result(
            {"title": "T", "charts": [{"title": "Активность", "type": "line"}]},
            "generate",
        )
    )

    assert reply.startswith("Построил")
