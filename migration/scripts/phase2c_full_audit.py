#!/usr/bin/env python3
"""
EWLearn / Students Management System
PHASE 2C - Full Repository + Database Intelligence Audit

Purpose:
    Collect a high-confidence snapshot of the current branch without modifying
    application source code or database data.

Run from:
    C:/xampp/htdocs/EWLearn/Students Management System

Example:
    python migration/tools/phase2c_full_audit.py

Outputs:
    migration/logs/phase2c_full_audit_<timestamp>.txt
    migration/logs/phase2c_full_audit_<timestamp>.json

The script is intentionally dependency-free (Python standard library only).
It uses mysql.exe if available and PHP CLI if available.
"""

from __future__ import annotations

import argparse
import collections
import datetime as dt
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Tuple


# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------

DEFAULT_DB = "student_management"
DEFAULT_MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
DEFAULT_PHP = r"C:\xampp\php\php.exe"

IGNORE_DIRS = {
    ".git", ".idea", ".vscode", "node_modules", "vendor",
    "bower_components", "cache", "tmp", "temp",
    "__pycache__",
}

TEXT_EXTENSIONS = {
    ".php", ".inc", ".phtml", ".php3", ".php4", ".php5", ".phps",
    ".sql", ".js", ".jsx", ".ts", ".tsx", ".css", ".scss", ".html",
    ".htm", ".json", ".xml", ".yml", ".yaml", ".md", ".txt", ".env",
}

PHP_EXTENSIONS = {".php", ".inc", ".phtml"}

DANGEROUS_WRITE_PATTERNS = [
    r"\bDROP\s+TABLE\b",
    r"\bTRUNCATE\s+TABLE\b",
    r"\bDELETE\s+FROM\b",
    r"\bALTER\s+TABLE\b",
    r"\bRENAME\s+TABLE\b",
    r"\bUPDATE\s+\w+\s+SET\b",
    r"\bINSERT\s+INTO\b",
    r"\bCREATE\s+TABLE\b",
]

TABLE_REF_RE = re.compile(
    r"\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+[`'\"]?([A-Za-z_][A-Za-z0-9_]*)",
    re.I,
)

PHP_INCLUDE_RE = re.compile(
    r"""(?:require_once|require|include_once|include)\s*\(?\s*["']([^"']+)["']""",
    re.I,
)

CLASS_RE = re.compile(r"\bclass\s+([A-Za-z_][A-Za-z0-9_]*)")
FUNCTION_RE = re.compile(r"\bfunction\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(")
NEW_RE = re.compile(r"\bnew\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(")
HEADER_PHPY_RE = re.compile(r"header\.phpy", re.I)

DB_CONTROLLER_RE = re.compile(r"\bDBController\b", re.I)

DUPLICATE_CANDIDATE_NAMES = {
    "attendancetablefunctions.php",
    "attendance_functions.php",
    "examfunctions.php",
    "exam_functions.php",
    "gradesfunctions.php",
    "grade_functions.php",
    "studentfunctions.php",
    "student_functions.php",
}

SUSPICIOUS_FILENAMES = {
    ".bak", ".backup", ".old", ".orig", ".tmp", ".swp",
}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def now_stamp() -> str:
    return dt.datetime.now().strftime("%Y%m%d_%H%M%S")


def rel(root: Path, p: Path) -> str:
    try:
        return p.relative_to(root).as_posix()
    except ValueError:
        return str(p).replace("\\", "/")


def safe_read(path: Path, max_bytes: int = 2_000_000) -> Optional[str]:
    try:
        if path.stat().st_size > max_bytes:
            return None
        data = path.read_bytes()
        return data.decode("utf-8", errors="replace")
    except Exception:
        return None


def sha256(path: Path) -> Optional[str]:
    try:
        h = hashlib.sha256()
        with path.open("rb") as f:
            for chunk in iter(lambda: f.read(1024 * 1024), b""):
                h.update(chunk)
        return h.hexdigest()
    except Exception:
        return None


