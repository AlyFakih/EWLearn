# Architecture

## Overview

Server-rendered PHP application, no frontend framework, no build step,
three role-scoped dashboards (Admin / Instructor / Student) sharing one
MySQL database and one authentication/session layer.

```text
Browser
  │
  ▼
Apache (mod_php)
  │
  ├── Public pages (frontend/pages/*.html, courses.php, courseDetails.php)
  │
  ├── Auth (backend/login.php, backend/regitser.php, backend/resetPassword.php)
  │
  ├── Admin Dashboard (frontend/pages/dashboardAdmin/)
  │     └── backend/*.php  (admin-only API endpoints)
  │
  ├── Teacher Dashboard (frontend/pages/Teacher Dashboard/)
  │     └── frontend/pages/Teacher Dashboard/php/*.php  (instructor-only, ownership-scoped)
  │
  └── Student Dashboard (frontend/pages/Student Dashboard/)
        └── frontend/pages/Student Dashboard/php/*.php  (student-only, self-scoped)
              │
              ▼
      frontend/core/DBController.php ── mysqli (prepared statements)
              │
              ▼
        MySQL: student_management
```

## Frontend

- Plain PHP templates emit HTML directly (no templating engine).
- Each dashboard has its own CSS (`frontend/styles/DashBoards/*.css` for
  Admin; `frontend/pages/Teacher Dashboard/css/*.css` for Teacher;
  `frontend/pages/Student Dashboard/*.css`-adjacent styles for Student) and
  its own JavaScript, loaded per-page rather than bundled.
- The Teacher Dashboard has a shared design-token stylesheet
  (`css/dashboard-theme.css`) that the other two dashboards do not use —
  each dashboard's visual system is independent by design; they do not share
  CSS files, so a change to one cannot visually regress another (verified
  during this cleanup: no cross-dashboard stylesheet references exist).
- The Admin Dashboard is a single-page shell (`AdminDash.php`) that loads
  section fragments via jQuery `.load()`/`$.ajax()` into `#dashboard-content`
  — `dash.php`, `teacher.php`, `students.php`, `coursesAdmin.php`,
  `staff.php`, `messages.php`, `NotifiAdmin.php`. Each fragment can carry its
  own `<link>`/`<script>` tags, which the browser picks up even though
  they're injected via innerHTML.
- The Teacher and Student dashboards are traditional multi-page apps — one
  full PHP page per section, sharing a common header/sidebar/footer via PHP
  `include`.

## Backend

Two generations of backend code coexist:

1. **`backend/`** — the original, flat API layer. One file per operation
   (`addUser.php`, `deleteUser.php`, `getCourses.php`, ...). Used
   exclusively by the Admin Dashboard's JavaScript. No routing framework —
   each file is requested directly by URL.
2. **`frontend/pages/{Teacher,Student} Dashboard/php/`** — newer, per-dashboard
   endpoint files, each independently gated. Also no routing framework.

Both generations use `frontend/core/DBController.php` (`mysqli` +
`bind_param` prepared statements) for all database access. There is no
ORM, no query builder, and no models layer — queries are written inline in
each endpoint file.

## Database

MySQL/MariaDB, database name `student_management`. Key tables:

| Table | Purpose | Notes |
|---|---|---|
| `users` | All accounts (admin/instructor/student), `role` column | `fullName` (not `full_name`), bcrypt `password` |
| `courses` | Course catalogue | **No `teacher_id` column** — ownership is via `instructorcourse` |
| `instructorcourse` | Instructor ↔ course ownership | Legacy **name-keyed**: `userInstructorID` = `users.fullName`, `courseID` = `courses.courseTitle` (both strings, not IDs) |
| `studentcourse` | Student ↔ course enrollment | Same name-keyed pattern as `instructorcourse` |
| `assignment`, `assignment_submissions` | Assignments and student submissions | ID-keyed (newer) |
| `course_grades` | Per-student, per-course grades | `overall_grade`/`midterm_grade`/`final_grade` — **no bare `grade` column** |
| `exam` | Exam schedule | `course_id` (snake_case, **not** `courseID`) |
| `attendance` | Per-student attendance records | Denormalized name/image snapshot columns |
| `academic_calendar` | Calendar events | **No `reference_type`/`reference_id` columns** |
| `notifications` | In-app notifications | |
| `instructor_skills`, `coursedetails`, `chapterdetails` | Profile/course supplementary data | |
| `studentcourse_backup` | A point-in-time snapshot of `studentcourse` | Zero code references — kept as a safety net from an earlier data fix, untouched by this cleanup per the "never modify production data" rule |

