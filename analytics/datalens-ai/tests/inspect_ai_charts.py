"""Inspect existing AI chart SQL and run it against the database."""

from __future__ import annotations

import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

import json

from config import get_settings
from datalens_client import DataLensClient

CHARTS = [
    ("jdb0yloq5ro82", "line"),
    ("60ynl8ejoq7ep", "column"),
    ("nhf42pxe36ls6", "column"),
]


def main() -> None:
    settings = get_settings()
    with DataLensClient(settings) as client:
        client.login()
        result = client.gateway(
            "us",
            "getEntries",
            {"scope": "widget", "ids": [c[0] for c in CHARTS], "includeData": True},
        )

        for entry in result.get("entries", []):
            print("\n===", entry.get("entryId"), entry.get("key"))
            raw = entry.get("data", {}).get("shared")
            shared = json.loads(raw) if isinstance(raw, str) else raw
            print("type:", shared.get("visualization", {}).get("id"))
            print("sql:\n", shared.get("queryValue"))


if __name__ == "__main__":
    main()
