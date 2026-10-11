"""Encrypted, paired PostgreSQL/uploads backups, run inside the app container."""

import argparse
import contextlib
import datetime as dt
import fcntl
import json
import os
from pathlib import Path
import re
import shutil
import signal
import subprocess
import sys
import tempfile
import time
from urllib.parse import parse_qs, unquote, urlsplit

ROOT = Path(__file__).resolve().parent.parent
HOST = "dogs-diary-production"
TAG = "dogs-diary-paired"


class BackupError(Exception):
    pass


def log(message):
    print(f"{dt.datetime.now(dt.timezone.utc).isoformat()} backup: {message}", flush=True)


def settings(environ):
    required = ("BACKUP_R2_ENDPOINT", "BACKUP_R2_BUCKET", "BACKUP_R2_ACCESS_KEY_ID",
                "BACKUP_R2_SECRET_ACCESS_KEY", "BACKUP_RESTIC_PASSWORD")
    for name in required:
        if not environ.get(name):
            raise BackupError(f"Missing {name}")
    endpoint = environ["BACKUP_R2_ENDPOINT"].rstrip("/")
    if not re.fullmatch(r"https://[a-f0-9]{32}(?:\.(?:eu|us|fedramp))?\.r2\.cloudflarestorage\.com", endpoint):
        raise BackupError("BACKUP_R2_ENDPOINT must be an HTTPS R2 account endpoint, without a bucket path")
    bucket = environ["BACKUP_R2_BUCKET"]
    if not re.fullmatch(r"[a-z0-9][a-z0-9-]{1,61}[a-z0-9]", bucket):
        raise BackupError("Invalid BACKUP_R2_BUCKET")
    if len(environ["BACKUP_RESTIC_PASSWORD"]) < 32:
        raise BackupError("BACKUP_RESTIC_PASSWORD must have at least 32 characters")
    if environ.get("BACKUP_R2_REGION", "auto") != "auto":
        raise BackupError("BACKUP_R2_REGION must be auto")
    # Do not pass application secrets, PG overrides, or unrelated restic options.
    return {
        "PATH": os.environ.get("PATH", "/usr/local/bin:/usr/bin:/bin"),
        "LANG": "C.UTF-8",
        "RESTIC_REPOSITORY": f"s3:{endpoint}/{bucket}/production",
        "RESTIC_PASSWORD": environ["BACKUP_RESTIC_PASSWORD"],
        "AWS_ACCESS_KEY_ID": environ["BACKUP_R2_ACCESS_KEY_ID"],
        "AWS_SECRET_ACCESS_KEY": environ["BACKUP_R2_SECRET_ACCESS_KEY"],
        "AWS_DEFAULT_REGION": "auto",
    }


def postgres_settings(environ):
    try:
        url = urlsplit(environ.get("DATABASE_URL", ""))
        if url.scheme not in ("postgres", "postgresql") or not url.hostname or not url.username or not url.path.strip("/"):
            raise ValueError()
        result = {
            "PATH": os.environ.get("PATH", "/usr/local/bin:/usr/bin:/bin"),
            "PGHOST": url.hostname, "PGPORT": str(url.port or 5432),
            "PGUSER": unquote(url.username), "PGPASSWORD": unquote(url.password or ""),
            "PGDATABASE": unquote(url.path[1:]), "PGCONNECT_TIMEOUT": "15",
        }
        query = parse_qs(url.query)
        # Doctrine's serverVersion/charset are not libpq connection parameters.
        for key, value in query.items():
            if key in ("serverVersion", "charset"):
                continue
            if key != "sslmode" or len(value) != 1 or value[0] not in ("disable", "allow", "prefer", "require", "verify-ca", "verify-full"):
                raise ValueError()
            result["PGSSLMODE"] = value[0]
        return result
    except (ValueError, TypeError):
        raise BackupError("DATABASE_URL must be a PostgreSQL URL with supported connection options") from None


