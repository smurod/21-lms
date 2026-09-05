#!/usr/bin/env python3
"""Remove orphaned chart/dataset entries from the AI workbook.

An entry is an orphan when no dashboard in the workbook references it.
Demo dashboards and their charts are referenced entries and stay intact.

Run:  python scripts/cleanup_orphan_charts.py [--dry-run]
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from datalens_client import DataLensClient  # noqa: E402
from config import get_settings  # noqa: E402


def _list_entries(client: DataLensClient, workbook_id: str, scope: str) -> list[dict]:
    """List all workbook entries of a scope, following pagination."""
    entries: list[dict] = []
    page = 0
    while True:
        result = client.gateway(
            "us",
            "getEntries",
            {"workbookId": workbook_id, "scope": scope, "pageSize": 200, "page": page},
        )
        batch = result.get("entries", [])
        entries.extend(batch)
        if len(batch) < 200:
            return entries
        page += 1


def _collect_chart_ids(node: object, out: set[str]) -> None:
    """Recursively collect every chartId value regardless of nesting.

    Dashboards may reference charts from tabs, selectors, nested items — a
    structural parser misses some of them and would mark live demo charts as
    orphans, so the scan is deliberately brute-force.
    """
    if isinstance(node, dict):
        for key, value in node.items():
            if key == "chartId" and isinstance(value, str) and value:
                out.add(value)
            else:
                _collect_chart_ids(value, out)
    elif isinstance(node, list):
        for item in node:
            _collect_chart_ids(item, out)


def _used_chart_ids(client: DataLensClient, workbook_id: str) -> set[str]:
    """Collect every chartId referenced by any dashboard in the workbook."""
    used: set[str] = set()
    for dash in _list_entries(client, workbook_id, "dash"):
        try:
            dashboard = client.get_dashboard(dash["entryId"])
        except Exception as exc:
            print(f"  ! skip unreadable dashboard {dash['entryId']}: {exc}")
            continue
        _collect_chart_ids(dashboard, used)
    return used


def _delete_chart(client: DataLensClient, entry: dict) -> list[str]:
    """Delete one widget entry; return the dataset ids it consumed."""
    shared = entry.get("_shared") or {}
    dataset_ids = shared.get("datasetsIds") or []
    if dataset_ids:
        client.delete_wizard_chart(entry["entryId"])
        for dataset_id in dataset_ids:
            try:
                client.delete_dataset(dataset_id)
            except Exception:
                pass
    else:
        client.delete_ql_chart(entry["entryId"])
    return dataset_ids


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--dry-run", action="store_true", help="Only print what would be deleted")
    args = parser.parse_args()

    settings = get_settings()
    with DataLensClient(settings) as client:
        client.login()
        workbook = client.get_workbook_by_title(settings.datalens_workbook_title)
        workbook_id = workbook["workbookId"]
        print(f"Workbook: {workbook.get('title')} ({workbook_id})")

        used_chart_ids = _used_chart_ids(client, workbook_id)
        print(f"Charts referenced by dashboards: {len(used_chart_ids)}")

        widgets = _list_entries(client, workbook_id, "widget")
        print(f"Widget entries in workbook: {len(widgets)}")

        orphans = [w for w in widgets if w["entryId"] not in used_chart_ids]
        print(f"Orphan charts: {len(orphans)}")

        used_datasets: set[str] = set()
        for widget in widgets:
            if widget["entryId"] in used_chart_ids:
                shared = widget.get("data", {}).get("shared")
                shared = shared if isinstance(shared, dict) else None
                for dataset_id in (shared or {}).get("datasetsIds") or []:
                    used_datasets.add(dataset_id)

        if args.dry_run:
            for orphan in orphans:
                print(f"  would delete {orphan['entryId']} | {orphan.get('key', '')[:60]}")
            return 0

        removed = 0
        for orphan in orphans:
            try:
                _delete_chart(client, orphan)
                removed += 1
                print(f"  deleted {orphan['entryId']} | {orphan.get('key', '')[:60]}")
            except Exception as exc:
                print(f"  ! failed {orphan['entryId']}: {exc}")
        print(f"Deleted charts: {removed}/{len(orphans)}")

        datasets = _list_entries(client, workbook_id, "dataset")
        orphan_datasets = [d for d in datasets if d["entryId"] not in used_datasets]
        print(f"Orphan datasets: {len(orphan_datasets)}")
        removed_ds = 0
        for dataset in orphan_datasets:
            try:
                client.delete_dataset(dataset["entryId"])
                removed_ds += 1
                print(f"  deleted dataset {dataset['entryId']} | {dataset.get('key', '')[:50]}")
            except Exception as exc:
                print(f"  ! failed dataset {dataset['entryId']}: {exc}")
        print(f"Deleted datasets: {removed_ds}/{len(orphan_datasets)}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
