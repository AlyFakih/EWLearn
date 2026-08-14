from pathlib import Path
import re
import shutil

CORE = Path("frontend/core/DBController.php")

SOURCES = [
    Path("migration/backups/dbcontroller_fix/calendar.php"),
    Path("migration/backups/dbcontroller_fix/notifications.php"),
]

METHODS = [
    "createEvent",
    "updateEvent",
    "deleteEvent",
    "getEventsByDateRange",
    "getUpcomingEvents",
    "getEventById",
    "createNotification",
    "getUserNotifications",
    "countUnreadNotifications",
    "markAsRead",
    "markAllAsRead",
    "deleteNotification",
]


def extract_method(text, method_name):
    """
    Extract a PHP method using brace balancing.
    This avoids fragile regex matching of nested PHP blocks.
    """

    pattern = re.compile(
        rf'(?:public|protected|private)?\s*function\s+{re.escape(method_name)}\s*\(',
        re.MULTILINE
    )

    match = pattern.search(text)

    if not match:
        return None

    start = match.start()

    brace_start = text.find("{", match.end())

    if brace_start == -1:
        raise RuntimeError(
            f"Opening brace not found for method {method_name}"
        )

    depth = 0
    in_single = False
    in_double = False
    escaped = False

    i = brace_start

    while i < len(text):
        char = text[i]

        if escaped:
            escaped = False
            i += 1
            continue

        if char == "\\":
            escaped = True
            i += 1
            continue

        if char == "'" and not in_double:
            in_single = not in_single
            i += 1
            continue

        if char == '"' and not in_single:
            in_double = not in_double
            i += 1
            continue

        if not in_single and not in_double:

            if char == "{":
                depth += 1

            elif char == "}":
                depth -= 1

                if depth == 0:
                    return text[start:i + 1].strip()

        i += 1

    raise RuntimeError(
        f"Unbalanced braces while extracting {method_name}"
    )


core_text = CORE.read_text(
    encoding="utf-8",
    errors="ignore"
)

# Make absolutely sure we are modifying the expected class.
if "class DBController" not in core_text:
    raise RuntimeError(
        "frontend/core/DBController.php does not contain class DBController"
    )

# Collect methods from backup sources.
extracted = {}

for source in SOURCES:

    if not source.exists():
        raise FileNotFoundError(
            f"Required backup source missing: {source}"
        )

    source_text = source.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    for method in METHODS:

        if method in extracted:
            continue

        block = extract_method(
            source_text,
            method
        )

        if block:
            extracted[method] = block

missing = [
    method
    for method in METHODS
    if method not in extracted
]

if missing:
    raise RuntimeError(
        "Could not extract required methods:\n"
        + "\n".join(missing)
    )

# Detect methods already present in the core controller.
existing = set(
    re.findall(
        r'function\s+([A-Za-z0-9_]+)\s*\(',
        core_text
    )
)

to_add = [
    method
    for method in METHODS
    if method not in existing
]

already_present = [
    method
    for method in METHODS
    if method in existing
]

if not to_add:
    print("No methods needed to be added.")
    print("All requested methods already exist.")
    raise SystemExit(0)

# Insert methods immediately before the final class closing brace.
# The core controller currently has no namespace and one DBController class.
last_brace = core_text.rfind("}")

if last_brace == -1:
    raise RuntimeError(
        "Could not find final closing brace in DBController.php"
    )

methods_text = "\n\n"

for method in to_add:
    methods_text += (
        "    "
        + extracted[method].replace("\n", "\n    ")
        + "\n\n"
    )

new_core = (
    core_text[:last_brace]
    + methods_text
    + core_text[last_brace:]
)

# Safety checks before writing.
if new_core.count("class DBController") != 1:
    raise RuntimeError(
        "Safety check failed: unexpected DBController class count."
    )

for method in METHODS:
    count = len(
        re.findall(
            rf'function\s+{re.escape(method)}\s*\(',
            new_core
        )
    )

    if count != 1:
        raise RuntimeError(
            f"Safety check failed: {method} appears {count} times."
        )

CORE.write_text(
    new_core,
    encoding="utf-8"
)

print()
print("========================================")
print("STEP 4.2 MERGE COMPLETE")
print("========================================")
print()

print("ADDED METHODS:")
for method in to_add:
    print("  +", method)

print()
print("ALREADY PRESENT:")
for method in already_present:
    print("  =", method)

print()
print("TOTAL CORE METHODS NOW:",
      len(re.findall(
          r'function\s+[A-Za-z0-9_]+\s*\(',
          new_core
      )))

