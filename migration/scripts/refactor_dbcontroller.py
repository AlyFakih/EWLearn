from pathlib import Path


root = Path(
    "frontend/pages/Teacher Dashboard"
)


old_paths = [
    "php/dbcontroller.php",
    "../Student Dashboard/php/dbcontroller.php",
    "php/../dbcontroller.php"
]


new_import = "../../core/DBController.php"


changed=[]


for file in root.rglob("*.php"):

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )


    original=text


    for old in old_paths:

        if old in text:

            text=text.replace(
                old,
                new_import
            )


    if text != original:

        file.write_text(
            text,
            encoding="utf-8"
        )

        changed.append(
            str(file)
        )


print(
    "Changed files:"
)

for f in changed:
    print(f)


print(
    "Total:",
    len(changed)
)