This mix of **legacy name-keyed** tables (`instructorcourse`/`studentcourse`)
and **modern ID-keyed** tables (`assignment_submissions`, `course_grades`,
`exam`) is the single most important thing to know before touching this
codebase — several past bugs (and the dead code archived in this cleanup,
see `docs/FILE_AUDIT.md`) came from code written against an assumed schema
that didn't match either of these actual conventions (e.g. assuming
`courses.teacher_id` exists, or `exam.courseID` instead of `exam.course_id`).
**Always verify against the live schema, never assume.**

## Authentication & Authorization

Single shared guard: `frontend/core/auth_guard.php`, included first (before
any other logic) by every protected page and endpoint.

```text
Request
  │
  ▼
auth_session_boot()        Hardened session start: HttpOnly, SameSite=Lax,
  │                        strict mode. Must run before any other
  │                        session_start() or the flags are silently dropped.
  ▼
auth_user()                 Resolves the session's user_id, checks idle
  │                        timeout (30 min) and absolute lifetime (12h),
  │                        then RE-READS the role from `users` in the
  │                        database - never trusts the session's cached
  │                        copy. A deleted or role-changed account loses
  │                        access on its very next request.
  ▼
auth_require_role(role)     401 (not logged in) or 403 (wrong role) for
  │                        APIs; 302-to-login for pages. Denial bodies
  │                        never include the requested data.
  ▼
Endpoint's own logic        For Teacher endpoints: ownership check via
                            DBController::isCourseOwnedByTeacher() /
                            getTeacherCourseIds() before any read/write
                            touching a specific course's data.
```

Login (`backend/login.php`) calls `auth_login_session()`, which calls
`session_regenerate_id(true)` — defeats session fixation by discarding any
session ID an attacker might have planted in the victim's browser before
they authenticated.

**Role source of truth**: exclusively the `users.role` column, read fresh on
every request. Nothing — not `$_SESSION['role']` (kept only as a UI
convenience copy, always overwritten from the DB read), not
`localStorage`, not a request parameter — is trusted for an authorization
decision.

**Resource ownership (IDOR)**: every Teacher endpoint that reads or writes a
specific course/grade/attendance-record/exam/submission checks that the
authenticated instructor actually owns that course before proceeding.
Verified live (not just by code review) during the earlier security
remediation pass: Instructor A, authenticated with a valid session, was
unable to read or modify Instructor B's course, grade, attendance record,
exam, or assignment submission in any of 14 attack scenarios.

## Shared components

- `frontend/pages/common/calendar.php` / `calendar_api.php` — FullCalendar
  backing, session-gated, scoped per role.
- `frontend/pages/common/notifications.php` / `notification_api.php` — the
  in-app notification bell/badge.
- `frontend/pages/common/file_handler.php` — shared upload handler (used by
  assignment submission and profile photo upload), used with `__DIR__`-anchored
  paths to avoid PHP's relative-include resolution surprises (see below).
- `frontend/pages/Teacher Dashboard/components/sidebar.php` /
  `components/header.php` — shared Teacher Dashboard chrome, included by
  `php/header.php`.

## A PHP-specific gotcha worth knowing

PHP resolves a **bare relative** `require`/`include` path (e.g.
`require_once "../../core/DBController.php"`) against the **top-level
requested script's** directory, not the directory of the file doing the
including — this bit multiple shared files earlier in this project's
history when they were `include`d from pages at different directory depths.
Every shared/common file in the current codebase uses `__DIR__`-anchored
paths (`require_once __DIR__ . "/../../core/DBController.php"`) specifically
to avoid this class of bug. Keep using `__DIR__` for any new shared include.

## Migration history

See the top-level `README.md`'s "Migration system" section and
`docs/FILE_AUDIT.md` for the full audit trail of what changed during each
phase of this project's evolution (initial Teacher Dashboard migration →
security remediation → UI redesign → this cleanup pass).
