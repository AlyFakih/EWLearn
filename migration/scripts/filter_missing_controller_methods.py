from pathlib import Path
import re

controller = Path("frontend/core/DBController.php")

root = Path("frontend")

controller_text = controller.read_text(
    encoding="utf-8",
    errors="ignore"
)

available=set(
    re.findall(
        r"function\s+([a-zA-Z0-9_]+)",
        controller_text
    )
)


ignore = {
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
"close"
}


used=set()

for file in root.rglob("*.php"):

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )


    for m in re.findall(
        r"\$[a-zA-Z0-9_]+->([a-zA-Z0-9_]+)\(",
        text
    ):
        used.add(m)



missing=sorted(
    used - available - ignore
)


print("\nREAL MISSING METHODS\n")

for m in missing:
    print(m)


print("\nTOTAL:",len(missing))
