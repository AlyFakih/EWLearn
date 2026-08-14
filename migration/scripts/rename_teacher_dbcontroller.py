from pathlib import Path

file = Path(
"frontend/pages/Teacher Dashboard/php/dbcontroller.php"
)

content = file.read_text(
    encoding="utf-8",
    errors="ignore"
)

content = content.replace(
"class DBController",
"class TeacherDBController"
)

content = content.replace(
"new DBController()",
"new TeacherDBController()"
)

file.write_text(
content,
encoding="utf-8"
)

print("Teacher DBController renamed")