def run_cmd(
    cmd: List[str],
    cwd: Optional[Path] = None,
    timeout: int = 60,
) -> Dict[str, Any]:
    try:
        p = subprocess.run(
            cmd,
            cwd=str(cwd) if cwd else None,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=timeout,
            shell=False,
        )
        return {
            "ok": p.returncode == 0,
            "returncode": p.returncode,
            "stdout": p.stdout,
            "stderr": p.stderr,
            "cmd": cmd,
        }
    except FileNotFoundError:
        return {
            "ok": False,
            "returncode": None,
            "stdout": "",
            "stderr": "COMMAND_NOT_FOUND",
            "cmd": cmd,
        }
    except subprocess.TimeoutExpired as e:
        return {
            "ok": False,
            "returncode": None,
            "stdout": e.stdout or "",
            "stderr": "TIMEOUT",
            "cmd": cmd,
        }
    except Exception as e:
        return {
            "ok": False,
            "returncode": None,
            "stdout": "",
            "stderr": f"{type(e).__name__}: {e}",
            "cmd": cmd,
        }


def section(title: str) -> str:
    return "\n" + "=" * 90 + "\n" + title + "\n" + "=" * 90 + "\n"


def add_finding(findings: List[Dict[str, Any]], severity: str, category: str,
                item: str, detail: str, evidence: Any = None) -> None:
    findings.append({
        "severity": severity,
        "category": category,
        "item": item,
        "detail": detail,
        "evidence": evidence,
    })


def mysql_available(mysql: str) -> bool:
    return Path(mysql).exists() or shutil.which(mysql) is not None


def mysql_query(mysql: str, db: str, sql: str, timeout: int = 30) -> Dict[str, Any]:
    """
    Read-only MySQL query.
    -u root intentionally follows the current local XAMPP setup.
    No password is supplied; if the local server requires one, the query
    simply reports the failure and the repository audit continues.
    """
    cmd = [mysql, "-u", "root", db, "-e", sql]
    return run_cmd(cmd, timeout=timeout)


def mysql_query_tsv(mysql: str, db: str, sql: str) -> Tuple[bool, List[Dict[str, str]], str]:
    cmd = [mysql, "-u", "root", "--batch", "--raw", db, "-e", sql]
    result = run_cmd(cmd, timeout=60)
    if not result["ok"]:
        return False, [], result["stderr"] or result["stdout"]
    lines = [x for x in result["stdout"].splitlines() if x.strip()]
    if not lines:
        return True, [], ""
    headers = lines[0].split("\t")
    rows = []
    for line in lines[1:]:
        vals = line.split("\t")
        vals += [""] * (len(headers) - len(vals))
        rows.append(dict(zip(headers, vals[:len(headers)])))
    return True, rows, ""


# ---------------------------------------------------------------------------
# Repository inventory
# ---------------------------------------------------------------------------

def walk_files(root: Path) -> List[Path]:
    files: List[Path] = []
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [
            d for d in dirnames
            if d not in IGNORE_DIRS and not d.startswith(".git")
        ]
        base = Path(dirpath)
        for name in filenames:
            p = base / name
            try:
                if p.is_file():
                    files.append(p)
            except OSError:
                pass
    return files


def repo_inventory(root: Path, files: List[Path]) -> Dict[str, Any]:
    ext_counts = collections.Counter()
    dir_counts = collections.Counter()
    total_bytes = 0

    for p in files:
        ext_counts[p.suffix.lower() or "[no extension]"] += 1
        try:
            total_bytes += p.stat().st_size
        except OSError:
            pass
        parent = p.parent
        try:
            first = p.relative_to(root).parts[0]
        except Exception:
            first = str(parent)
        dir_counts[first] += 1

    return {
        "file_count": len(files),
        "total_bytes": total_bytes,
        "extensions": dict(ext_counts.most_common()),
        "top_level_file_counts": dict(dir_counts.most_common()),
    }


# ---------------------------------------------------------------------------
# PHP/static analysis
# ---------------------------------------------------------------------------

