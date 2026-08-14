# EWLearn — Students Management System

A PHP/MySQL web application for managing a school's Admin, Instructor
(Teacher), and Student workflows: course management, enrollment, grading,
exams, assignments, attendance, and academic calendar/notifications.

## Features

- **Admin**: user management (add/edit/delete instructors, students, staff),
  course management, staff directory, internal messaging (email).
- **Instructor/Teacher**: course roster, student management, grading, exam
  scheduling, assignment creation and submission grading, attendance
  tracking (per-course tabs), profile management, calendar and
  notifications.
- **Student**: enrolled courses, assignment submission, grades, attendance
  history, exam schedule, profile management.
- Public marketing/catalogue pages (course listing, course details,
  instructor bios, contact/about).

## Technology stack

- **Backend**: PHP 8.2 (procedural + a small number of classes), `mysqli`
  with prepared statements
- **Database**: MySQL / MariaDB (XAMPP)
- **Frontend**: server-rendered PHP views, vanilla JavaScript + jQuery,
  hand-written CSS (no build step, no bundler, no framework)
- **Libraries** (all loaded from CDN or vendored, no package manager for the
  frontend): jQuery, Font Awesome, Chart.js, FullCalendar, PHPMailer
  (`backend/vendor/`, managed via Composer)
- **Server**: Apache (XAMPP), `mod_php`

There is no build step for the frontend — PHP pages are requested directly
and JavaScript/CSS files are served as static assets.

## Architecture

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the full breakdown of
frontend/backend/database structure, authentication/authorization model, and
how the three dashboards (Admin/Teacher/Student) are wired together.

See [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md) for the annotated
directory tree.

See [docs/FILE_AUDIT.md](docs/FILE_AUDIT.md) for the cleanup audit trail —
every file that was moved, archived, or reorganized, with evidence for why.

## Directory structure (top level)

```text
backend/            Admin-only API endpoints (user/course CRUD, email)
frontend/
  core/              Shared PHP: DBController, auth_guard
  config/            Database connection config
  pages/
    dashboardAdmin/  Admin Dashboard (PHP pages, AJAX section fragments)
    Teacher Dashboard/  Instructor Dashboard
    Student Dashboard/  Student Dashboard
    common/          Shared calendar/notification/file-upload endpoints
  javascript/        Per-dashboard JS
  styles/            Per-dashboard CSS
  assets/            Shared images/icons
  images/            User-uploaded profile photos
database/            Schema (database_schema_full.sql) + additive migrations
                      (database/migrations/)
uploads/             User-uploaded assignment submission files
tools/               Developer utilities (account seeding, image download)
docs/                This documentation
```

## Installation (XAMPP / local development)

1. Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB +
   PHP 8.2+).
2. Clone/copy this repository into `htdocs/`, e.g.
   `C:\xampp\htdocs\EWLearn\Students Management System`.
3. Start Apache and MySQL from the XAMPP control panel.
4. Create the database and import the schema (see **Database setup**
   below).
5. Visit `http://localhost/EWLearn/Students%20Management%20System/frontend/pages/loginRegister.html`.

### Database setup

Create a database, then apply, in order:

1. `database/database_schema_full.sql` — base schema
2. Everything in `database/migrations/`, in this order:
   `academic_calendar_table.sql`, `notifications_table.sql`,
   `2026_08_08_teacher_dashboard_schema.sql`, `generated_exam_migration.sql`

All of the migration files are additive (new tables/columns only) — none of
them drop or rename anything.

### Configuration

All configuration (database credentials and SMTP credentials) is read at
runtime from `backend/.env` — a plain `KEY=VALUE` file, gitignored, never
committed. Copy the template and fill in real values:

```bash
cp .env.example backend/.env
```

Required variables (see `.env.example` for the full, up-to-date list):

- `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME` — read by
  `backend/config.php` and `frontend/config/database.php` via the small
  loader in `backend/load_env.php`. (Two separate config files because the
  frontend and the original `backend/` API layer were built at different
  times and never consolidated onto one shared config include — both now
  read from the same `.env`.)
- `SMTP_USERNAME`, `SMTP_PASSWORD` — read by `backend/send_email.php` (Admin
  "Message" feature) and `backend/sendContactMessage.php` (public contact
  form).

`load_env()` reads the file directly into a PHP array rather than using
`putenv()`/`getenv()` — the production host this app is deployed to disables
`putenv()` (the call silently no-ops instead of erroring), so anything
relying on it to actually propagate values would have failed silently.

