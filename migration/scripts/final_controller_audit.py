from pathlib import Path
import re
from collections import Counter

CORE = Path("frontend/core/DBController.php")
ROOT = Path("frontend")

core_text = CORE.read_text(
    encoding="utf-8",
    errors="ignore"
)

# Methods actually implemented by the new core controller
implemented = set(
    re.findall(
        r'\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(',
        core_text
    )
)

# Methods called through -> across the application
usage = Counter()

for file in ROOT.rglob("*.php"):
    if file.resolve() == CORE.resolve():
        continue

    text = file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    for method in re.findall(
        r'->([A-Za-z_][A-Za-z0-9_]*)\s*\(',
        text
    ):
        usage[method] += 1

# Methods that are definitely missing from DBController
missing = {
    method: count
    for method, count in usage.items()
    if method not in implemented
}

# Ignore methods that obviously belong to mysqli/PHP/native objects.
native = {
    "bind_param",
    "fetch_assoc",
    "fetch_all",
    "get_result",
    "prepare",
    "execute",
    "commit",
    "rollback",
    "begin_transaction",
    "set_charset",
    "close",
    "query",
    "connect",
    "connectDB",
    "uploadFile",
}

real_missing = {
    method: count
    for method, count in missing.items()
    if method not in native
}

print("=" * 70)
print("DBCONTROLLER IMPLEMENTED METHODS")
print("=" * 70)

for method in sorted(implemented):
    print(f"OK   {method}")

print()
print("=" * 70)
print("REMAINING NON-NATIVE METHODS")
print("=" * 70)

if real_missing:
    for method, count in sorted(
        real_missing.items(),
        key=lambda x: (-x[1], x[0])
    ):
        print(f"MISSING {count:3}x  {method}")
else:
    print("NONE")

print()
print("=" * 70)
print("TOTALS")
print("=" * 70)
print(f"Implemented methods : {len(implemented)}")
print(f"Missing methods     : {len(real_missing)}")

Path(
    "migration/logs/final_controller_audit.txt"
).write_text(
    "\n".join(
        f"{count:3}x {method}"
        for method, count in sorted(
            real_missing.items(),
            key=lambda x: (-x[1], x[0])
        )
    ),
    encoding="utf-8"
)

print()
print("Audit saved to:")
print("migration/logs/final_controller_audit.txt")
