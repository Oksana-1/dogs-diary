"""Vercel startup configuration checks; no Docker, database, mail or R2 access."""

import os
from pathlib import Path
import subprocess
import tempfile
import unittest


ENTRYPOINT = Path(__file__).resolve().parents[2] / "docker/vercel-entrypoint.sh"


class VercelEntrypointTests(unittest.TestCase):
    def run_entrypoint(self, extra):
        with tempfile.TemporaryDirectory() as directory:
            fake_php = Path(directory) / "php"
            fake_php.write_text(
                '#!/bin/sh\n'
                'printf "%s|%s|%s\\n" "${APP_BASE_URL:-}" "${MAILER_DSN:-}" "$*"\n'
                'exit "${FAKE_PHP_EXIT:-0}"\n'
            )
            fake_php.chmod(0o700)
            fake_server = Path(directory) / "frankenphp"
            fake_server.write_text('#!/bin/sh\nprintf "server:%s\\n" "$*"\n')
            fake_server.chmod(0o700)
            environment = {
                "PATH": directory + os.pathsep + "/usr/bin:/bin",
                "APP_SECRET": "preview-startup-test-only-secret-2026",
                "DATABASE_URL": "postgresql://test:test@db.invalid/test?sslmode=require",
                "VERCEL_ENV": "preview",
                **extra,
            }
            return subprocess.run(
                ["sh", str(ENTRYPOINT), "frankenphp", "run", "--config", "/etc/caddy/Caddyfile"], env=environment,
                text=True, capture_output=True, check=False,
            )

    def test_hosted_preview_uses_its_own_https_origin_and_disables_mail(self):
        result = self.run_entrypoint({
            "VERCEL": "1", "VERCEL_URL": "preview-123.vercel.app",
            "MAILER_DSN": "smtp://must-not-send.invalid",
        })
        self.assertEqual(0, result.returncode, result.stderr)
        lines = result.stdout.splitlines()
        self.assertEqual(4, len(lines))
        self.assertTrue(all(line.startswith("https://preview-123.vercel.app|null://null|") for line in lines[:3]))
        self.assertIn("--preview --require-tls", lines[0])
        self.assertEqual("server:run --config /etc/caddy/Caddyfile", lines[3])

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

    def test_production_requires_an_explicit_canonical_origin(self):
        result = self.run_entrypoint({"VERCEL": "1", "VERCEL_ENV": "production"})
        self.assertNotEqual(0, result.returncode)
        self.assertIn("APP_BASE_URL", result.stderr)
        self.assertEqual("", result.stdout)

    def production_environment(self):
        return {
            "VERCEL": "1", "VERCEL_ENV": "production",
            "APP_BASE_URL": "https://diary.example.test",
            "MAILER_DSN": "smtp://must-remain-configured.invalid",
            "MAILER_FROM_ADDRESS": "hello@example.test",
            "MEDIA_STORAGE": "r2",
        }

    def test_production_preserves_mail_and_requires_tls_and_r2(self):
        result = self.run_entrypoint(self.production_environment())
        self.assertEqual(0, result.returncode, result.stderr)
        lines = result.stdout.splitlines()
        self.assertEqual(4, len(lines))
        self.assertTrue(all(line.startswith("https://diary.example.test|smtp://must-remain-configured.invalid|") for line in lines[:3]))
        self.assertIn("--require-tls --require-r2", lines[0])
        self.assertEqual("server:run --config /etc/caddy/Caddyfile", lines[3])
        self.assertNotIn("--preview", result.stdout)
        self.assertNotIn("migrations", result.stdout)

    def test_production_rejects_local_media_and_missing_mail(self):
        for changes in ({"MEDIA_STORAGE": "local"}, {"MAILER_DSN": ""}):
            result = self.run_entrypoint({**self.production_environment(), **changes})
            self.assertNotEqual(0, result.returncode)
            self.assertEqual("", result.stdout)

    def test_failed_preflight_never_warms_cache_or_starts_http_service(self):
        result = self.run_entrypoint({
            **self.production_environment(), "FAKE_PHP_EXIT": "7",
        })
        self.assertEqual(7, result.returncode)
        self.assertIn("app:production:check --require-tls --require-r2", result.stdout)
        self.assertNotIn("cache:", result.stdout)
        self.assertNotIn("server:", result.stdout)


if __name__ == "__main__":
    unittest.main()
