# Project Structure

This is the actual, verified top-level layout (not an idealized target —
see `docs/FILE_AUDIT.md` for what was reorganized to get here and why).

```text
Students Management System/
│
├── .env.example          Documented placeholder config (see note inside — the
│                         app doesn't read real env vars yet, this documents
│                         what frontend/config/database.php and backend/config.php
│                         currently hardcode)
├── .htaccess              Root security rules: blocks source-suffix files
│                         (*.php.bak etc.), composer manifests, disables
│                         display_errors
├── README.md
│
├── backend/               Admin-only API layer (one file per operation,
│   │                     no routing framework - each requested by direct URL)
│   ├── addUser.php, deleteUser.php, updateUser.php, getAllUser.php
│   ├── addCourse.php, deleteCourse.php, updateCourse.php, getCourses.php, getAllCourses.php
│   ├── ChooseINcourse.php, addCourseDetails.php, addCourses.php
│   ├── deleteInstructorCourse.php, updateInstructorCourse.php, getInstructorCourses.php
│   ├── getEmailsInstr.php, getEmailsStudents.php, getFullNameInstr.php, get_user_emails.php
│   ├── instructors.php, send_email.php
│   ├── login.php, regitser.php, resetPassword.php   Public auth endpoints (not admin-gated)
│   ├── config.php                                    DB connection (mysqli_connect style)
│   ├── dashStudent/                                  Admin-only student-roster endpoints
│   └── vendor/                                        PHPMailer (Composer-managed)
│
├── frontend/
│   ├── core/
│   │   ├── auth_guard.php       Shared authentication/authorization guard
│   │   └── DBController.php     Shared mysqli wrapper (prepared statements)
│   ├── config/
│   │   └── database.php         DB connection (OOP style, used by frontend code)
│   ├── pages/
│   │   ├── dashboardAdmin/      Admin Dashboard (AJAX-loaded section fragments)
│   │   ├── Teacher Dashboard/   Instructor Dashboard (one PHP page per section)
│   │   │   ├── php/             Instructor-gated AJAX endpoints
│   │   │   └── components/      Shared sidebar/header/footer includes
│   │   ├── Student Dashboard/   Student Dashboard (one PHP page per section)
│   │   │   └── php/             Student-gated AJAX endpoints
│   │   ├── common/              Shared calendar/notifications/file-upload endpoints
│   │   ├── backups_courseDetails/   Archived pre-fix versions of courseDetails.php
│   │   │                         (denied at web level, historical only)
│   │   └── *.html, *.php        Public marketing/catalogue pages (Home, courses, etc.)
│   ├── javascript/              Per-dashboard JS (dashboard/ subfolder = Admin JS)
│   ├── styles/                  Per-dashboard CSS
│   ├── assets/                  Shared images/icons used by public pages
│   └── images/                  User-uploaded profile photos
│
├── database/                    THE schema/migrations home (consolidated 2026-08-11
│   │                           from a split between a top-level database/ folder
│   │                           and migration/sql/ - one location, not two)
│   ├── database_schema_full.sql  Base schema
│   └── migrations/                Additive migrations, apply in the order listed
│       ├── academic_calendar_table.sql
│       ├── notifications_table.sql
│       ├── 2026_08_08_teacher_dashboard_schema.sql
│       └── generated_exam_migration.sql
│
├── migration/                   Migration/cleanup PROCESS history (not schema -
│   │                           see database/ above) - see README's "Migration
│   │                           system" section and FILE_AUDIT.md
│   ├── scripts/                 One-off Python migration/cleanup scripts (incl.
│   │   │                       the former migration/tools/phase2c_full_audit.py,
│   │   │                       folded in 2026-08-11 - both were "one-off python
│   │   │                       tooling", no reason to be two locations)
│   │   └── legacy-fixes/        Root-level fix_*.py scripts, relocated here
│   │                           2026-08-09 for organization (gitignored, local-only)
│   ├── logs/                    JSON audit logs generated during migration work
│   ├── instructor_dashboard/discovery/   Original pre-migration codebase discovery notes
│   ├── snapshots/                Point-in-time JSON snapshots of project state
│   ├── backups/                  Full pre-rewrite snapshots of the Teacher Dashboard
│   │   ├── sql-snapshots/        Table-level SQL backups (e.g. instructor_skills
│   │   │                        before an ID-column fix)
│   │   └── backup_before_migration/   Snapshot of course-dashboard.php etc. from
│   │                            before the Teacher Dashboard migration began -
│   │                            folded in from the former top-level
│   │                            backup_before_migration/ on 2026-08-11
│   └── dead-code/                Confirmed-unreferenced files archived out of the
│                                active tree during the 2026-08-09 cleanup pass
│
├── tools/                        Developer utilities meant to be run TODAY, as
│   │                           opposed to migration/scripts/'s historical record
│   │                           of scripts already run once during past migrations
│   ├── dev-scripts/              seed_users.php and friends - relocated out of
│   │                            backend/ during the earlier security pass
│   │                            (was previously web-reachable and could create
│   │                            accounts with known passwords)
│   └── download_user_images.py
│
├── uploads/                      User-uploaded assignment submission files
│   └── assignments/
│
├── docs/                         This documentation
│   ├── ARCHITECTURE.md
│   ├── PROJECT_STRUCTURE.md      (this file)
│   └── FILE_AUDIT.md
│
├── .claude/                      Claude Code project settings (not app code)
└── .vscode/                       Editor settings (not app code)
```

## Directories denied at the web server level

These exist in the repository (for history/reference/local tooling) but
return `403 Forbidden` if requested over HTTP, enforced by `.htaccess` files
placed in each directory during the security remediation pass:

- `migration/` (all of it, including `dead-code/` and `backups/`, which now
  also contains the former top-level `backup_before_migration/`)
- `tools/` (all of it, including `dev-scripts/`)
- `frontend/pages/backups_courseDetails/`

## Why `backend/` and `frontend/pages/*/php/` both exist

These are two generations of backend code from different points in this
project's history — `backend/` predates the Teacher/Student dashboard
rewrite and now serves the Admin Dashboard exclusively; the newer
per-dashboard `php/` folders were built during the Teacher/Student
dashboard work. They were not consolidated during this cleanup because
doing so would mean rewriting ~30 working, already-security-hardened Admin
endpoints for a purely organizational win — out of proportion to the risk,
per this cleanup's own "don't optimize for fewest files, preserve working
functionality" mandate. Documented here as a known architectural
inconsistency rather than silently left unexplained.
