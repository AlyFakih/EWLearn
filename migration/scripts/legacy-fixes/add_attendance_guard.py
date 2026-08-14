file = "frontend/pages/Teacher Dashboard/attendence-dashboard.php"
with open(file, "r", encoding="utf-8") as f:
    content = f.read()

old = "<?php\nerror_reporting(E_ALL);"

guard = """<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'instructor') {
    header("Location: ../../login.php");
    exit();
}
error_reporting(E_ALL);"""

if old in content:
    content = content.replace(old, guard)
    print("Replaced")
else:
    print("Pattern not found - check current content")

with open(file, "w", encoding="utf-8") as f:
    f.write(content)