def command(args, env, cwd=None, timeout=1800):
    """Keep connection strings and tool diagnostics out of application logs."""
    try:
        process = subprocess.Popen(args, env=env, cwd=cwd, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL)
    except OSError:
        raise BackupError(f"Unable to start {args[0]}; check installed backup tools") from None
    try:
        output, _ = process.communicate(timeout=timeout)
    except BaseException:
        process.kill()
        process.wait()
        raise
    if process.returncode:
        raise BackupError(f"{args[0]} {args[1]} failed (exit {process.returncode}); check credentials, connectivity, space and repository state")
    return output.decode("utf-8")


def restic(args, env, **kwargs):
    return command(["restic", "--no-cache", "-o", "s3.region=auto", *args], env, **kwargs)


@contextlib.contextmanager
def exclusive_lock(path):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("a") as handle:
        try:
            fcntl.flock(handle, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise BackupError("Another backup or an application write is in progress; retry later") from None
        try:
            yield
        finally:
            fcntl.flock(handle, fcntl.LOCK_UN)


def capture(root, destination, environ):
    uploads = root / "var/uploads"
    if not uploads.is_dir() or uploads.is_symlink():
        raise BackupError("Uploads directory is missing or is a symlink")
    pg_env = postgres_settings(environ)
    with exclusive_lock(root / "var/backup/writes.lock"):
        # Symlinks could export files outside uploads or produce an unrestorable copy.
        if any(path.is_symlink() for path in uploads.rglob("*")):
            raise BackupError("Uploads contains a symlink; backup refused")
        command(["pg_dump", "--format=custom", "--no-owner", "--no-acl", "--file", str(destination / "database.dump")], pg_env, timeout=300)
        command(["pg_restore", "--list", str(destination / "database.dump")], pg_env, timeout=60)
        shutil.copytree(uploads, destination / "uploads")
        manifest = {
            "format": 1,
            "captured_at": dt.datetime.now(dt.timezone.utc).isoformat(),
            "commit": environ.get("RAILWAY_GIT_COMMIT_SHA", "unknown"),
            "postgres_major": 16,
            "uploads_files": sum(path.is_file() for path in (destination / "uploads").rglob("*")),
        }
        (destination / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
    # Network upload happens after the write lock is released.


def backup(root, env, environ):
    with tempfile.TemporaryDirectory(prefix="dogs-diary-backup-") as temporary:
        destination = Path(temporary)
        log("Capturing database and uploads")
        capture(root, destination, environ)
        log("Uploading encrypted snapshot")
        output = restic(["backup", "--json", "--host", HOST, "--tag", TAG,
                         "database.dump", "uploads", "manifest.json"], env, cwd=destination)
        summaries = [json.loads(line) for line in output.splitlines() if line.strip()]
        snapshot = next((item.get("snapshot_id") for item in summaries if item.get("message_type") == "summary"), None)
        if not snapshot:
            raise BackupError("restic returned no completed snapshot ID")
        restic(["check"], env)
        log(f"Snapshot {snapshot} saved; repository metadata check passed")
        return snapshot


def retention(env, dry_run=True):
    # Never expire raw R2 objects: restic snapshots share encrypted data chunks.
    restic(["check"], env)
    args = ["forget", "--host", HOST, "--tag", TAG, "--group-by", "host",
            "--keep-daily", "7", "--keep-weekly", "4"]
    args += ["--dry-run"] if dry_run else ["--prune"]
    print(restic(args, env), flush=True)


def status(env):
    snapshots = json.loads(restic(["snapshots", "--json", "--host", HOST, "--tag", TAG], env))
    if not snapshots:
        raise BackupError("No completed paired backups found")
    newest = max(snapshots, key=lambda item: item["time"])
    timestamp = dt.datetime.fromisoformat(newest["time"].replace("Z", "+00:00"))
    age = dt.datetime.now(dt.timezone.utc) - timestamp
    log(f"Latest snapshot {newest['id']}: {timestamp.isoformat()}")
    if age > dt.timedelta(hours=26):
        raise BackupError("Latest backup is older than 26 hours")


def restore(root, snapshot, env):
    if not re.fullmatch(r"[a-f0-9]{8,64}", snapshot):
        raise BackupError("Restore requires an explicit snapshot ID (8 to 64 hexadecimal characters)")
    directory = Path(tempfile.mkdtemp(prefix="restore-", dir=root / "var/backup"))
    restic(["restore", snapshot, "--target", str(directory), "--verify"], env)
    log(f"Restored files for inspection to {directory}; no database was changed")
    return directory


def stop_process(process):
    if process.poll() is None:
        os.killpg(process.pid, signal.SIGTERM)
        try:
            process.wait(timeout=10)
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGKILL)
            process.wait()


def next_run(now, hour):
    target = now.replace(hour=hour, minute=0, second=0, microsecond=0)
    return target if target > now else target + dt.timedelta(days=1)


def serve(server_args, environ):
    """Supervise the web process and one daily backup without a second volume mount."""
    if not server_args:
        raise BackupError("serve requires the web-server command")
    settings(environ)
    postgres_settings(environ)
    try:
        hour = int(environ.get("BACKUP_HOUR_UTC", "3"))
        if not 0 <= hour <= 23:
            raise ValueError()
    except ValueError:
        raise BackupError("BACKUP_HOUR_UTC must be an integer from 0 to 23") from None
    server_env = {key: value for key, value in environ.items() if not key.startswith("BACKUP_R2_") and key != "BACKUP_RESTIC_PASSWORD"}
    server = subprocess.Popen(server_args, env=server_env, start_new_session=True)
    active = None
    attempts = 0
    due = next_run(dt.datetime.now(dt.timezone.utc), hour)
    log(f"Daily schedule enabled; next run {due.isoformat()}")
    try:
        while server.poll() is None:
            if active is not None and active.poll() is not None:
                log(f"Scheduled job exited {active.returncode}")
                if active.returncode != 0 and attempts < 3:
                    due = dt.datetime.now(dt.timezone.utc) + dt.timedelta(minutes=5)
                    log(f"Retry scheduled for {due.isoformat()}")
                else:
                    attempts = 0
                active = None
            now = dt.datetime.now(dt.timezone.utc)
            if active is None and now >= due:
                active = subprocess.Popen([sys.executable, "-B", str(Path(__file__).resolve()), "run"], env=environ, start_new_session=True)
                attempts += 1
                due = next_run(now, hour)
            time.sleep(1)
        return server.returncode or 1
    finally:
        if active is not None:
            stop_process(active)
        stop_process(server)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("init", "run", "status", "check", "restore", "retention-preview", "prune", "serve"))
    parser.add_argument("server", nargs=argparse.REMAINDER)
    args = parser.parse_args()
    if args.action not in ("serve", "restore") and args.server:
        parser.error("Unexpected arguments")
    if args.action == "restore" and len(args.server) != 1:
        parser.error("restore requires one explicit snapshot ID")
    os.umask(0o077)
    def interrupted(signum, frame):
        raise SystemExit(128 + signum)
    signal.signal(signal.SIGTERM, interrupted)
    try:
        if os.geteuid() == 0:
            raise BackupError("Run as the application user: gosu www-data /app/bin/backup <action>")
        if args.action == "serve":
            return serve(args.server, dict(os.environ))
        env = settings(os.environ)
        with exclusive_lock(ROOT / "var/backup/job.lock"):
            if args.action == "init":
                restic(["init"], env)
                log("Encrypted repository initialized")
            elif args.action == "run":
                backup(ROOT, env, os.environ)
                if os.environ.get("BACKUP_RETENTION_ENABLED") == "1":
                    retention(env, dry_run=False)
            elif args.action == "status":
                status(env)
            elif args.action == "check":
                restic(["check", "--read-data"], env)
                log("Full repository integrity check passed")
            elif args.action == "restore":
                restore(ROOT, args.server[0], env)
            else:
                retention(env, dry_run=args.action == "retention-preview")
        return 0
    except (BackupError, OSError, ValueError, subprocess.TimeoutExpired) as error:
        # OSError can contain private paths; only our curated errors are logged.
        log(f"FAILED: {error if isinstance(error, BackupError) else type(error).__name__}")
        return 1


if __name__ == "__main__":
    sys.exit(main())
