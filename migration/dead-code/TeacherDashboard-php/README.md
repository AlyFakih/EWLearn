# Archived dead code — Teacher Dashboard `php/` duplicates

Moved here on 2026-08-09 during the repository cleanup pass. Every file in
this folder was confirmed **unreferenced anywhere in the live codebase**
(zero PHP includes, zero JS `fetch`/`$.ajax` calls, zero HTML form actions)
before being moved — see `docs/FILE_AUDIT.md` for the full evidence per file.

Protected by `migration/.htaccess` (`Require all denied`), same as the rest
of this directory — not web-reachable even if someone guessed the URL.

| File | Why it's dead |
|---|---|
| `examFunctions.php` | Superseded by `exam_functions.php` (referenced by `exam-combined.js`). References `exam.courseID`, but the real column is `course_id`. |
| `gradesFunctions.php` | Superseded by `grade_functions.php` (referenced by `grades-new.js`). References a bare `grade` column; the real table (`course_grades`) has `overall_grade`/`midterm_grade`/`final_grade`. |
| `attendancetablefunctions.php` | Superseded by `attendance_functions.php` (referenced by `attendence.js`). Not referenced anywhere. |
| `student_functions.php` | Superseded by `studentFunctions.php` (referenced by `student.js`), which carries an explicit comment that it was rewritten to fix "no session/role check at all... SQL injection... UPDATE/DELETE arbitrary rows in `users`". This file is that pre-fix version, abandoned in place rather than removed. |
| `profile_functions.php` | `profile-dashboard.php` handles instructor-skills CRUD inline; this standalone endpoint was never wired to anything. |

Not deleted outright per this cleanup's safety rule (archive over destroy
when a file could still have historical value) — kept here, out of the
active tree, fully explained, and easy to permanently remove later if
someone confirms it's never needed again.