def php_analysis(root: Path, files: List[Path]) -> Dict[str, Any]:
    php_files = [p for p in files if p.suffix.lower() in PHP_EXTENSIONS]

    classes = []
    functions = []
    includes = []
    db_usage = []
    suspicious = []
    table_refs = collections.defaultdict(list)
    malformed = []

    for p in php_files:
        text = safe_read(p)
        if text is None:
            continue
        r = rel(root, p)

        for m in CLASS_RE.finditer(text):
            classes.append({"file": r, "class": m.group(1)})

        for m in FUNCTION_RE.finditer(text):
            functions.append({"file": r, "function": m.group(1)})

        for m in PHP_INCLUDE_RE.finditer(text):
            includes.append({"file": r, "include": m.group(1)})

        if DB_CONTROLLER_RE.search(text):
            db_usage.append({
                "file": r,
                "new_DBController_count": len(re.findall(r"\bnew\s+DBController\s*\(", text)),
                "requires_DBController": [
                    x for x in re.findall(PHP_INCLUDE_RE, text)
                    if "dbcontroller" in x.lower()
                ],
            })

        if HEADER_PHPY_RE.search(text):
            malformed.append({"file": r, "pattern": "header.phpy"})

        for m in TABLE_REF_RE.finditer(text):
            table = m.group(1).lower()
            table_refs[table].append(r)

        lower = p.name.lower()
        if lower in DUPLICATE_CANDIDATE_NAMES:
            suspicious.append({
                "file": r,
                "reason": "duplicate-function-family candidate",
                "sha256": sha256(p),
            })

        for suffix in SUSPICIOUS_FILENAMES:
            if p.name.lower().endswith(suffix):
                suspicious.append({
                    "file": r,
                    "reason": f"suspicious backup/temp suffix {suffix}",
                    "sha256": sha256(p),
                })
                break

    class_counts = collections.Counter(x["class"] for x in classes)
    function_counts = collections.Counter(x["function"] for x in functions)

    duplicate_classes = {
        k: v for k, v in class_counts.items() if v > 1
    }
    duplicate_functions = {
        k: v for k, v in function_counts.items() if v > 1
    }

    return {
        "php_file_count": len(php_files),
        "classes": classes,
        "duplicate_classes": duplicate_classes,
        "functions_count": len(functions),
        "duplicate_functions": duplicate_functions,
        "includes_count": len(includes),
        "includes": includes,
        "dbcontroller_usage": db_usage,
        "malformed_patterns": malformed,
        "suspicious_files": suspicious,
        "table_references": {
            k: sorted(set(v)) for k, v in sorted(table_refs.items())
        },
    }


def resolve_include(root: Path, source: Path, include: str) -> Optional[Path]:
    """
    Best-effort PHP include resolver for literal relative paths.
    Does not execute PHP.
    """
    include = include.replace("\\", "/")
    if include.startswith("/"):
        candidate = Path(include)
    else:
        candidate = source.parent / include

    candidates = [candidate]
    if candidate.suffix == "":
        candidates.extend([
            Path(str(candidate) + ".php"),
            Path(str(candidate) + ".inc"),
        ])

    for c in candidates:
        try:
            c = c.resolve()
            if c.exists() and c.is_file():
                return c
        except Exception:
            pass
    return None


def include_integrity(root: Path, files: List[Path]) -> Dict[str, Any]:
    php_files = [p for p in files if p.suffix.lower() in PHP_EXTENSIONS]
    missing = []
    resolved = []

    for p in php_files:
        text = safe_read(p)
        if text is None:
            continue
        for inc in PHP_INCLUDE_RE.findall(text):
            target = resolve_include(root, p, inc)
            item = {
                "file": rel(root, p),
                "include": inc,
                "resolved_to": rel(root, target) if target else None,
            }
            if target:
                resolved.append(item)
            else:
                missing.append(item)

    return {
        "missing_literal_includes": missing,
        "resolved_literal_includes": resolved,
    }


# ---------------------------------------------------------------------------
# Duplicate content detection
# ---------------------------------------------------------------------------

def duplicate_files(root: Path, files: List[Path]) -> Dict[str, Any]:
    by_hash = collections.defaultdict(list)

    for p in files:
        try:
            size = p.stat().st_size
        except OSError:
            continue

        # Ignore very large binary-ish files for this comparison.
        if size > 5_000_000:
            continue

        h = sha256(p)
        if h:
            by_hash[h].append(rel(root, p))

    groups = [
        {"sha256": h, "files": paths}
        for h, paths in by_hash.items()
        if len(paths) > 1
    ]
    groups.sort(key=lambda x: (-len(x["files"]), x["files"]))
    return {"identical_file_groups": groups}


# ---------------------------------------------------------------------------
# Git analysis
# ---------------------------------------------------------------------------

def git_analysis(root: Path) -> Dict[str, Any]:
    result: Dict[str, Any] = {}

    commands = {
        "status": ["git", "status", "--short", "--branch"],
        "branch": ["git", "branch", "--show-current"],
        "log": ["git", "log", "-10", "--oneline", "--decorate"],
        "remotes": ["git", "remote", "-v"],
        "diff_stat": ["git", "diff", "--stat"],
        "diff_name_only": ["git", "diff", "--name-only"],
    }

    for key, cmd in commands.items():
        result[key] = run_cmd(cmd, cwd=root, timeout=30)

    return result


# ---------------------------------------------------------------------------
# Targeted project structure
# ---------------------------------------------------------------------------

