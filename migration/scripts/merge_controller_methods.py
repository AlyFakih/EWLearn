#!/usr/bin/env python3
"""
STEP 4.2 - Merge calendar.php and notifications.php methods into DBController.php
"""

import re
import shutil
import sys
from datetime import datetime
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[2]
CALENDAR_SRC = PROJECT_ROOT / "migration" / "backups" / "dbcontroller_fix" / "calendar.php"
NOTIFICATIONS_SRC = PROJECT_ROOT / "migration" / "backups" / "dbcontroller_fix" / "notifications.php"
DB_CONTROLLER = PROJECT_ROOT / "frontend" / "core" / "DBController.php"
BACKUP_DIR = PROJECT_ROOT / "migration" / "backups" / "dbcontroller_final"

METHODS_FROM_CALENDAR = [
    "createEvent", "updateEvent", "deleteEvent",
    "getEventsByDateRange", "getUpcomingEvents", "getEventById",
]

METHODS_FROM_NOTIFICATIONS = [
    "createNotification", "getUserNotifications", "countUnreadNotifications",
    "markAsRead", "markAllAsRead", "deleteNotification",
]


def extract_method(source_code: str, method_name: str) -> str:
    pattern = re.compile(
        r"(?:/\*\*.*?\*/\s*)?"
        r"public\s+function\s+" + re.escape(method_name) + r"\s*\([^)]*\)\s*\{",
        re.DOTALL,
    )
    match = pattern.search(source_code)
    if not match:
        raise ValueError(f"Could not find method '{method_name}' in source.")

    brace_start = match.end() - 1
    depth = 0
    i = brace_start
    for i in range(brace_start, len(source_code)):
        ch = source_code[i]
        if ch == "{":
            depth += 1
        elif ch == "}":
            depth -= 1
            if depth == 0:
                break
    else:
        raise ValueError(f"Unbalanced braces extracting '{method_name}'.")

    return source_code[match.start():i + 1]


def rewrite_db_handle_calls(method_code: str) -> str:
    return method_code.replace("$this->db_handle->", "$this->")


def indent(method_code: str, spaces: int = 4) -> str:
    pad = " " * spaces
    return "\n".join(pad + line if line.strip() else line for line in method_code.splitlines())


def main():
    if not CALENDAR_SRC.exists():
        sys.exit(f"ERROR: source file not found: {CALENDAR_SRC}")
    if not NOTIFICATIONS_SRC.exists():
        sys.exit(f"ERROR: source file not found: {NOTIFICATIONS_SRC}")
    if not DB_CONTROLLER.exists():
        sys.exit(f"ERROR: target file not found: {DB_CONTROLLER}")

    calendar_code = CALENDAR_SRC.read_text(encoding="utf-8")
    notifications_code = NOTIFICATIONS_SRC.read_text(encoding="utf-8")
    controller_code = DB_CONTROLLER.read_text(encoding="utf-8")

    already_present = []
    for name in METHODS_FROM_CALENDAR + METHODS_FROM_NOTIFICATIONS:
        if re.search(r"function\s+" + re.escape(name) + r"\s*\(", controller_code):
            already_present.append(name)
    if already_present:
        sys.exit(
            "ERROR: DBController.php already defines: "
            + ", ".join(already_present)
            + "\nAborting to avoid duplicate method fatal errors. "
              "Remove/rename them first, then re-run."
        )

    extracted_blocks = []
    report_lines = []

    for name in METHODS_FROM_CALENDAR:
        block = extract_method(calendar_code, name)
        block = rewrite_db_handle_calls(block)
        extracted_blocks.append(indent(block))
        report_lines.append(f"  - {name}  (from calendar.php)")

    for name in METHODS_FROM_NOTIFICATIONS:
        block = extract_method(notifications_code, name)
        block = rewrite_db_handle_calls(block)
        extracted_blocks.append(indent(block))
        report_lines.append(f"  - {name}  (from notifications.php)")

    BACKUP_DIR.mkdir(parents=True, exist_ok=True)
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_path = BACKUP_DIR / f"DBController_before_step4.2_{timestamp}.php"
    shutil.copy2(DB_CONTROLLER, backup_path)

    closing_tag_pos = controller_code.rfind("?>")
    search_region = controller_code if closing_tag_pos == -1 else controller_code[:closing_tag_pos]
    last_brace_pos = search_region.rfind("}")

    if last_brace_pos == -1:
        sys.exit("ERROR: could not find closing brace of DBController class.")

    insertion = (
        "\n    // ---- Merged from calendar.php / notifications.php (STEP 4.2) ----\n\n"
        + "\n\n".join(extracted_blocks)
        + "\n"
    )

    new_code = (
        controller_code[:last_brace_pos]
        + insertion
        + controller_code[last_brace_pos:]
    )

    DB_CONTROLLER.write_text(new_code, encoding="utf-8")

    print("STEP 4.2 merge complete.")
    print(f"Backup written to: {backup_path}")
    print("Methods merged into DBController.php:")
    print("\n".join(report_lines))
    print("\nNext: run `php -l frontend/core/DBController.php` to validate syntax.")


if __name__ == "__main__":
    main()
