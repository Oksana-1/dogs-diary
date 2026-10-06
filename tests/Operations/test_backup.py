import datetime as dt
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location("backup", Path(__file__).resolve().parents[2] / "ops/backup.py")
backup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(backup)


class BackupTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        (self.root / "var/uploads/dogs/1").mkdir(parents=True)
        (self.root / "var/uploads/dogs/1/photo.jpg").write_bytes(b"photo content")
        self.environ = {
            "DATABASE_URL": "postgresql://app:p%40ss@database:5432/app?serverVersion=16&charset=utf8",
            "BACKUP_R2_ENDPOINT": "https://" + "a" * 32 + ".r2.cloudflarestorage.com",
            "BACKUP_R2_BUCKET": "dogs-diary-backups",
            "BACKUP_R2_ACCESS_KEY_ID": "test-access",
            "BACKUP_R2_SECRET_ACCESS_KEY": "test-secret",
            "BACKUP_RESTIC_PASSWORD": "test-password-" * 4,
        }
        self.env = backup.settings(self.environ)

    def test_credentials_are_scoped_to_r2_and_do_not_include_app_or_database_secrets(self):
        env = backup.settings({**self.environ, "APP_SECRET": "private", "RESTIC_PASSWORD_COMMAND": "unsafe"})
        self.assertNotIn("APP_SECRET", env)
        self.assertNotIn("DATABASE_URL", env)
        self.assertNotIn("RESTIC_PASSWORD_COMMAND", env)
        self.assertEqual(env["RESTIC_REPOSITORY"], "s3:" + self.environ["BACKUP_R2_ENDPOINT"] + "/dogs-diary-backups/production")
        for endpoint in ("http://example.com", "https://example.com", self.environ["BACKUP_R2_ENDPOINT"] + "/bucket"):
            with self.subTest(endpoint=endpoint), self.assertRaises(backup.BackupError):
                backup.settings({**self.environ, "BACKUP_R2_ENDPOINT": endpoint})

    def test_database_url_decodes_password_and_filters_doctrine_parameters(self):
        env = backup.postgres_settings(self.environ)
        self.assertEqual(env["PGPASSWORD"], "p@ss")
        self.assertEqual(env["PGDATABASE"], "app")
        self.assertNotIn("serverVersion", env)
        with self.assertRaises(backup.BackupError):
            backup.postgres_settings({"DATABASE_URL": "sqlite:///:memory:"})
        with self.assertRaises(backup.BackupError):
            backup.postgres_settings({"DATABASE_URL": self.environ["DATABASE_URL"] + "&options=unexpected"})

    def test_paired_snapshot_contains_dump_files_and_manifest_and_releases_writes_before_upload(self):
        captured = []
        def pg_command(args, env, **kwargs):
            if args[0] == "pg_dump":
                Path(args[-1]).write_bytes(b"PGDMP-test")
            with self.assertRaises(backup.BackupError):
                with backup.exclusive_lock(self.root / "var/backup/writes.lock"):
                    pass
            return ""
        def restic(args, env, **kwargs):
            if args[0] == "backup":
                directory = kwargs["cwd"]
                captured.append(directory)
                self.assertEqual((directory / "database.dump").read_bytes(), b"PGDMP-test")
                self.assertEqual((directory / "uploads/dogs/1/photo.jpg").read_bytes(), b"photo content")
                self.assertEqual(json.loads((directory / "manifest.json").read_text())["uploads_files"], 1)
                with backup.exclusive_lock(self.root / "var/backup/writes.lock"):
                    pass
                return json.dumps({"message_type": "summary", "snapshot_id": "abc123"})
            return ""
        with patch.object(backup, "command", side_effect=pg_command), patch.object(backup, "restic", side_effect=restic):
            self.assertEqual(backup.backup(self.root, self.env, self.environ), "abc123")
        self.assertFalse(captured[0].exists())

    def test_failed_dump_never_uploads_a_snapshot_and_releases_lock(self):
        with patch.object(backup, "command", side_effect=backup.BackupError("dump failed")), patch.object(backup, "restic") as remote:
            with self.assertRaises(backup.BackupError):
                backup.backup(self.root, self.env, self.environ)
            remote.assert_not_called()
        with backup.exclusive_lock(self.root / "var/backup/writes.lock"):
            pass

    def test_symlink_is_rejected_without_exporting_external_files(self):
        (self.root / "var/uploads/outside").symlink_to(self.root / "private")
        destination = self.root / "capture"
        destination.mkdir()
        with patch.object(backup, "command") as pg:
            with self.assertRaises(backup.BackupError):
                backup.capture(self.root, destination, self.environ)
            pg.assert_not_called()

    def test_upload_failure_cleans_plaintext_staging_and_reports_failure(self):
        captures = []
        def capture(root, destination, environ):
            captures.append(destination)
            (destination / "private.dump").write_text("private data")
        with patch.object(backup, "capture", side_effect=capture), patch.object(backup, "restic", side_effect=backup.BackupError("upload failed")):
            with self.assertRaises(backup.BackupError):
                backup.backup(self.root, self.env, self.environ)
        self.assertFalse(captures[0].exists())

    def test_retention_preview_does_not_prune_and_is_scoped_to_our_snapshots(self):
        with patch.object(backup, "restic", return_value="preview") as remote:
            backup.retention(self.env)
        args = remote.call_args.args[0]
        self.assertIn("--dry-run", args)
        self.assertNotIn("--prune", args)
        self.assertIn(backup.HOST, args)
        self.assertIn(backup.TAG, args)

    def test_missing_and_stale_backups_fail_status(self):
        old = (dt.datetime.now(dt.timezone.utc) - dt.timedelta(hours=27)).isoformat()
        recent = dt.datetime.now(dt.timezone.utc).isoformat()
        for snapshots in ([], [{"id": "old", "time": old}]):
            with patch.object(backup, "restic", return_value=json.dumps(snapshots)), self.assertRaises(backup.BackupError):
                backup.status(self.env)
        with patch.object(backup, "restic", return_value=json.dumps([{"id": "new", "time": recent}])):
            backup.status(self.env)

    def test_tool_failure_does_not_expose_stderr_or_stdout(self):
        with self.assertRaises(backup.BackupError) as error:
            backup.command([sys.executable, "-c", "import sys; print('secret'); sys.stderr.write('password'); sys.exit(4)"], dict(os.environ))
        self.assertNotIn("secret", str(error.exception))
        self.assertNotIn("password", str(error.exception))
        self.assertIn("exit 4", str(error.exception))

    def test_scheduler_uses_utc_daily_boundary(self):
        now = dt.datetime(2026, 10, 6, 2, 59, tzinfo=dt.timezone.utc)
        self.assertEqual(backup.next_run(now, 3), now.replace(hour=3, minute=0))
        now = now.replace(hour=3, minute=0)
        self.assertEqual(backup.next_run(now, 3), now + dt.timedelta(days=1))

    def test_supervised_process_is_stopped(self):
        process = subprocess.Popen([sys.executable, "-c", "import time; time.sleep(60)"], start_new_session=True)
        self.addCleanup(lambda: backup.stop_process(process))
        backup.stop_process(process)
        self.assertIsNotNone(process.poll())


if __name__ == "__main__":
    unittest.main()
