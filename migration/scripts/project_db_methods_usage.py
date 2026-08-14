from pathlib import Path
import re

root=Path("frontend")

methods={}

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
        methods[m]=methods.get(m,0)+1


for method,count in sorted(
    methods.items(),
    key=lambda x:x[1],
    reverse=True
):
    print(f"{count:3}  {method}")
