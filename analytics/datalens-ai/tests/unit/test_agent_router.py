"""Unit tests for the agent router prompt assembly (main._build_agent_messages)."""

from __future__ import annotations

import pytest
from pydantic import ValidationError

import main


def test_history_and_known_dashboards_reach_the_prompt():
    request = main.AgentMessageRequest(
        message="да",
        history=[
            main.HistoryMessage(role="user", content="построй дашборд по сабмитам"),
            main.HistoryMessage(role="assistant", content="Такой дашборд уже есть. Переделать?"),
        ],
        known_dashboards=[
            main.KnownDashboard(
                dashboard_id="abc123",
                title="Сабмиты",
                prompt="дай сабмиты",
                charts=["Распределение статусов"],
            ),
        ],
        has_dashboard=True,
    )

    system, user = main._build_agent_messages(request)

    assert "История диалога" in user.content
    assert "Переделать?" in user.content
    assert "Известные дашборды пользователя" in user.content
    assert "Распределение статусов" in user.content
    # The repeat-handling rules are part of the system prompt.
    assert "НЕ запускай новую генерацию" in system.content
    assert "replace_dashboard" in system.content


def test_empty_context_omits_optional_sections():
    request = main.AgentMessageRequest(message="Привет")

    _, user = main._build_agent_messages(request)

    assert "История диалога" not in user.content
    assert "Известные дашборды пользователя" not in user.content
    assert "Сообщение: Привет" in user.content


def test_history_and_dashboards_are_capped_by_validation():
    with pytest.raises(ValidationError):
        main.AgentMessageRequest(
            message="ок",
            history=[main.HistoryMessage(role="user", content=f"msg {i}") for i in range(11)],
        )
    with pytest.raises(ValidationError):
        main.AgentMessageRequest(
            message="ок",
            known_dashboards=[
                main.KnownDashboard(dashboard_id=f"d{i}", title=f"t{i}") for i in range(11)
            ],
        )