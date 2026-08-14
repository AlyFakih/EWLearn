from pathlib import Path

files = [
    Path("frontend/pages/Student Dashboard/php/dbcontroller.php"),
    Path("frontend/pages/common/calendar.php"),
    Path("frontend/pages/common/notifications.php")
]


for file in files:

    content = file.read_text(
        encoding="utf-8",
        errors="ignore"
    )

    old = "class DBController"
    new = "class StudentDBController"

    if old in content:
        content = content.replace(old,new)

    old2 = "new DBController()"
    new2 = "new StudentDBController()"

    content = content.replace(old2,new2)

    file.write_text(
        content,
        encoding="utf-8"
    )

    print("Updated:",file)
