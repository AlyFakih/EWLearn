from pathlib import Path
import re

controller = Path("frontend/core/DBController.php")

root = Path("frontend")

controller_text = controller.read_text(
    encoding="utf-8",
    errors="ignore"
)

available = set(
    re.findall(
        r"function\s+([a-zA-Z0-9_]+)",
        controller_text
    )
)

used=set()

for file in root.rglob("*.php"):

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    methods=re.findall(
        r"->([a-zA-Z0-9_]+)\(",
        text
    )

    for m in methods:
        used.add(m)


missing = sorted(
    used - available
)

print("\nAVAILABLE METHODS:")
for m in sorted(available):
    print(" OK ",m)


print("\nMISSING METHODS:")
for m in missing:
    print(" !! ",m)

print("\nTOTAL MISSING:",len(missing))
