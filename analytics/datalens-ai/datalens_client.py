"""
DataLens REST client for the local open-source DataLens instance.

The client does three important things:
1. Logs in as admin/admin and stores auth cookies.
2. Sends commands to the DataLens gateway.
3. Finds/creates the "AI Generated" workbook, configured database connection, and QL charts.
"""

from __future__ import annotations

import asyncio
import json
import logging
import re
import time
from typing import Any

import httpx

from config import Settings, get_settings

logger = logging.getLogger(__name__)


class DataLensError(RuntimeError):
    """Raised when DataLens returns an error response."""


class DataLensClient:
    def __init__(self, settings: Settings | None = None):
        self.settings = settings or get_settings()
        self._client = httpx.Client(
            base_url=self.settings.datalens_base_url.rstrip("/"),
            timeout=self.settings.datalens_timeout,
            follow_redirects=True,
        )

    def close(self) -> None:
        self._client.close()

    def __enter__(self) -> "DataLensClient":
        return self

    def __exit__(self, *args: object) -> None:
        self.close()

    # ------------------------------------------------------------
    # Authentication
    # ------------------------------------------------------------

    def login(self) -> dict[str, str]:
        """Log in using DataLens auth service and save cookies in the client."""
        auth_base = self.settings.datalens_auth_url.rstrip("/")
        payload = {
            "login": self.settings.datalens_username,
            "password": self.settings.datalens_password,
        }

        response = httpx.post(
            f"{auth_base}/signin",
            json=payload,
            timeout=30.0,
            follow_redirects=False,
        )

        if response.status_code not in (200, 204):
            raise DataLensError(
                f"DataLens login failed: HTTP {response.status_code}, {response.text[:500]}"
            )

        # Copy all cookies from auth response into the gateway HTTP client.
        for cookie in response.cookies.jar:
            self._client.cookies.set(
                cookie.name,
                cookie.value,
                domain="localhost",
                path="/",
            )

        logger.info("Logged into DataLens as %s", self.settings.datalens_username)
        return {cookie.name: cookie.value for cookie in self._client.cookies.jar}

    # ------------------------------------------------------------
    # Generic gateway call
    # ------------------------------------------------------------

    def gateway(
        self,
        service: str,
        action: str,
        body: dict[str, Any] | None = None,
        headers: dict[str, str] | None = None,
    ) -> Any:
        """
        Call DataLens gateway: POST /gateway/root/<service>/<action>.

        Even read operations use POST in DataLens gateway. The request body
        contains the arguments expected by that action.
        """
        url = f"/gateway/root/{service}/{action}"
        response = self._client.post(url, json=body or {}, headers=headers or {})

        if response.status_code >= 400:
            raise DataLensError(
                f"Gateway call failed: {service}.{action} -> "
                f"HTTP {response.status_code}, {response.text[:1000]}"
            )

        if not response.content:
            return None

        return response.json()

    # ------------------------------------------------------------
    # Workbooks
    # ------------------------------------------------------------

    def get_workbook_by_title(self, title: str | None = None) -> dict[str, Any] | None:
        """Find a workbook by title. Returns None when not found."""
        wanted = title or self.settings.datalens_workbook_title
        data = self.gateway(
            "us",
            "getWorkbooksList",
            {"page": 0, "pageSize": 100, "filterString": wanted},
        )

        for workbook in data.get("workbooks", []):
            if workbook.get("title") == wanted:
                return workbook
        return None

    def ensure_workbook(self, title: str | None = None) -> dict[str, Any]:
        """Find workbook, or create it if it does not exist."""
        wanted = title or self.settings.datalens_workbook_title
        existing = self.get_workbook_by_title(wanted)
        if existing:
            logger.info("Using existing workbook: %s", wanted)
            return existing

        logger.info("Creating workbook: %s", wanted)
        created = self.gateway(
            "us",
            "createWorkbook",
            {"collectionId": None, "title": wanted, "description": "Created by datalens-ai"},
        )
        return created.get("workbook", created)

    # ------------------------------------------------------------
    # Connections
    # ------------------------------------------------------------

    def create_database_connection(
        self,
        *,
        name: str,
        workbook_id: str,
        connection_type: str | None = None,
        host: str | None = None,
        port: int | None = None,
        db_name: str | None = None,
        username: str | None = None,
        password: str | None = None,
    ) -> str:
        """Create a configured DataLens database connection and return its ID.

        DataLens receives its connection type from `DATALENS_DB_TYPE`. The
        caller validates AI analyzer support before reaching this API call.
        """
        s = self.settings
        resolved_type = (connection_type or s.datalens_db_type).strip().lower()
        if resolved_type == "postgresql":
            resolved_type = "postgres"
        if not resolved_type:
            raise DataLensError("DATALENS_DB_TYPE must not be empty.")
        payload = {
            "name": name,
            "type": resolved_type,
            "host": host or s.datalens_db_host,
            "port": port or s.datalens_db_port,
            "db_name": db_name or s.datalens_db_name,
            "username": username or s.datalens_db_user,
            "password": password or s.datalens_db_password,
            # control-api expects snake_case workbook_id in the body.
            # Without it, connection goes to root folder, not into workbook.
            "workbook_id": workbook_id,
            # QL charts run raw SQL. This matches the working 21_lms_connection.
            "raw_sql_level": "dashsql",
        }

        result = self.gateway("bi", "createConnection", payload)

        connection_id = result.get("id") or result.get("connectionId")
        if not connection_id:
            raise DataLensError(f"Connection was created, but no ID returned: {result}")

        logger.info("Created DataLens %s connection %s: %s", resolved_type, connection_id, name)
        return connection_id

    def create_postgres_connection(self, **kwargs: Any) -> str:
        """Backward-compatible helper for existing smoke scripts.

        New application code must use :meth:`create_database_connection` and
        pass the configured connection type explicitly.
        """
        return self.create_database_connection(connection_type="postgres", **kwargs)

    def delete_connection(self, connection_id: str) -> Any:
        """Delete a DataLens connection by ID."""
        return self.gateway("bi", "deleteConnection", {"connectionId": connection_id})

    # ------------------------------------------------------------
    # QL charts
    # ------------------------------------------------------------

    def create_ql_chart(
        self,
        *,
        name: str,
        workbook_id: str,
        data: dict[str, Any],
    ) -> str:
        """
        Create a QL chart through the charts engine.

        `data` is the full chart.shared object built by ql_chart_builder.
        Returns the new chart entryId.
        """
        # In one AI run all charts are created in the same second, so a plain
        # timestamp-based title can collide. Add a short unique suffix.
        unique_name = f"{name} {int(time.time() * 1000) % 100000:05d}"
        payload = {
            "template": "ql",
            "data": data,
            "workbookId": workbook_id,
            "name": unique_name,
            "key": None,
        }

        response = self._client.post("/api/charts/v1/charts", json=payload)
        if response.status_code >= 400:
            raise DataLensError(
                "Create QL chart failed: "
                f"HTTP {response.status_code}, {response.text[:1000]}"
            )

        result = response.json() if response.content else {}
        entry_id = result.get("entryId") or result.get("id") or result.get("chartId")
        if not entry_id:
            raise DataLensError(
                f"Chart response has no entryId: {response.text[:1000]}"
            )
        return entry_id

    def delete_ql_chart(self, entry_id: str) -> None:
        """Delete a chart entry through the charts engine."""
        response = self._client.delete(f"/api/charts/v1/charts/{entry_id}")
        if response.status_code >= 400:
            raise DataLensError(
                "Delete QL chart failed: "
                f"HTTP {response.status_code}, {response.text[:1000]}"
            )

    def create_dashboard(
        self,
        *,
        name: str,
        workbook_id: str,
        data: dict[str, Any],
    ) -> str:
        """Create a dashboard using mix.createDashboardV1. Returns dashboard entryId."""
        payload = {
            "entry": {
                "workbookId": workbook_id,
                "name": name,
                "data": data,
                "meta": {},
            },
            "mode": "publish",
        }
        result = self.gateway("mix", "createDashboardV1", payload)
        entry = result.get("entry", result)
        dash_id = entry.get("entryId") or entry.get("id")
        if not dash_id:
            raise DataLensError(f"Dashboard response has no entryId: {result}")
        return dash_id

    # ------------------------------------------------------------
    # QL chart reading and updating
    # ------------------------------------------------------------

    def get_ql_chart(self, chart_id: str) -> dict[str, Any]:
        """Fetch a QL chart entry with shared config."""
        result = self.gateway(
            "us",
            "getEntries",
            {"scope": "widget", "ids": [chart_id], "includeData": True},
        )
        entries = result.get("entries", [])
        if not entries:
            raise DataLensError(f"Chart {chart_id} not found")
        entry = entries[0]
        raw_shared = entry.get("data", {}).get("shared")
        entry["_shared"] = (
            json.loads(raw_shared) if isinstance(raw_shared, str) else raw_shared
        )
        return entry

    def update_ql_chart(self, chart_id: str, data: dict[str, Any]) -> None:
        """Update an existing QL chart by ID."""
        response = self._client.post(
            f"/api/charts/v1/charts/{chart_id}",
            json={"template": "ql", "data": data, "mode": "publish"},
        )
        if response.status_code >= 400:
            raise DataLensError(
                "Update QL chart failed: "
                f"HTTP {response.status_code}, {response.text[:1000]}"
            )

    # ------------------------------------------------------------
    # Dashboard reading and updating
    # ------------------------------------------------------------

    def get_dashboard(self, dashboard_id: str) -> dict[str, Any]:
        """Fetch a dashboard entry with full data."""
        return self.gateway(
            "mix",
            "getDashboardV1",
            {"dashboardId": dashboard_id, "includePermissions": True},
        )

    def _us_url(self, entry_id: str) -> str:
        return f"{self.settings.datalens_us_url.rstrip('/')}/v1/locks/{entry_id}"

    def force_release_entry_lock(self, entry_id: str) -> None:
        """Release a stale local UI lock through the direct US lock endpoint."""
        try:
            response = self._client.delete(
                self._us_url(entry_id),
                params={"force": "true"},
            )
            if response.status_code in (200, 204, 404):
                return
        except httpx.HTTPError:
            pass

        # Backward-compatible fallback for deployments where US is not exposed.
        for action in ("unlockEntry", "deleteLock"):
            try:
                self.gateway("us", action, {"entryId": entry_id, "force": True})
                return
            except DataLensError:
                continue

    def update_dashboard(self, dashboard_id: str, data: dict[str, Any]) -> None:
        """Replace dashboard data and publish.

        mix.updateDashboardV1 refuses to write (HTTP 423) while ANY US entry
        lock exists, so callers must force-release stale locks beforehand and
        must NOT hold their own lock across this call.
        """
        entry = {
            "entryId": dashboard_id,
            "data": data,
            "meta": {},
        }

        payload = {
            "entry": entry,
            "mode": "publish",
        }
        self.gateway("mix", "updateDashboardV1", payload)

    def render_chart(self, entry_id: str) -> dict[str, Any]:
        """Render one chart server-side via POST /api/run.

        Used for the pre-publication render validation: the returned payload
        contains the fully built chart config (series/rows) or an error.
        """
        response = self._client.post("/api/run", json={"id": entry_id})
        if response.status_code >= 400:
            raise DataLensError(
                f"render /api/run -> HTTP {response.status_code}, {response.text[:300]}"
            )
        return response.json()

    def dashboard_embed_url(self, dashboard_id: str, workbook_id: str) -> str:
        """Return the direct DataLens URL for one dashboard.

        DataLens resolves the entry by the 13-character entry ID at the start
        of a public dashboard URL. The trailing slug is for readability. The
        internal `key` is not a URL slug: it may contain collection IDs and
        slashes, therefore it must never be used in a browser URL.
        """
        dashboard = self.get_dashboard(dashboard_id)
        entry = dashboard.get("entry", dashboard)
        name = str(entry.get("name") or "ai-analytics-dashboard")
        slug = re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-")
        slug = slug or "dashboard"
        base_url = self.settings.datalens_base_url.rstrip("/")

        return f"{base_url}/{dashboard_id}-{slug}"

    # ------------------------------------------------------------
    # Dataset (wizard pipeline)
    # ------------------------------------------------------------

    def create_dataset(
        self,
        *,
        name: str,
        workbook_id: str,
        connection_id: str,
        result_schema: list[dict[str, Any]],
        source_id: str,
        avatar_id: str,
        sql: str,
        raw_schema: list[dict[str, Any]],
    ) -> str:
        """Create a DataLens dataset with a Custom SQL source.

        Returns the new dataset entryId.
        """
        payload = {
            "dataset": {
                "sources": [
                    {
                        "id": source_id,
                        "connection_id": connection_id,
                        "title": "Custom SQL",
                        "source_type": "PG_SUBSELECT",
                        "managed_by": "user",
                        "valid": True,
                        "parameters": {"subsql": sql},
                        "raw_schema": raw_schema,
                    }
                ],
                "source_avatars": [
                    {
                        "id": avatar_id,
                        "source_id": source_id,
                        "title": "Custom SQL",
                        "is_root": True,
                        "managed_by": "user",
                        "valid": True,
                    }
                ],
                "avatar_relations": [],
                "result_schema": result_schema,
                "rls": {},
                "filters": [],
                "component_errors": {"items": []},
                "obligatory_filters": [],
            },
            "workbook_id": workbook_id,
            "name": name,
        }
        result = self.gateway("bi", "createDataset", payload)
        dataset_id = (
            result.get("id")
            or result.get("datasetId")
            or (result.get("dataset") or {}).get("id")
        )
        if not dataset_id:
            raise DataLensError(f"Dataset created but no ID returned: {result}")
        logger.info("Created dataset %s: %s", dataset_id, name)
        return dataset_id

    def delete_dataset(self, dataset_id: str) -> None:
        """Delete a DataLens dataset."""
        try:
            self.gateway("bi", "deleteDataset", {"datasetId": dataset_id})
            logger.info("Deleted dataset %s", dataset_id)
        except DataLensError as exc:
            logger.warning("Could not delete dataset %s: %s", dataset_id, exc)

    # ------------------------------------------------------------
    # Wizard charts (metric / pie / flatTable)
    # ------------------------------------------------------------

    def create_wizard_chart(
        self,
        *,
        name: str,
        workbook_id: str,
        chart_type: str,
        shared: dict[str, Any],
    ) -> str:
        """Create a wizard chart (metric_wizard_node / graph_wizard_node / table_wizard_node).

        chart_type: 'metric' | 'pie' | 'donut' | 'column' | 'bar' |
                    'line' | 'area' | 'flatTable'
        Returns the new chart entryId.
        """
        node_type_map = {
            "metric":    "metric_wizard_node",
            "pie":       "graph_wizard_node",
            "donut":     "graph_wizard_node",
            "column":    "graph_wizard_node",
            "bar":       "graph_wizard_node",
            "line":      "graph_wizard_node",
            "area":      "graph_wizard_node",
            "flatTable": "table_wizard_node",
        }
        node_type = node_type_map.get(chart_type, "graph_wizard_node")

        unique_name = f"{name} {int(time.time() * 1000) % 100000:05d}"
        # The UI of this DataLens build creates editor charts through
        # mix.createEditorChart with {name, workbookId, type, data, annotation};
        # mix.createChartV1 does not exist here (HTTP 404 UNKNOWN_SERVICE_ACTION).
        # shared must be a serialized JSON string, exactly like the UI sends it.
        payload = {
            "name": unique_name,
            "workbookId": workbook_id,
            "type": node_type,
            "data": {"shared": json.dumps(shared) if not isinstance(shared, str) else shared},
            "annotation": None,
        }
        result = self.gateway("mix", "createEditorChart", payload)
        entry = result.get("entry", result)
        entry_id = entry.get("entryId") or entry.get("id")
        if not entry_id:
            raise DataLensError(f"Wizard chart has no entryId: {result}")
        logger.info("Created wizard chart %s (%s): %s", entry_id, node_type, name)
        return entry_id

    def delete_wizard_chart(self, entry_id: str) -> None:
        """Delete a wizard chart entry."""
        try:
            self._delete_us_entry(entry_id)
            logger.info("Deleted wizard chart %s", entry_id)
        except DataLensError as exc:
            logger.warning("Could not delete wizard chart %s: %s", entry_id, exc)

    def update_wizard_chart(self, entry_id: str, shared: dict[str, Any]) -> None:
        """Update a wizard chart entry via the US REST API.

        The QL charts endpoint (POST /api/charts/v1/charts/{id}) only serves
        QL entries; a wizard chart updates through POST /v1/entries/{id}.
        """
        payload = {
            "data": {"shared": json.dumps(shared) if not isinstance(shared, str) else shared},
            "mode": "publish",
        }
        response = httpx.post(
            f"{self.settings.datalens_us_url.rstrip('/')}/v1/entries/{entry_id}",
            json=payload,
            cookies=self._client.cookies,
            timeout=self.settings.datalens_timeout,
        )
        if response.status_code >= 400:
            raise DataLensError(
                f"Update wizard chart {entry_id} failed: "
                f"HTTP {response.status_code}, {response.text[:300]}"
            )

    def _delete_us_entry(self, entry_id: str) -> Any:
        """DELETE {US_URL}/v1/entries/{entryId} — the US REST route in this build.

        The gateway action us._deleteUSEntry does not exist here (HTTP 404
        UNKNOWN_SERVICE_ACTION), and the UI server (:8085) has no DELETE
        /v1/entries route — the US service (:3030) does, so the call goes
        there directly with the session cookies.
        """
        response = httpx.delete(
            f"{self.settings.datalens_us_url.rstrip('/')}/v1/entries/{entry_id}",
            cookies=self._client.cookies,
            timeout=self.settings.datalens_timeout,
        )
        if response.status_code >= 400:
            raise DataLensError(
                f"Delete entry {entry_id} failed: "
                f"HTTP {response.status_code}, {response.text[:300]}"
            )
        return response.json() if response.content else None

    # ------------------------------------------------------------
    # Generic entries
    # ------------------------------------------------------------

    def delete_entry(self, entry_id: str, scope: str) -> Any:
        """Delete a US entry by ID and scope."""
        return self._delete_us_entry(entry_id)


class AsyncDataLensClient:
    """Async bridge over the sync DataLensClient.

    Every method call runs in a worker thread via ``asyncio.to_thread``, so
    the event loop keeps serving SSE progress and parallel jobs while DataLens
    gateway requests are in flight (dozens of 50–500 ms calls per job).

    Usage: keep the sync client for lifecycle (login/context manager), wrap it
    once, and ``await`` every call from async pipeline code.
    """

    def __init__(self, client: DataLensClient):
        self._sync = client

    def __getattr__(self, name: str):
        attr = getattr(self._sync, name)
        if not callable(attr):
            return attr

        async def call(*args: Any, **kwargs: Any):
            return await asyncio.to_thread(attr, *args, **kwargs)

        return call