def target_paths(root: Path) -> Dict[str, Any]:
    targets = [
        "frontend/pages",
        "frontend/pages/Student Dashboard",
        "frontend/pages/Teacher Dashboard",
        "frontend/pages/common",
        "core",
        "migration",
        "migration/logs",
        "backend",
        "api",
        "includes",
        "models",
        "controllers",
        "functions",
    ]

    result = {}
    for t in targets:
        p = root / t
        if p.exists():
            try:
                children = sorted(
                    [rel(root, x) for x in p.iterdir()],
                    key=str.lower
                )
            except Exception:
                children = []
            result[t] = {
                "exists": True,
                "type": "directory" if p.is_dir() else "file",
                "children": children[:500],
            }
        else:
            result[t] = {"exists": False}

    return result


def teacher_student_focus(root: Path, files: List[Path]) -> Dict[str, Any]:
    focus_terms = [
        "teacher dashboard",
        "teacher-dashboard",
        "instructor",
        "student dashboard",
        "student-dashboard",
        "course",
        "attendance",
        "assignment",
        "grade",
        "exam",
        "calendar",
        "notification",
    ]

    matches = []
    for p in files:
        r = rel(root, p)
        lr = r.lower()
        if any(term in lr for term in focus_terms):
            matches.append(r)

    return {
        "matching_paths": sorted(matches, key=str.lower)
    }


# ---------------------------------------------------------------------------
# Database analysis
# ---------------------------------------------------------------------------

def database_analysis(mysql: str, db: str) -> Dict[str, Any]:
    result: Dict[str, Any] = {
        "mysql_path": mysql,
        "database": db,
        "available": mysql_available(mysql),
        "queries": {},
    }

    if not result["available"]:
        result["error"] = "mysql.exe not found at configured path or PATH."
        return result

    # Completely read-only queries.
    queries = {
        "database_tables": """
            SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_ROWS
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME;
        """,
        "columns": """
            SELECT TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE,
                   IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME, ORDINAL_POSITION;
        """,
        "foreign_keys": """
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME,
                   REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, COLUMN_NAME;
        """,
        "indexes": """
            SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX,
                   COLUMN_NAME, COLLATION, CARDINALITY
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
        """,
        "routines": """
            SELECT ROUTINE_TYPE, ROUTINE_NAME, DATA_TYPE
            FROM information_schema.ROUTINES
            WHERE ROUTINE_SCHEMA = DATABASE()
            ORDER BY ROUTINE_TYPE, ROUTINE_NAME;
        """,
        "triggers": """
            SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE,
                   ACTION_TIMING
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
            ORDER BY EVENT_OBJECT_TABLE, TRIGGER_NAME;
        """,
    }

    for name, sql in queries.items():
        ok, rows, err = mysql_query_tsv(mysql, db, sql)
        result["queries"][name] = {
            "ok": ok,
            "rows": rows,
            "error": err if not ok else None,
        }

    # Exact target table requested in the previous phase.
    for table in ["instructorcourse", "courses", "users", "students", "instructors",
                  "notifications", "academic_calendar"]:
        sql = f"""
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                   COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{table}'
            ORDER BY ORDINAL_POSITION;
        """
        ok, rows, err = mysql_query_tsv(mysql, db, sql)
        result["queries"][f"describe_{table}"] = {
            "ok": ok,
            "rows": rows,
            "error": err if not ok else None,
        }

    # Read-only counts for all tables, useful for spotting empty/legacy tables.
    table_rows = result["queries"].get("database_tables", {}).get("rows", [])
    counts = []
    for row in table_rows:
        table = row.get("TABLE_NAME", "")
        if not re.fullmatch(r"[A-Za-z0-9_]+", table):
            continue
        ok, rows, err = mysql_query_tsv(
            mysql, db,
            f"SELECT COUNT(*) AS row_count FROM `{table}`;"
        )
        counts.append({
            "table": table,
            "ok": ok,
            "row_count": int(rows[0]["row_count"]) if ok and rows else None,
            "error": err if not ok else None,
        })
    result["row_counts"] = counts

    # Specific read-only sample from instructorcourse, because the previous
    # phase showed that IDs are stored as VARCHAR and values look name-based.
    ok, rows, err = mysql_query_tsv(
        mysql, db,
        """
        SELECT id, name, userInstructorID, courseID
        FROM instructorcourse
        ORDER BY id
        LIMIT 200;
        """
    )
    result["queries"]["instructorcourse_sample"] = {
        "ok": ok,
        "rows": rows,
        "error": err if not ok else None,
    }

    return result


