from pathlib import Path
import re


sources=[
Path("migration/backups/final_cleanup/student_controller/student_dbcontroller_before_remove.php"),
Path("migration/backups/dbcontroller_fix/teacher_dbcontroller.php")
]


wanted=[
"countUnreadNotifications",
"createNotification",
"createEvent",
"getUpcomingEvents",
"markAsRead",
"uploadFile",
"deleteNotification",
"getUserNotifications",
"updateEvent",
"deleteEvent"
]


for source in sources:

    if not source.exists():
        continue

    text=source.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    for method in wanted:

        pattern=r'(function\s+'+method+r'\s*\(.*?\{.*?\n\s*\})'

        found=re.search(
            pattern,
            text,
            re.S
        )

        if found:

            print("\n\n====================")
            print(method)
            print("====================")

            print(found.group(1))
