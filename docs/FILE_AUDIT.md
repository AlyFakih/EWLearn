# File Audit — 2026-08-09/10 repository cleanup pass

## Classification legend

```text
A = ACTIVE / REQUIRED
B = ACTIVE BUT NEEDS REFACTORING
C = DUPLICATE BUT POTENTIALLY ACTIVE
D = LEGACY BUT STILL REFERENCED
E = DEAD / UNUSED
F = ARCHIVE / HISTORICAL
G = GENERATED
H = DEVELOPMENT TOOL / SCRIPT
I = MIGRATION / DATABASE ARTIFACT
J = DOCUMENTATION
K = UNKNOWN — REQUIRES INVESTIGATION
```

## Starting repository state

- **718 files**, **88 directories** (excluding `.git`)
- By extension: 279 `.php`, 110 `.jpg`, 57 `.css`, 54 `.py`, 47 `.js`,
  42 `.txt`, 30 `.jpeg`, 28 `.png`, 13 `.html`, 9 `.json`, 7 `.bak`, 6 `.sql`,
  6 `.htaccess`, 4 `.gif`, 2 `.zip`, 2 `.phpy`, 2 `.md`, 2 `.lock`,
  2 `.backup`

## 2026-08-11 follow-up pass

A second, deliberately conservative cleanup pass, executing only two
pre-approved actions from a broader audit:

