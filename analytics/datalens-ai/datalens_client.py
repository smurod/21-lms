"""
DataLens REST client for the local open-source DataLens instance.

The client does three important things:
1. Logs in as admin/admin and stores auth cookies.
2. Sends commands to the DataLens gateway.
3. Finds/creates the "AI Generated" workbook, configured database connection, and QL charts.
"""

from __future__ import annotations

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

    def acquire_entry_lock(self, entry_id: str, *, duration: int = 30) -> str:
        """Force-release an old UI lock, then acquire a short update lock.

        A short TTL limits the impact if a local US version does not expose a
        compatible release route. Dashboard publication itself is immediate.
        """
        self.force_release_entry_lock(entry_id)

        try:
            response = self._client.post(
                self._us_url(entry_id),
                json={"duration": duration, "force": True},
            )
            if response.status_code < 400:
                token = (response.json() or {}).get("lockToken")
                if token:
                    return str(token)
            direct_error = f"HTTP {response.status_code}, {response.text[:500]}"
        except httpx.HTTPError as exc:
            direct_error = str(exc)

        last_error: Exception | str = direct_error
        for action in ("lockEntry", "createLock"):
            try:
                result = self.gateway(
                    "us",
                    action,
                    {"entryId": entry_id, "duration": duration, "force": True},
                )
                token = (result or {}).get("lockToken")
                if token:
                    return str(token)
            except DataLensError as exc:
                last_error = exc

        raise DataLensError(f"Could not acquire dashboard lock for {entry_id}: {last_error}")

    def release_entry_lock(self, entry_id: str, lock_token: str) -> None:
        """Release the short lock acquired for an AI dashboard update."""
        try:
            response = self._client.delete(
                self._us_url(entry_id),
                params={"lockToken": lock_token},
            )
            if response.status_code in (200, 204):
                return
        except httpx.HTTPError:
            pass

        for action in ("unlockEntry", "deleteLock"):
            try:
                self.gateway("us", action, {"entryId": entry_id, "lockToken": lock_token})
                return
            except DataLensError:
                continue

        logger.warning("Could not release dashboard lock: %s", entry_id)

    def update_dashboard(self, dashboard_id: str, data: dict[str, Any], lock_token: str | None = None) -> None:
        """Replace dashboard data, optionally holding a US entry lock."""
        entry = {
            "entryId": dashboard_id,
            "data": data,
            "meta": {},
        }
        if lock_token:
            entry["lockToken"] = lock_token

        payload = {
            "entry": entry,
            "mode": "publish",
        }
        self.gateway("mix", "updateDashboardV1", payload)

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
    # Generic entries
    # ------------------------------------------------------------

    def delete_entry(self, entry_id: str, scope: str) -> Any:
        """Delete a US entry by ID and scope."""
        return self.gateway(
            "us",
            "_deleteUSEntry",
            {"entryId": entry_id, "scope": scope},
        )
