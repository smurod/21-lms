# Объекты DataLens и работа с ними

Сверено с `datalens_client.py`, `ql_chart_builder.py`, `dashboard_builder.py` **2026-08-14**.

## Connection

Создаётся через gateway `bi.createConnection`.

Тело, которое реально шлёт код:

```json
{
  "name": "AI Connection <timestamp>",
  "type": "postgres",
  "host": "<DATALENS_DB_HOST>",
  "port": 5432,
  "db_name": "<DATALENS_DB_NAME>",
  "username": "<DATALENS_DB_USER>",
  "password": "<DATALENS_DB_PASSWORD>",
  "workbook_id": "<13-char id>",
  "raw_sql_level": "dashsql"
}
```

Важно:

- workbook задаётся полем **`workbook_id`** (snake_case) в теле, не заголовком;
- без `raw_sql_level=dashsql` QL-чарты отвечают `DashSQL API is disallowed`;
- пароль при чтении через API не возвращается;
- boolean-поля connection лучше не слать как JSON `true`/`false`.

Удаление: `bi.deleteConnection` с `{"connectionId": "..."}`.

## QL chart

Код **не** вызывает `mix.__createQLChart__`.

Создание:

```text
POST {DATALENS_BASE_URL}/api/charts/v1/charts
```

```json
{
  "template": "ql",
  "data": { "...shared object from ql_chart_builder..." },
  "workbookId": "...",
  "name": "<title> <msec suffix>",
  "key": null
}
```

Обновление:

```text
POST /api/charts/v1/charts/{chart_id}
{"template": "ql", "data": {...}, "mode": "publish"}
```

Удаление:

```text
DELETE /api/charts/v1/charts/{chart_id}
```

Чтение: gateway `us.getEntries` (`scope=widget`, `ids`, `includeData=true`). Поле `data.shared` часто приходит строкой JSON; клиент кладёт разобранный объект в `_shared`.

Особенности builder:

- `datasetId` = `ql-mocked-dataset`;
- первый столбец line — ось X;
- у column на Y идёт **последнее числовое** поле;
- table — все колонки в `flat-table-columns`;
- `axisModeMap` строится по реальным именам полей.

Поддерживаемые шаблоны: только `line`, `column`, `table`.

## Dashboard

Создание: `mix.createDashboardV1`

```json
{
  "entry": {
    "workbookId": "...",
    "name": "...",
    "data": { "...dashboard JSON..." },
    "meta": {}
  },
  "mode": "publish"
}
```

Чтение: `mix.getDashboardV1` с `{"dashboardId": "...", "includePermissions": true}`.

Обновление: `mix.updateDashboardV1` — `entryId` **внутри** `entry`:

```json
{
  "entry": {
    "entryId": "...",
    "data": { ... },
    "meta": {}
  },
  "mode": "publish"
}
```

## Layout

- сетка 36 колонок;
- `line` и `table`: `w=36 h=20`;
- `column`: `w=18 h=16`;
- секция: title-виджет + опциональный note;
- у каждого item свой `id`, та же строка в `layout[].i`;
- коллизия id → DataLens `Duplicated id`.
