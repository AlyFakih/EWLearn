# Known Issues

Three low-severity items identified during the full-site audit (see the audit report for full context) that were deliberately left unfixed — flagged here for tracking rather than acted on unprompted, since none were in scope of what was asked and none carry meaningful security or data-integrity risk.

## 1. Malformed rows in the `instructorcourse` table

**What it is:** Some rows in the live `instructorcourse` table contain a bare instructor name (e.g., `Dr. Ali Fakkeh`) instead of the formatted `"A/B - InstructorName - CourseName"` string the application's own code always produces when linking an instructor to a course. There's also a spelling inconsistency between rows — `Dr. Ali Fakkeh` (double-k) vs. the correct `Dr. Ali Fakeh` used elsewhere.

**Where it lives:** The data itself is in the live `instructorcourse` table. It's surfaced (not caused) by `backend/getInstructorCourses.php`, which does a plain `SELECT name FROM instructorcourse` — confirmed safe and correctly parameterized; it just displays whatever is actually stored.

**Why it wasn't fixed:** This is production data, not a code defect. The code paths that write to this table (`ChooseINcourse.php`, `updateInstructorCourse.php`) are correct and already audited. Editing live rows to "clean up" formatting falls outside code-audit scope and risks silently changing real instructor-course associations without knowing how the malformed rows got there in the first place.

**Severity/impact:** Low. It affects a dropdown display's formatting cosmetically; it doesn't change access control, break a query, or expose anything. Worth a one-time manual data-cleanup pass if the client wants tidier admin UI text, but not urgent.

## 2. Admin Messages role filter doesn't actually filter

**What it is:** On the Admin "Messages" page, the recipient list has a role dropdown (All Users / Student / Instructor / Staff / Admin). Selecting "Student" or "Instructor" correctly narrows the recipient list. Selecting "Staff" or "Admin" shows *every* user's email regardless of role — the same as "All Users."

**Where it lives:** `frontend/pages/dashboardAdmin/messages.php` (the dropdown) and `frontend/javascript/dashboard/messages.js` (`updateEmailOptions()`), which only branches on `student` and `instructor` — everything else, including `staff` and `admin`, falls through to the same catch-all endpoint, `backend/get_user_emails.php`, which has no role filter at all (`SELECT email FROM users` with no `WHERE`).

**Why it wasn't fixed:** Low severity and outside the scope of what was asked for this pass — it over-shows recipients rather than under-showing or leaking anything unintended (an admin composing a message just sees more emails than the label implies, never fewer or the wrong ones). Also worth noting: "Staff" isn't a real role this application ever assigns (`admin`/`instructor`/`student` are the only valid values — see the dead "Staff" dropdown option removed elsewhere in this audit), so a genuine fix would need a decision on whether to build a role-filtered endpoint for "Admin" or simply remove the non-functional "Staff"/"Admin" options from this specific dropdown.

**Severity/impact:** Low. Cosmetic/UX mismatch, not a security or data issue — no unauthorized recipient exposure, since this is an admin-only tool showing admin-only-visible data (user emails) regardless of which filter is picked.

## 3. Course titles silently truncate at the database column length

**What it is:** `courses.courseTitle` is a fixed-length column. Submitting a course title longer than that limit doesn't produce an error — MySQL silently truncates it on insert, so the course gets created with a shorter title than what was typed, with no indication to the admin that anything was cut.

**Where it lives:** The `courses` table schema (`courseTitle` column), and every endpoint that inserts/updates it: `backend/addCourse.php`, `backend/addCourses.php`, `backend/updateCourse.php`. This was noticed incidentally while testing the input-validation fixes made to those three files during this audit — those fixes added *presence* validation (rejecting empty fields), not *length* validation.

**Why it wasn't fixed:** Discovered as a side effect of other testing, not something specifically asked for. Fixing it properly means picking one of two directions — widen the column, or add client/server-side length validation with a clear error message — either of which is a small but distinct scope decision better made deliberately than as a drive-by fix.

**Severity/impact:** Low. No security implication; worst case is a course admin is confused why a long title got shortened. Would be a quick, contained fix whenever it's prioritized.
