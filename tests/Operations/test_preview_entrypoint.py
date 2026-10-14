"""Preview startup configuration checks; no Docker, database, or mail access."""

import os
from pathlib import Path
import subprocess
import tempfile
import unittest


ENTRYPOINT = Path(__file__).resolve().parents[2] / "docker/vercel-preview-entrypoint.sh"


class PreviewEntrypointTests(unittest.TestCase):
    def run_entrypoint(self, extra):
        with tempfile.TemporaryDirectory() as directory:
            fake_php = Path(directory) / "php"
            fake_php.write_text(
                '#!/bin/sh\n'
                'printf "%s|%s|%s|%s\\n" "$APP_BASE_URL" "$MAILER_DSN" "$BACKUP_SCHEDULE_ENABLED" "$*"\n'
            )
            fake_php.chmod(0o700)
            environment = {
                "PATH": directory + os.pathsep + "/usr/bin:/bin",
                "APP_SECRET": "preview-startup-test-only-secret-2026",
                "DATABASE_URL": "postgresql://test:test@db.invalid/test?sslmode=require",
                "VERCEL_ENV": "preview",
                **extra,
            }
            return subprocess.run(
                ["sh", str(ENTRYPOINT), "true"], env=environment,
                text=True, capture_output=True, check=False,
            )

    def test_hosted_preview_uses_its_own_https_origin_and_disables_mail(self):
        result = self.run_entrypoint({
            "VERCEL": "1", "VERCEL_URL": "preview-123.vercel.app",
            "MAILER_DSN": "smtp://must-not-send.invalid", "BACKUP_SCHEDULE_ENABLED": "1",
        })
        self.assertEqual(0, result.returncode, result.stderr)
        lines = result.stdout.splitlines()
        self.assertEqual(3, len(lines))
        self.assertTrue(all(line.startswith("https://preview-123.vercel.app|null://null|0|") for line in lines))
        self.assertIn("--preview --require-tls", lines[0])

    def test_explicit_origin_is_preserved(self):
        result = self.run_entrypoint({
            "VERCEL": "1", "VERCEL_URL": "preview-123.vercel.app",
            "APP_BASE_URL": "https://custom-preview.example.test",
        })
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(result.stdout.startswith("https://custom-preview.example.test|"))

    def test_local_startup_still_requires_an_explicit_origin(self):
        result = self.run_entrypoint({"VERCEL_URL": "untrusted-local.example.test"})
        self.assertNotEqual(0, result.returncode)
        self.assertIn("APP_BASE_URL", result.stderr)
        self.assertEqual("", result.stdout)

    def test_production_is_rejected_before_startup(self):
        result = self.run_entrypoint({"VERCEL": "1", "VERCEL_ENV": "production"})
        self.assertNotEqual(0, result.returncode)
        self.assertIn("Preview only", result.stderr)
        self.assertEqual("", result.stdout)


if __name__ == "__main__":
    unittest.main()
