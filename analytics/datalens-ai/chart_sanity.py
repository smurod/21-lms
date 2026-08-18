"""Chart-shape validation beyond SQL syntax and row availability."""

from __future__ import annotations

NumericTypes = {"integer", "float"}
DateTypes = {"date"}


def chart_shape_error(chart_type: str, columns: list[tuple[str, str]]) -> str | None:
    """Return a user-safe reason when fields do not fit a visualization."""
    normalized_type = (chart_type or "table").lower()
    normalized_columns = [(name, data_type.lower()) for name, data_type in columns]
    types = [data_type for _name, data_type in normalized_columns]

    if len(normalized_columns) < 2 and normalized_type != "table":
        return "Для графика нужны минимум категория/дата и числовая метрика."

    if normalized_type in {"line", "area"}:
        if not types or types[0] not in DateTypes:
            return "Line/area должен начинаться с даты или времени."
        if not any(data_type in NumericTypes for data_type in types[1:]):
            return "Line/area должен содержать числовую метрику после даты."

    if normalized_type in {"column", "bar", "pie"}:
        # Integer levels/IDs are valid categories (for example user_level 1/2).
        # For QL charts the last selected field is the metric; require it to be
        # numeric and allow any preceding field to be the category.
        if len(types) < 2 or types[-1] not in NumericTypes:
            return "Сравнительный график должен содержать категорию и числовую метрику."

    if normalized_type == "table" and types and all(data_type in NumericTypes for data_type in types):
        return (
            "Таблица из одних агрегированных чисел не является детализацией; "
            "нужна категория, дата или сущность."
        )

    return None
