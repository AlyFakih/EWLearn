from pathlib import Path
import subprocess


MYSQL = r"C:\xampp\mysql\bin\mysql.exe"

output = Path(
    "migration/sql/database_schema_full.sql"
)

tables = []

result = subprocess.check_output(
    [
        MYSQL,
        "-u",
        "root",
        "student_management",
        "-e",
        "SHOW TABLES;"
    ],
    text=True
)


for line in result.splitlines():
    line=line.strip()

    if line and not line.startswith("Tables"):
        tables.append(line)


with output.open(
    "w",
    encoding="utf-8"
) as file:


    for table in tables:

        file.write("\n")
        file.write("="*80+"\n")
        file.write(f"TABLE: {table}\n")
        file.write("="*80+"\n")


        schema = subprocess.check_output(
            [
                MYSQL,
                "-u",
                "root",
                "student_management",
                "-e",
                f"SHOW CREATE TABLE `{table}`;"
            ],
            text=True
        )

        file.write(schema)
        file.write("\n")


print(
    "Database exported:",
    output.resolve()
)
