from pathlib import Path
import re


sources = [
    Path("migration/backups/final_cleanup/student_controller/student_dbcontroller_before_remove.php"),
    Path("migration/backups/dbcontroller_fix/teacher_dbcontroller.php")
]


wanted = {
    "countUnreadNotifications",
    "createNotification",
    "createEvent",
    "getUpcomingEvents",
    "markAsRead",
    "uploadFile",
    "deleteNotification",
    "getUserNotifications",
    "updateEvent",
    "deleteEvent",
    "getEventsByDateRange",
    "getEventById",
    "deleteEventsByReference",
    "createSystemNotification",
    "formatFileSize",
    "sanitizeFilename",
    "scanForViruses",
    "getErrorResponse",
    "getMessage"
}


output = Path(
    "migration/logs/controller_missing_methods_full.txt"
)


result=[]


for source in sources:

    if not source.exists():
        continue

    text = source.read_text(
        encoding="utf-8",
        errors="ignore"
    )


    result.append("\n\n================================")
    result.append(str(source))
    result.append("================================\n")


    for method in wanted:

        pattern = (
            r'(function\s+' +
            re.escape(method) +
            r'\s*\([^)]*\)\s*\{)'
        )


        match = re.search(
            pattern,
            text
        )


        if match:

            start = match.start()


            brace = 0
            end = None


            for i in range(start, len(text)):

                if text[i] == "{":
                    brace += 1

                elif text[i] == "}":
                    brace -= 1

                    if brace == 0:
                        end = i + 1
                        break


            if end:
                result.append(
                    text[start:end]
                )


output.write_text(
    "\n".join(result),
    encoding="utf-8"
)


print("Created:", output)