def database_findings(dbinfo: Dict[str, Any], findings: List[Dict[str, Any]]) -> None:
    if not dbinfo.get("available"):
        add_finding(
            findings, "WARNING", "database",
            "MySQL unavailable",
            "Database analysis could not run. Re-run with the correct mysql.exe path."
        )
        return

    q = dbinfo.get("queries", {})

    desc = q.get("describe_instructorcourse", {})
    rows = desc.get("rows", [])
    if rows:
        names = {r["COLUMN_NAME"] for r in rows}
        if {"userInstructorID", "courseID"} <= names:
            types = {
                r["COLUMN_NAME"]: r.get("COLUMN_TYPE", "")
                for r in rows
            }
            if "varchar" in types.get("userInstructorID", "").lower():
                add_finding(
                    findings, "INFO", "schema",
                    "instructorcourse.userInstructorID",
                    "This column is VARCHAR rather than a numeric foreign key.",
                    types.get("userInstructorID")
                )
            if "varchar" in types.get("courseID", "").lower():
                add_finding(
                    findings, "INFO", "schema",
                    "instructorcourse.courseID",
                    "This column is VARCHAR rather than a numeric foreign key.",
                    types.get("courseID")
                )

    sample = q.get("instructorcourse_sample", {}).get("rows", [])
    if sample:
        looks_like_names = 0
        for r in sample:
            instructor = str(r.get("userInstructorID", ""))
            course = str(r.get("courseID", ""))
            if instructor.startswith("Dr.") or " " in instructor:
                looks_like_names += 1
            if course and not course.isdigit():
                looks_like_names += 1
        if looks_like_names:
            add_finding(
                findings, "INFO", "data-model",
                "instructorcourse sample",
                "Current sample values appear to store instructor/course names in ID-named VARCHAR columns.",
                sample[:20]
            )

    counts = dbinfo.get("row_counts", [])
    empty = [x["table"] for x in counts if x.get("ok") and x.get("row_count") == 0]
    if empty:
        add_finding(
            findings, "INFO", "database",
            "empty tables",
            f"{len(empty)} tables currently have zero rows.",
            empty
        )


# ---------------------------------------------------------------------------
# PHP lint
# ---------------------------------------------------------------------------

def php_lint(root: Path, files: List[Path], php: str) -> Dict[str, Any]:
    php_files = [p for p in files if p.suffix.lower() in PHP_EXTENSIONS]

    if not (Path(php).exists() or shutil.which(php)):
        return {
            "available": False,
            "php_path": php,
            "checked": 0,
            "failures": [],
            "skipped_reason": "php.exe not found",
        }

    failures = []
    checked = 0

    # Avoid extremely large/generated PHP files.
    for p in php_files:
        try:
            if p.stat().st_size > 3_000_000:
                continue
        except OSError:
            continue

        checked += 1
        result = run_cmd([php, "-l", str(p)], cwd=root, timeout=15)
        if not result["ok"]:
            failures.append({
                "file": rel(root, p),
                "returncode": result["returncode"],
                "stdout": result["stdout"],
                "stderr": result["stderr"],
            })

    return {
        "available": True,
        "php_path": php,
        "checked": checked,
        "failures": failures,
    }


# ---------------------------------------------------------------------------
# Pattern / architecture findings
# ---------------------------------------------------------------------------

