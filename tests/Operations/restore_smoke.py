"""Run only inside a disposable image with an isolated PostgreSQL test service."""

import importlib.util
import os
from pathlib import Path
import tempfile

spec = importlib.util.spec_from_file_location("backup", "/app/ops/backup.py")
backup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(backup)

os.umask(0o077)
with tempfile.TemporaryDirectory(prefix="backup-smoke-") as temporary:
    root = Path(temporary)
    uploads = root / "var/uploads/dogs/1"
    uploads.mkdir(parents=True)
    payload = b"Disposable upload fixture\x00\xff"
    (uploads / "sample.jpg").write_bytes(payload)
    source = {"DATABASE_URL": "postgresql://postgres:backup-test-only@127.0.0.1:5432/backup_source"}
    pg = backup.postgres_settings(source)
    admin = dict(pg, PGDATABASE="postgres")
    for name in ("backup_source", "backup_restored"):
        backup.command(["psql", "-v", "ON_ERROR_STOP=1", "-c", f"CREATE DATABASE {name}"], admin)
    backup.command(["psql", "-v", "ON_ERROR_STOP=1", "-c",
                    "CREATE TABLE media (id integer PRIMARY KEY, storage_key text NOT NULL); "
                    "INSERT INTO media VALUES (1, 'dogs/1/sample.jpg')"], pg)
    env = {"PATH": os.environ["PATH"], "RESTIC_REPOSITORY": str(root / "repository"),
           "RESTIC_PASSWORD": "disposable-test-password-not-a-secret"}
    backup.restic(["init"], env)
    snapshot = backup.backup(root, env, source)
    backup.status(env)
    backup.restic(["check", "--read-data"], env)
    restored = backup.restore(root, snapshot, env)
    dump = next(restored.rglob("database.dump"))
    recovered_upload = next(restored.rglob("sample.jpg"))
    assert recovered_upload.read_bytes() == payload
    target = dict(pg, PGDATABASE="backup_restored")
    backup.command(["pg_restore", "--no-owner", "--no-acl", "--exit-on-error",
                    "--dbname", "backup_restored", str(dump)], target)
    rows = backup.command(["psql", "-At", "-c", "SELECT id, storage_key FROM media"], target)
    assert rows.strip() == "1|dogs/1/sample.jpg", rows
    backup.retention(env)
    print("PASS: encrypted snapshot, full integrity check, PostgreSQL restore and upload bytes")
