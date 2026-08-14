from pathlib import Path
import shutil

source = Path("frontend/pages/Teacher Dashboard/php/dbcontroller.php")

backup = Path(
    "migration/backups/dbcontroller_old.php"
)

shutil.copy2(source, backup)

print("Backup created:")
print(backup.resolve())