def generate_findings(
    root: Path,
    inventory: Dict[str, Any],
    phpinfo: Dict[str, Any],
    includes: Dict[str, Any],
    dup: Dict[str, Any],
    focus: Dict[str, Any],
    lint: Dict[str, Any],
    findings: List[Dict[str, Any]],
) -> None:
    if phpinfo["malformed_patterns"]:
        add_finding(
            findings, "HIGH", "code-integrity",
            "header.phpy references",
            "Literal references to 'header.phpy' were found. This is likely a typo or stale migration artifact.",
            phpinfo["malformed_patterns"]
        )

    missing = includes["missing_literal_includes"]
    if missing:
        add_finding(
            findings, "HIGH", "include-integrity",
            "missing PHP includes",
            f"{len(missing)} literal PHP include/require paths could not be resolved.",
            missing[:100]
        )

    if phpinfo["duplicate_classes"]:
        add_finding(
            findings, "HIGH", "php-architecture",
            "duplicate class declarations",
            "The same PHP class name appears in multiple files.",
            phpinfo["duplicate_classes"]
        )

    if phpinfo["duplicate_functions"]:
        add_finding(
            findings, "MEDIUM", "php-architecture",
            "duplicate function names",
            "The same function name appears in multiple PHP files. This can cause redeclaration errors depending on load order.",
            phpinfo["duplicate_functions"]
        )

    if phpinfo["suspicious_files"]:
        add_finding(
            findings, "INFO", "repository-hygiene",
            "backup/duplicate candidates",
            "Backup/temp/duplicate-family files were found. These should be reviewed before final cleanup.",
            phpinfo["suspicious_files"][:150]
        )

    if dup["identical_file_groups"]:
        add_finding(
            findings, "INFO", "repository-hygiene",
            "identical files",
            "Some files have byte-identical content.",
            dup["identical_file_groups"][:100]
        )

    if phpinfo["dbcontroller_usage"]:
        add_finding(
            findings, "INFO", "database-access",
            "Student Dashboard DBController usage",
            f"{len(phpinfo['dbcontroller_usage'])} PHP files reference DBController.",
            phpinfo["dbcontroller_usage"]
        )

    if lint.get("available") and lint.get("failures"):
        add_finding(
            findings, "CRITICAL", "php-syntax",
            "PHP lint failures",
            f"{len(lint['failures'])} PHP files failed php -l.",
            lint["failures"]
        )

    # Exact file existence checks relevant to the previous commands.
    expected = [
        "core/DBController.php",
        "frontend/pages/common/calendar.php",
        "frontend/pages/common/notifications.php",
        "frontend/pages/Student Dashboard",
    ]
    for item in expected:
        if not (root / item).exists():
            add_finding(
                findings, "WARNING", "expected-path",
                item,
                "Expected path was not found in this branch."
            )


# ---------------------------------------------------------------------------
# Report rendering
# ---------------------------------------------------------------------------

