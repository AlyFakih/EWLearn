from pathlib import Path
import re

ROOT = Path("frontend")

METHODS = {
    "getMessage",
    "deleteEventsByReference",
    "formatFileSize",
    "sanitizeFilename",
    "createSystemNotification",
    "getErrorResponse",
    "scanForViruses",
}

for file in sorted(ROOT.rglob("*.php")):
    text = file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    for method in sorted(METHODS):
        pattern = rf'->\s*{re.escape(method)}\s*\('

        matches = list(re.finditer(pattern, text))

        if not matches:
            continue

        print()
        print("=" * 80)
        print(f"METHOD: {method}")
        print(f"FILE:   {file}")
        print("=" * 80)

        for match in matches:
            start = max(0, match.start() - 250)
            end = min(len(text), match.end() + 250)

            snippet = text[start:end]

            print(snippet)
            print("-" * 80)
