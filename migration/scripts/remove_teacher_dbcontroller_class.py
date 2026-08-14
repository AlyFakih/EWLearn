from pathlib import Path
import shutil

source = Path(
"frontend/pages/Teacher Dashboard/php/dbcontroller.php"
)

backup = Path(
"migration/backups/dbcontroller_fix/teacher_before_remove.php"
)

shutil.copy2(source, backup)

content = source.read_text(
    encoding="utf-8",
    errors="ignore"
)

start = content.find("<?php")

class_pos = content.find("class DBController")

if class_pos == -1:
    print("No class found")
    exit()

# keep only helper includes/functions before class
new_content = content[:class_pos]

source.write_text(
new_content,
encoding="utf-8"
)

print("Teacher DBController class removed")