## Authentication

Session-based (PHP native sessions), hardened in
`frontend/core/auth_guard.php`:

- Session ID regenerated on login (fixation protection)
- `HttpOnly` + `SameSite=Lax` cookies, strict session mode
- 30-minute idle timeout, 12-hour absolute session lifetime
- **The role is re-read from the `users` table on every request** — never
  trusted from the session, a cookie, or any client-supplied value. A
  deleted or role-changed account loses access immediately.

## Authorization

Every Admin, Instructor, and Student page and API endpoint is gated
server-side via `auth_guard.php`'s `auth_require_role()` — see
[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md#authentication--authorization)
for the full model, including instructor resource-ownership (IDOR)
enforcement.

## Admin Dashboard

`frontend/pages/dashboardAdmin/` — a single-page shell (`AdminDash.php`)
that AJAX-loads section fragments (Dashboard stats, Instructors, Students,
Courses, Staff, Messages) into `#dashboard-content`. All privileged
operations (add/edit/delete user, course CRUD) go through admin-gated
endpoints in `backend/`.

## Teacher Dashboard

`frontend/pages/Teacher Dashboard/` — one PHP page per section (profile,
courses, students, exams, grades, assignments, attendance), sharing a
common header/sidebar/footer (`php/header.php`,
`components/sidebar.php`/`components/header.php`). Every write operation is
scoped to courses the authenticated instructor actually owns
(`DBController::isCourseOwnedByTeacher()` / `getTeacherCourseIds()`).

## Student Dashboard

`frontend/pages/Student Dashboard/` — dashboard, courses, assignments,
grades, attendance, exams, profile. Every query is scoped to the
authenticated student's own `user_id`.

## Development

No build step. Edit PHP/JS/CSS directly and refresh the browser. PHP syntax
can be checked file-by-file with `php -l <file>`.

## Testing

There is no automated test suite committed to the repository. Verification
during development has relied on:

- `php -l` for syntax validation
- Direct `curl`/`mysql` CLI checks against the running app and database
- Ad hoc Playwright browser-automation scripts (not part of this repo —
  written to a local scratch directory during development sessions, not
  committed, since they contain no reusable app code, only one-off
  assertions against a specific dev environment's state)

If you want a real, repository-committed test suite, that would be a
reasonable next step (see `docs/FILE_AUDIT.md`'s technical-debt notes).

## Security

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md#authentication--authorization)
and `docs/FILE_AUDIT.md` for the full security posture and audit history.
Summary: server-side auth/authz on every protected route and endpoint,
parameterized SQL throughout the active codebase, no secrets committed to
version control, session hardening as described above, and instructor
resource-ownership enforcement preventing one instructor from reading or
modifying another's courses/grades/attendance/exams.

### Audit & hardening history

This codebase went through a full-site security and functional audit
covering every page, role, and API endpoint. Highlights:

- **SQL injection**: every endpoint taking user input has been converted
  from raw string-interpolated queries to parameterized statements
  (`mysqli` prepared statements with `bind_param`). One endpoint with no
  remaining legitimate callers and an unsound data model was removed
  outright rather than patched.
- **File uploads**: every upload path validates file *content*, not just
  the claimed file extension — images are verified with `getimagesize()`,
  other document types are checked against a denylist of executable/script
  MIME signatures. A renamed script can no longer be uploaded as if it were
  an image or document.
- **Credential hygiene**: all database and SMTP credentials were moved out
  of source files and into a gitignored `.env`, loaded at runtime (never
  committed). Git history was swept for anything committed before this
  change and scrubbed with `git filter-repo` where found; live credentials
  that had been exposed were rotated.
- **Access control**: every backend endpoint and dashboard page was
  reviewed for a missing server-side authorization gate. No gaps were
  found beyond what's already documented above.
- **Input validation**: admin-facing write endpoints were checked for
  missing presence/type validation on required fields, so a malformed
  request fails with a clear error instead of writing partial data.
- **Mobile/responsive**: pages were tested at real phone viewport widths
  and in a second browser engine; several genuine layout bugs (missing
  responsive breakpoints, a broken image constraint, a positioning bug
  that let a decorative background element overflow the page) were found
  and fixed.

No client-identifying details or exposed data are reproduced here — this
section is intentionally a summary of *what kind* of work was done, not a
finding-by-finding writeup.
