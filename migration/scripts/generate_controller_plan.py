from pathlib import Path
import re

files=[
Path("migration/backups/final_cleanup/student_controller/student_dbcontroller_before_remove.php"),
Path("migration/backups/dbcontroller_fix/teacher_dbcontroller.php")
]

methods={}

for file in files:

    text=file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    matches=re.findall(
        r'function\s+([a-zA-Z0-9_]+)\s*\((.*?)\)',
        text,
        re.S
    )

    for name,args in matches:
        methods[name]=args.strip()


print("\nMETHODS TO MERGE INTO CORE\n")

for name,args in sorted(methods.items()):
    print(f"""
{name}({args})
""")
