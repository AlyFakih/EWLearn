
from pathlib import Path
import re


root = Path("frontend/pages/Student Dashboard")


methods=set()


for file in root.rglob("*.php"):

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    found=re.findall(
        r"->([a-zA-Z0-9_]+)\(",
        text
    )

    for m in found:
        methods.add(m)


print("\nUSED DATABASE METHODS\n")
for m in sorted(methods):
    print(m)