**Moved**: `backup_before_migration/` (4 files: `.htaccess`,
`course-dashboard.php`, `course.js`, `course_functions.php`) →
`migration/backups/backup_before_migration/`. Neither the source nor
destination was ever git-tracked (`backup*/` is gitignored), so this was a
plain filesystem move, not `git mv`. Verified zero code references to the
old path anywhere in the live codebase before moving (only three mentions
existed, all in this project's own `docs/`/`README.md`, updated to match).

**NOT deleted (at the time):** the 9 candidate `.bak`/`.backup`/`.before_*`
files identified in the prior audit as "safe to delete" were re-checked
against git history before any deletion, per that pass's own stated
precondition. **All 9 are untracked and have zero git history** — the entire
`*.bak`/`*.backup*`/`*.before_*` pattern is explicitly gitignored (`.gitignore`
lines 35-36, 82-83), so none of them are recoverable via git. That pass left
them untouched. See the next section for what happened to them.

## 2026-08-11 second follow-up pass — full reorganization

A broader pass, explicitly authorized to be "aggressive about organization,
conservative about deletion." Three actions:

**1. Database/schema consolidation.** SQL previously lived split across two
top-level locations for no principled reason: a `database/` folder
(`academic_calendar_table.sql`, `notifications_table.sql`) and
`migration/sql/` (`database_schema_full.sql`,
`2026_08_08_teacher_dashboard_schema.sql`), plus one more loose file at
`migration/generated_exam_migration.sql`. Consolidated into one `database/`
directory:

```text
database/academic_calendar_table.sql          → database/migrations/academic_calendar_table.sql  (git mv - was tracked)
database/notifications_table.sql               → database/migrations/notifications_table.sql       (git mv - was tracked)
migration/sql/database_schema_full.sql         → database/database_schema_full.sql                 (mv - untracked)
migration/sql/2026_08_08_teacher_dashboard_schema.sql → database/migrations/2026_08_08_teacher_dashboard_schema.sql  (mv - untracked)
migration/generated_exam_migration.sql         → database/migrations/generated_exam_migration.sql   (mv - untracked)
```

Verified before moving: zero PHP/JS/HTML references to any of these 5 files
anywhere in the codebase (SQL files are never `require`d/`include`d - they're
applied manually, once, to the database). Only documentation referenced them
(`README.md`, `docs/FILE_AUDIT.md`), updated to match. Verified after
moving: the root `.htaccess`'s `<FilesMatch "\.(sql|...)$">` deny rule
applies recursively project-wide, so `database/` needed no `.htaccess` of
its own - confirmed all 5 files return `403` at their new location.

**2. `migration/tools/` folded into `migration/scripts/`.** The single file
there (`phase2c_full_audit.py`) was the same category of thing as
everything already in `migration/scripts/` (one-off Python tooling used
during the migration) - two directories for one purpose, with no other
files ever added to `migration/tools/` to justify it being separate. Moved
`migration/tools/phase2c_full_audit.py` → `migration/scripts/phase2c_full_audit.py`
(plain `mv`, was untracked); removed the now-empty `migration/tools/`.

**3. The 9 `.bak`/`.backup`/`.before_*` files — deleted.** This pass's
instructions explicitly permit deleting non-git-recoverable files when they
are "clearly disposable temporary artifacts," which these are: each has a
confirmed active, working, superseding sibling in the *same directory*
(verified again immediately before deletion - `php -l` and `node --check`
run on all 7 code-bearing siblings afterward, all clean), zero references
anywhere, already unreachable over HTTP, and the fact that this entire file
pattern is explicitly gitignored is itself a signal the project's own
conventions never intended these to be preserved artifacts - unlike
`migration/backups/`, which is a *curated, organized* historical archive.
Deleted:

```text
backend/login.php.backup
backend/login.php.before_session_add
backend/login.php.before_session_fix
frontend/javascript/dashboard/dash.js.bak
frontend/javascript/dashboard/student.js.bak
frontend/javascript/dashboard/teacher.js.bak
frontend/pages/loginRegister.html.bak
frontend/pages/Student Dashboard/assignment-details.php.bak
frontend/styles/loginRegister/login.css.bak
```

**Note on `frontend/pages/backups_courseDetails/`** (6 files, also
`.before_*`/`.backup`/`.bak` suffixed): explicitly NOT touched by the above.
That directory is a deliberately curated set of iterative snapshots
documenting a specific past fix sequence for `courseDetails.php` - the same
"organized historical record" category as `migration/backups/`, not "stray
editor copy," so it stays classified F (archive), not deleted.

## Files ARCHIVED this session (moved, not deleted)

### E — Dead duplicate PHP endpoints

Moved to `migration/dead-code/TeacherDashboard-php/`. Each was checked with
a full-repository search (`grep -rl` across every `.php`/`.js`/`.html` file,
outside already-archived backup trees) before moving — zero references
found for any of them.

| Path (old) | References found | Evidence it's dead | Decision |
|---|---|---|---|
| `frontend/pages/Teacher Dashboard/php/examFunctions.php` | None | Superseded by `exam_functions.php` (referenced by `exam-combined.js`). References `exam.courseID` — the real column is `course_id`. | Archived |
| `frontend/pages/Teacher Dashboard/php/gradesFunctions.php` | None | Superseded by `grade_functions.php` (referenced by `grades-new.js`). References a bare `grade` column; the real table (`course_grades`) has `overall_grade`/`midterm_grade`/`final_grade`. | Archived |
| `frontend/pages/Teacher Dashboard/php/attendancetablefunctions.php` | None | Superseded by `attendance_functions.php` (referenced by `attendence.js`). | Archived |
| `frontend/pages/Teacher Dashboard/php/student_functions.php` | None | Superseded by `studentFunctions.php` (referenced by `student.js`), which carries an explicit code comment stating it was rewritten to fix "no session/role check at all... SQL injection... UPDATE/DELETE arbitrary rows in `users`". This file is the pre-fix version, left in place rather than removed at the time. | Archived |
| `frontend/pages/Teacher Dashboard/php/profile_functions.php` | None | `profile-dashboard.php` handles instructor-skills CRUD inline (confirmed 4 references to `instructor_skills` in that file); this standalone endpoint was never wired to anything. | Archived |

All five were already protected by `auth_require_role('instructor')`
(applied during the earlier security remediation pass, before their dead
status was confirmed), so they were not a live vulnerability — this move is
a clarity improvement, not a security fix. Not permanently deleted: per
this cleanup's own safety rules, archived with full evidence and a
`README.md` in the archive folder so a future developer can permanently
remove them once satisfied, or resurrect one if evidence turns out to be
wrong.

### H — Development scripts reorganized

27 root-level `fix_*.py` / `add_attendance_guard.py` /
`remove_instructor_input.py` one-off migration scripts (all dated 2026-08-07,
none referenced by any PHP via `exec`/`shell_exec`/`system`/`proc_open` —
confirmed zero matches repository-wide) moved from the repository root into
`migration/scripts/legacy-fixes/`, alongside the already-organized sibling
scripts in `migration/scripts/`. These match the `fix_*.py`/`find_*.py`/
`verify_*.py` patterns in `.gitignore` — they were never tracked by git, so
this move has no effect on version history.

### I — Migration artifact relocated

`instructor_skills_backup_before_id_fix.sql` (54-line `mysqldump` snapshot,
untracked) moved from the repository root to
`migration/backups/sql-snapshots/` for consistency with the rest of the
migration-history material.

## Files classified but explicitly KEPT AS-IS

### F — Archive / historical (preserved, not touched)

| Path | Why kept |
|---|---|
| `migration/backups/` (182 files, incl. `backup_before_migration/` folded in 2026-08-11 — see "Files MOVED 2026-08-11" below) | Full pre-rewrite snapshots of the Teacher Dashboard. Historical record of the migration. Already denied at the web server level (`.htaccess`, `Require all denied`) since the earlier security pass — confirmed still returns 403 after this cleanup. |
| `frontend/pages/backups_courseDetails/` | Six `courseDetails.before_*.php` variants documenting an earlier iterative fix to `courseDetails.php`. Denied at web level. |
| `migration/instructor_dashboard/discovery/` (8 `.txt`) | Original pre-migration codebase discovery notes — exactly the kind of migration history the cleanup brief says to preserve. |
| `migration/logs/` (34 `.txt`, 2 `.zip`) | JSON/text audit logs generated during the migration and security work. |
| `migration/snapshots/project_before_course.json` | Point-in-time project-state snapshot. |

**Update 2026-08-11:** the 9 stray `.bak`/`.backup`/`.before_*` files
formerly listed here (`backend/login.php.backup`,
`frontend/javascript/dashboard/teacher.js.bak`, and 7 others) were
**deleted** in the second follow-up pass — see that section above for the
full list and justification. They were editor-generated scratch copies,
not a curated archive like the rows above this note.

### D — Legacy but still referenced (kept, working)

| Path | Note |
|---|---|
| `frontend/pages/Teacher Dashboard/php/function.php` | Referenced by `assignment.js`'s inline-edit save handler. Legacy code (raw SQL string interpolation into `assignment_submissions`, mismatched column assumptions in places) but **live** — not archived. Flagged as technical debt below rather than removed, since it is currently reachable and doing so would need behavior verification beyond this pass's scope. |

### I — Database artifact (verified, untouched)

`studentcourse_backup` table (120 rows, live database) — a point-in-time
snapshot of `studentcourse`, zero code references anywhere in the
application. Per this cleanup's explicit "repository cleanup is not
permission to modify production data" rule, this table was **not**
touched, dropped, or renamed. Documented here so its existence is no longer
mysterious to a future developer.

### C — Two backend generations (kept, documented, not merged)

`backend/*.php` (Admin Dashboard API layer, ~30 files) and
`frontend/pages/{Teacher,Student} Dashboard/php/*.php` (per-dashboard
endpoints) are two independently-evolved backend layers from different
points in the project's history. Not consolidated — see
`docs/PROJECT_STRUCTURE.md`'s "Why `backend/` and `frontend/pages/*/php/`
both exist" section for the reasoning. Both are fully active, fully
security-gated, and independently tested.

## Files investigated and confirmed NOT a problem

| Path | Finding |
|---|---|
| `backend/ph-student-fill.png` | Lives inside `backend/` (a PHP-endpoint directory) rather than `frontend/assets/images/` — an odd location, but **actively referenced** by 4 files (`send_email.php`, `messages.php`, `Footer.html`, `navbar.html`). Left in place: moving it would require updating 4 references for a purely cosmetic win, out of proportion to the benefit. Logged as minor technical debt, not fixed. |
| `frontend/pages/Teacher Dashboard/php/get_submission.php`, `delete_submission.php`, `create_assignment.php`, `grade_submission.php`, `course_functions.php`, `grade_functions.php`, `exam_functions.php`, `attendance_functions.php`, `studentFunctions.php`, `update_profile.php`, `profile-dashboard.php`'s inline handlers | Confirmed **A — active**, each referenced by exactly one current JS file, each independently security-gated and IDOR-tested (see security test results in the final report). |
| `.phpy` files (2, inside `migration/backups/`) | Typo'd extension from an earlier backup script, harmless, already archived alongside the rest of that snapshot. |
| `database/migrations/academic_calendar_table.sql`, `database/migrations/notifications_table.sql` (moved 2026-08-11, formerly `database/*.sql`) | **A — active**, one-time-applied feature migrations for tables that exist in the live schema. Kept for setup documentation (see README). |

## Broken includes found — deliberately NOT fixed

Second-pass audit found that `tools/dev-scripts/seed_users*.php` and
`tools/dev-scripts/test.php` have `require`/`include` paths that no longer
resolve (`./config.php`, `__DIR__ . '/../frontend/core/auth_guard.php'`) —
left stale from when these files were relocated out of `backend/` during
the earlier security remediation pass (they create user accounts with
known passwords and were previously reachable over HTTP).

**Not fixed.** These scripts are already denied at the web server level
(`tools/.htaccess`, confirmed still returning 403). Repairing their include
paths would make them executable again from the command line for no
legitimate benefit, while the broken paths currently add an incidental
extra safety margin. Correctness was intentionally not prioritized over
security here — flagging the finding rather than silently leaving it
unexplained, per this audit's own transparency requirement, without
"fixing" something that arguably should stay inert.

## Remaining K — Unknown

None. Every file surfaced during this audit was resolved to a concrete
classification with evidence. If a future audit surfaces something new,
follow the same rule this pass did: if genuine doubt remains, keep it and
mark it `K` rather than guessing.

## Duplicate-function audit (the specific pattern the cleanup brief called out)

| Pair | Active (referenced) | Archived (dead) |
|---|---|---|
| exam functions | `exam_functions.php` ← `exam-combined.js` | `examFunctions.php` |
| grade functions | `grade_functions.php` ← `grades-new.js` | `gradesFunctions.php` |
| attendance functions | `attendance_functions.php` ← `attendence.js` | `attendancetablefunctions.php` |
| student functions | `studentFunctions.php` ← `student.js` | `student_functions.php` |
| profile functions | inline in `profile-dashboard.php` | `profile_functions.php` |

## Naming inconsistency note

File naming across `frontend/pages/Teacher Dashboard/php/` mixes
`camelCase` (`studentFunctions.php`), `snake_case`
(`attendance_functions.php`), and no-separator (`function.php`) — a genuine
inconsistency, but **not renamed** during this pass. Renaming a live,
referenced endpoint requires updating every JS reference, re-testing the
full request chain, and carries real regression risk for a purely cosmetic
win, which conflicts with this cleanup's own priority order (correctness
and safety over uniformity for its own sake). Logged as technical debt for
a dedicated future pass, not attempted here.
