from pathlib import Path
import re

files = [
    Path("migration/backups/final_cleanup/student_controller/student_dbcontroller_before_remove.php"),
    Path("migration/backups/dbcontroller_fix/teacher_dbcontroller.php")
]

output = Path("migration/logs/old_controller_methods_dump.txt")

result=[]

for file in files:
    if not file.exists():
        continue

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    result.append("\n\n==============================")
    result.append(str(file))
    result.append("==============================\n")

    methods=re.findall(
        r'(function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{)',
        text
    )

    for m in methods:
        result.append(m)


output.write_text(
    "\n".join(result),
    encoding="utf-8"
)

print("Created:",output)