def render_report(data: Dict[str, Any]) -> str:
    out: List[str] = []

    meta = data["meta"]
    out.append("EWLEARN / STUDENTS MANAGEMENT SYSTEM")
    out.append("PHASE 2C - FULL REPOSITORY + DATABASE INTELLIGENCE AUDIT")
    out.append(f"Generated: {meta['generated_at']}")
    out.append(f"Root: {meta['root']}")
    out.append(f"Python: {meta['python']}")
    out.append("READ-ONLY AUDIT: no application source or database data was intentionally modified.")

    inv = data["inventory"]
    out.append(section("1. REPOSITORY INVENTORY"))
    out.append(f"Files: {inv['file_count']}")
    out.append(f"Approx bytes: {inv['total_bytes']:,}")
    out.append("\nExtensions:")
    for k, v in inv["extensions"].items():
        out.append(f"  {k:20} {v}")
    out.append("\nTop-level file counts:")
    for k, v in inv["top_level_file_counts"].items():
        out.append(f"  {k:30} {v}")

    out.append(section("2. GIT STATE"))
    git = data["git"]
    for key in ["status", "branch", "log", "remotes", "diff_stat", "diff_name_only"]:
        r = git.get(key, {})
        out.append(f"[{key}]")
        out.append(r.get("stdout", "").rstrip() or r.get("stderr", "(no output)"))

    out.append(section("3. TARGET PROJECT STRUCTURE"))
    for path, info in data["targets"].items():
        out.append(f"\n{path}: {'EXISTS' if info['exists'] else 'MISSING'}")
        if info.get("exists"):
            for child in info.get("children", [])[:200]:
                out.append(f"  - {child}")

    out.append(section("4. TEACHER/STUDENT/ACADEMIC FOCUS PATHS"))
    for p in data["focus"]["matching_paths"]:
        out.append(f"  {p}")

    p = data["php_analysis"]
    out.append(section("5. PHP STATIC ANALYSIS"))
    out.append(f"PHP files: {p['php_file_count']}")
    out.append(f"Functions found: {p['functions_count']}")
    out.append(f"Literal includes found: {p['includes_count']}")

    out.append("\nDBController usage:")
    for item in p["dbcontroller_usage"]:
        out.append(
            f"  {item['file']} | new DBController={item['new_DBController_count']} | "
            f"requires={item['requires_DBController']}"
        )

    out.append("\nMalformed header.phpy:")
    for item in p["malformed_patterns"]:
        out.append(f"  {item}")

    out.append("\nDuplicate classes:")
    for k, v in p["duplicate_classes"].items():
        out.append(f"  {k}: {v}")

    out.append("\nDuplicate functions:")
    for k, v in p["duplicate_functions"].items():
        out.append(f"  {k}: {v}")

    out.append("\nSuspicious files:")
    for item in p["suspicious_files"][:200]:
        out.append(f"  {item}")

    out.append(section("6. INCLUDE INTEGRITY"))
    for item in data["includes"]["missing_literal_includes"]:
        out.append(f"MISSING: {item['file']} -> {item['include']}")
    if not data["includes"]["missing_literal_includes"]:
        out.append("No missing literal PHP includes detected.")

    out.append(section("7. IDENTICAL FILES"))
    groups = data["duplicates"]["identical_file_groups"]
    if not groups:
        out.append("No identical file groups detected.")
    else:
        for g in groups[:100]:
            out.append(f"SHA256 {g['sha256']}")
            for f in g["files"]:
                out.append(f"  - {f}")

    out.append(section("8. PHP LINT"))
    lint = data["php_lint"]
    out.append(f"Available: {lint.get('available')}")
    out.append(f"Checked: {lint.get('checked', 0)}")
    if lint.get("skipped_reason"):
        out.append(f"Skipped: {lint['skipped_reason']}")
    for f in lint.get("failures", []):
        out.append(f"\nFAIL: {f['file']}")
        out.append(f["stdout"])
        out.append(f["stderr"])

    out.append(section("9. DATABASE"))
    db = data["database"]
    out.append(f"MySQL available: {db.get('available')}")
    out.append(f"Database: {db.get('database')}")
    if db.get("error"):
        out.append(db["error"])

    if db.get("available"):
        tables = db["queries"].get("database_tables", {}).get("rows", [])
        out.append("\nTables:")
        for row in tables:
            out.append(
                f"  {row.get('TABLE_NAME')} | {row.get('ENGINE')} | "
                f"estimated_rows={row.get('TABLE_ROWS')}"
            )

        out.append("\nExact table row counts:")
        for row in db.get("row_counts", []):
            out.append(f"  {row['table']}: {row.get('row_count')}")

        for name in [
            "describe_instructorcourse",
            "describe_courses",
            "describe_users",
            "describe_students",
            "describe_instructors",
            "describe_notifications",
            "describe_academic_calendar",
        ]:
            q = db["queries"].get(name)
            if not q:
                continue
            out.append(f"\n{name}:")
            if not q["ok"]:
                out.append(f"  ERROR: {q['error']}")
            else:
                for row in q["rows"]:
                    out.append("  " + " | ".join(f"{k}={v}" for k, v in row.items()))

        out.append("\nInstructorcourse sample:")
        q = db["queries"].get("instructorcourse_sample", {})
        if q.get("ok"):
            for row in q.get("rows", []):
                out.append("  " + " | ".join(f"{k}={v}" for k, v in row.items()))
        else:
            out.append(f"  ERROR: {q.get('error')}")

        out.append("\nForeign keys:")
        for row in db["queries"].get("foreign_keys", {}).get("rows", []):
            out.append(
                f"  {row['TABLE_NAME']}.{row['COLUMN_NAME']} -> "
                f"{row['REFERENCED_TABLE_NAME']}.{row['REFERENCED_COLUMN_NAME']}"
            )

    out.append(section("10. SQL TABLE REFERENCES FOUND IN PHP"))
    for table, files in p["table_references"].items():
        out.append(f"{table}:")
        for f in files[:100]:
            out.append(f"  - {f}")

    out.append(section("11. FINDINGS / RISK MAP"))
    severity_order = {"CRITICAL": 0, "HIGH": 1, "MEDIUM": 2, "WARNING": 3, "INFO": 4}
    findings = sorted(
        data["findings"],
        key=lambda x: (severity_order.get(x["severity"], 99), x["category"], x["item"])
    )
    if not findings:
        out.append("No findings.")
    for i, f in enumerate(findings, 1):
        out.append(
            f"\n[{i}] {f['severity']} | {f['category']} | {f['item']}\n"
            f"    {f['detail']}"
        )
        if f.get("evidence") is not None:
            ev = json.dumps(f["evidence"], ensure_ascii=False, indent=2)
            # Keep text report manageable.
            if len(ev) > 5000:
                ev = ev[:5000] + "\n... [truncated; see JSON report]"
            out.append("    Evidence:\n" + "\n".join("    " + x for x in ev.splitlines()))

    out.append(section("12. NEXT STEP"))
    out.append(
        "Use this report as the single source of truth for the next migration/refactor "
        "decision. Do NOT start changing duplicate files, schema, or dashboard code "
        "until the findings have been reviewed."
    )

    return "\n".join(out)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(
        description="Full read-only EWLearn repository + database audit."
    )
    parser.add_argument(
        "--root",
        default=os.getcwd(),
        help="Project root. Default: current directory.",
    )
    parser.add_argument(
        "--db",
        default=DEFAULT_DB,
        help=f"MySQL database name. Default: {DEFAULT_DB}",
    )
    parser.add_argument(
        "--mysql",
        default=DEFAULT_MYSQL,
        help=f"Path to mysql.exe. Default: {DEFAULT_MYSQL}",
    )
    parser.add_argument(
        "--php",
        default=DEFAULT_PHP,
        help=f"Path to php.exe. Default: {DEFAULT_PHP}",
    )
    parser.add_argument(
        "--no-db",
        action="store_true",
        help="Skip all database queries.",
    )
    parser.add_argument(
        "--no-lint",
        action="store_true",
        help="Skip PHP syntax lint.",
    )
    args = parser.parse_args()

    root = Path(args.root).resolve()
    if not root.exists():
        print(f"ERROR: root does not exist: {root}", file=sys.stderr)
        return 2

    stamp = now_stamp()
    logs = root / "migration" / "logs"
    logs.mkdir(parents=True, exist_ok=True)

    files = walk_files(root)
    inventory = repo_inventory(root, files)
    phpinfo = php_analysis(root, files)
    includes = include_integrity(root, files)
    dup = duplicate_files(root, files)
    git = git_analysis(root)
    targets = target_paths(root)
    focus = teacher_student_focus(root, files)
    lint = (
        {"available": False, "checked": 0, "failures": [], "skipped_reason": "--no-lint"}
        if args.no_lint
        else php_lint(root, files, args.php)
    )

    database = (
        {
            "mysql_path": args.mysql,
            "database": args.db,
            "available": False,
            "skipped": True,
            "error": "--no-db",
            "queries": {},
        }
        if args.no_db
        else database_analysis(args.mysql, args.db)
    )

    findings: List[Dict[str, Any]] = []
    generate_findings(
        root, inventory, phpinfo, includes, dup, focus, lint, findings
    )
    database_findings(database, findings)

    # Add a read-only warning if the repository contains SQL with destructive
    # statements. This does not execute SQL; it only identifies migration risk.
    sql_risky = []
    for p in files:
        if p.suffix.lower() != ".sql":
            continue
        text = safe_read(p)
        if not text:
            continue
        hits = []
        for pat in DANGEROUS_WRITE_PATTERNS:
            if re.search(pat, text, re.I):
                hits.append(pat)
        if hits:
            sql_risky.append({"file": rel(root, p), "patterns": hits})

    if sql_risky:
        add_finding(
            findings, "WARNING", "migration-safety",
            "SQL files contain write/DDL statements",
            "These statements were NOT executed by this audit; review before running migration scripts.",
            sql_risky[:100]
        )

    data = {
        "meta": {
            "generated_at": dt.datetime.now().astimezone().isoformat(),
            "root": str(root),
            "python": sys.version,
            "platform": sys.platform,
            "audit_mode": "read-only",
        },
        "inventory": inventory,
        "git": git,
        "targets": targets,
        "focus": focus,
        "php_analysis": phpinfo,
        "includes": includes,
        "duplicates": dup,
        "php_lint": lint,
        "database": database,
        "findings": findings,
    }

    txt_path = logs / f"phase2c_full_audit_{stamp}.txt"
    json_path = logs / f"phase2c_full_audit_{stamp}.json"

    txt_path.write_text(render_report(data), encoding="utf-8")
    json_path.write_text(
        json.dumps(data, ensure_ascii=False, indent=2, default=str),
        encoding="utf-8",
    )

    print("\n" + "=" * 90)
    print("PHASE 2C FULL AUDIT COMPLETE")
    print("=" * 90)
    print(f"Project root : {root}")
    print(f"Files scanned: {len(files)}")
    print(f"PHP files    : {phpinfo['php_file_count']}")
    print(f"DB available : {database.get('available')}")
    print(f"Findings     : {len(findings)}")
    print(f"Text report  : {txt_path}")
    print(f"JSON report  : {json_path}")
    print("=" * 90)
    print("No application source files or database rows were intentionally modified.")
    print("Paste the final terminal output here and/or upload the generated TXT report.")
    print("The JSON report is also useful if we need machine-readable follow-up.")
    print("=" * 90)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
