# Private R2 media storage

Status: local/R2 adapters and direct browser uploads are implemented. Local
filesystem storage remains the default. Live R2 connectivity and synthetic
retained-object recovery passed; browser CORS and coordinated DB/media recovery
are still unverified. The Vercel entrypoint now supports fail-closed
production startup and a separate health-only R2 diagnostic. Neither constitutes
a completed user-facing production release.

This bucket will contain live photos and videos. Keep it separate from the
encrypted restic repository described in [R2_BACKUPS.md](R2_BACKUPS.md).

## Cloudflare setup

1. Open Cloudflare Dashboard > R2 object storage > Overview > Create bucket.
   Use `dogs-diary-media` unless a dedicated live-media bucket already exists.
   Choose Standard storage. Confirm any activation/billing terms before enabling
   R2; its free allowance is not an unlimited spending cap.
2. Keep public access disabled: no public `r2.dev` URL or public custom domain.
   The application will authorize access and issue short-lived signed URLs.
3. Under R2 > Account Details > API Tokens > Manage, create a token with
   **Object Read & Write**, scoped to this media bucket only. Save the generated
   Access Key ID and Secret Access Key in your password manager. The Secret
   Access Key is shown only once. The application does not need bucket-admin
   permissions or access to the backup bucket.
4. Copy the bucket's S3 API endpoint from its settings. Use the displayed
   endpoint, including the jurisdiction suffix if present. SDK region is `auto`.
5. In the bucket's Settings > CORS policy, use the policy below after replacing
   the origin with the exact production website origin. If the deployed site is
   `https://dogsdiary.app`, use that value. Add another origin only when the app
   will actually run there. An origin has no path or trailing slash.

```json
[
  {
    "AllowedOrigins": ["https://YOUR-PRODUCTION-ORIGIN.example"],
    "AllowedMethods": ["GET", "HEAD", "PUT"],
    "AllowedHeaders": ["Content-Type", "Range"],
    "ExposeHeaders": ["ETag", "Content-Length", "Accept-Ranges", "Content-Range"],
    "MaxAgeSeconds": 3600
  }
]
```

CORS permits browser requests; it does not make the bucket public or replace
authorization. The browser receives signed URLs, never the permanent access keys.
Browser deletion is handled through the application's authenticated API.

## Vercel configuration

Set these names in the existing project's **Production** environment:

| Variable | Value |
| --- | --- |
| `MEDIA_STORAGE` | `r2` for the production R2 configuration |
| `MEDIA_R2_ENDPOINT` | Exact S3 endpoint copied from the bucket settings |
| `MEDIA_R2_REGION` | `auto` |
| `MEDIA_R2_BUCKET` | `dogs-diary-media`, or the chosen media bucket name |
| `MEDIA_R2_ACCESS_KEY_ID` | Bucket-scoped Access Key ID; store as sensitive |
| `MEDIA_R2_SECRET_ACCESS_KEY` | Bucket-scoped Secret Access Key; store as sensitive |

The code reads these variables at runtime and rejects invalid R2 settings.
Adding variables does not change an already-built deployment. Do not redeploy
production until the remaining live checks and production settings are in place. Use Vercel's environment
variable editor for keys; do not send them in chat, commit them, or put them in
command-line arguments. Existing `BACKUP_R2_*` credentials remain separate.

The only setup information needed in chat is the bucket name, S3 endpoint,
production website origin, and whether the credentials are saved in Vercel.

Metadata-only check on 15 October: all five `MEDIA_R2_*` variables are present
in Vercel Production; both credentials are marked sensitive. `MEDIA_STORAGE`
is not present yet. No values were decrypted or printed, so their correctness
and bucket access have not been established.

## Application implementation

The existing product limits remain 10 MiB for images and 100 MiB for videos.
Vercel documents a 4.5 MB Function payload limit, including for its container
runtime's Function limits. The current multipart upload through the app cannot
serve as the production large-file upload path.

The implementation:

1. Authenticate the user and verify ownership before issuing an expiring upload
   ticket and a signed PUT URL for a random staging object.
2. Upload the file directly from the browser to R2. The Vue repositories handle
   this without exposing storage details in the UI.
3. Finalize through an authenticated, CSRF-protected API request. Verify the
   ticket's owner, target, expiry and completion state; inspect actual file bytes
   and size with the existing validation rules. Publish validated bytes under a
   new canonical storage key so a reusable staging URL cannot overwrite them.
4. Store metadata and ticket completion in the same database transaction after
   validation succeeds. A database-backed lock prevents concurrent completion of
   one ticket. Replays return its existing media, never re-read staged bytes;
   deleted media cannot be resurrected. Treatment replacements reuse the existing
   photo row under the existing treatment lock.
5. Keep the existing owner-scoped media URLs. Authorize each request, then return
   an uncached 307 redirect to a 60-second signed GET URL. R2 serves file bytes
   and ranges. Already-issued URLs remain bearer links until expiry, even after
   logout or ownership changes; fresh application requests recheck ownership.
6. Support R2 existence checks and paginated media audit while keeping filesystem
   storage for local development and tests. Existing local files are not imported
   automatically; a controlled key-preserving importer remains separate work.

Both media routes use `POST .../media/uploads` to initiate and
`POST .../media/uploads/{uploadId}/complete` to finalize. The existing multipart
endpoint remains available only for local storage. Tickets and signed PUT URLs
expire after ten minutes. Actual size is checked before downloading staged
bytes, then bounded while streaming and checked against validated metadata.

Ticket issuance and incomplete-ticket finalization each have a separate limit
of 30 attempts per authenticated user per hour, shared across instances. Completed
replays do not consume the finalization allowance. Expired ticket rows are
removed on subsequent initiation after a one-day grace period.

These are application quotas, not a Cloudflare spending cap. SDK-signed PUT URLs
do not enforce a browser-provided MIME type or Content-Length at the R2 edge.
A malicious user can upload oversized staging objects; finalization rejects
them, but temporary storage/operations can still incur cost. Configure account
alerts, review this exposure before public registration, and apply the staging
lifecycle below only after verifying the exact prefixes.

Affected code includes `MediaStorageInterface`, `LocalMediaStorage`, a new R2
adapter, `PrivateMediaResponseFactory`, `AuditMediaCommand`, dog/treatment media
services and controllers, the upload-ticket persistence/migration, and both Vue
media repositories. Before frontend test changes, follow
`tests/JavaScript/TESTING.md`.

## Deletion, retention, and migration

The accepted database recovery window is six hours. Before deleting a canonical
R2 object, the adapter copies it to
`retained/<UTC-deletion-time>/<random-id>/<original-storage-key>`. A failed copy
prevents removal of the original. Retention therefore starts at removal, not
at the file's original creation time. Failures after database deletion are logged
and leave the original object available for audit/recovery.

Initially leave lifecycle rules unchanged. After checking the bucket contents,
configure expiration of `staging/` after one day and `retained/` after at least
one day. Never apply expiration to the bucket root, `dogs/` or `treatments/`.
Without these rules, temporary/retained objects remain stored indefinitely.
Ordinary media audit lists only canonical `dogs/` and `treatments/` keys; it does
not delete staging or retained copies.

Recovery procedure (requires separate authorization for the target environment):

1. Stop writes and restore the selected database recovery point in isolation.
2. Run read-only `php bin/console app:media:audit` against that restored database
   and its matching R2 configuration. Do not use `--delete-orphans` during recovery.
3. For each missing canonical key, locate the retained object's matching key
   suffix. Copy its bytes back to the exact canonical key only if that key is
   absent. Never overwrite a live object or remove the retained source. Verify
   size and SHA-256 against the corresponding backup manifest/source bytes;
   if these are unavailable, flag that verification gap instead of claiming
   complete recovery.
4. Rerun the audit and authorized media checks before reopening writes.

The object-copy/restore portion passed with real R2 synthetic objects on 15 October.
Coordinated database/media recovery still needs an isolated rehearsal.
The existing filesystem/restic backup job does not capture R2 live media;
retained copies are not an independent backup. Do not schedule that job as an
R2 backup or treat bucket retention alone as demonstrated disaster recovery.

Existing archives stay private and intact. After the integration is tested,
prepare a controlled media import preserving storage keys; compare counts,
sizes, SHA-256 values and the database/media audit before changing live reads.
Source media are not deleted by the import. A database restore must be rehearsed
with the corresponding retained media.

## Verification before production

- Owner isolation and CSRF on ticket creation/finalization; unsigned bucket reads fail.
- Image/video size limits, content validation, corrupt files and forged metadata.
- Expired/foreign/replayed tickets, overwritten staging objects and partial uploads.
- Dog media CRUD, thumbnails/profile media and concurrent treatment-photo replacement.
- Download ranges, signed URL expiry, refresh and private cache behavior.
- Delete/replace recovery across the accepted six-hour database window.
- `bin/check`, the isolated PostgreSQL job in [CI.md](CI.md), and the current image build.
- A bounded R2 connectivity check with synthetic objects before private media migration.

Local verification on 15 October 2026: the configured quality gate, disposable
PostgreSQL migration/schema/baseline checks, and Vercel image build passed. Live
bucket permissions and synthetic range/copy/restore passed in the hosted check
below. Browser CORS, large uploads, sustained video playback, bucket lifecycle
policy verification and coordinated DB/media recovery remain release gates.

Exact passing commands (PHP commands used isolated test environment settings;
PostgreSQL targeted only a disposable localhost:15433 test container):

```bash
bin/check --local
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:schema:validate
php tests/Migrations/verify_baseline.php
docker build --platform linux/amd64 -f Dockerfile.vercel -t dogs-diary:vercel-runtime-check .
git diff --check
```

The final quality gate passed 128 PHP tests / 1,058 assertions, eight frontend
tests, 23 operations tests, Composer validation/audit and all configured linters.
All eight PostgreSQL baseline/adoption scenarios passed. The disposable test
container was removed afterward; development and hosted databases were untouched.

The updated runtime also passed `python3 -B tests/Operations/shared_state_smoke.py`
with two new disposable containers and a synthetic PostgreSQL 18 database:
cross-instance CSRF/login/logout/reset, restarts, session expiry and concurrent
login throttling. The test containers/network were removed afterward. The
diagnostic Caddy config separately returned 204 for `/healthz` and 503 for an
application route locally.

## Hosted synthetic verification — 15 October 2026

Deployment `dpl_GNWDyE7rGACWp7rL9XZDcRG2tth7` reached READY at
`https://dogs-diary-kacipkgfw-4438488-gmailcoms-projects.vercel.app`, using saved
Production secrets without retrieving them. Vercel authentication protects it.
`vercel curl /healthz --deployment <diagnostic-host>` returned 204;
`vercel curl /api/dogs --deployment <diagnostic-host>` returned 503 as intended.
Deployment-scoped structured logs reported all nine checks passed:

- Signed PUT and staged-byte validation.
- Signed GET and byte-range GET.
- Denial of unsigned reads of the synthetic object.
- Listing only the new synthetic canonical-key prefix.
- Retain-before-delete, byte-identical restore, and cleanup verified with HEAD.

Only random synthetic objects were touched, including canonical keys under
`dogs/<PHP_INT_MAX>/`; all their staging, canonical and retained objects were
removed and absence verified. No existing media, application database or mail
was accessed. This was not an end-to-end browser/application test or full
disaster-recovery rehearsal.

Vercel unexpectedly moved the default project alias despite `--skip-domain`.
It was restored to the prior deployment `dpl_CgKXXHxWNSw4fD8SqdAU1xqtokpF`
and verified through alias metadata. A rollback request was rejected because
Vercel already considered that prior deployment current; explicit alias
assignment performed the required restoration. Do not promote the diagnostic
or assume `--skip-domain` alone preserves alias mappings in this setup.

Cloudflare setup and application implementation are separate from production
deployment, migrations against live data, DNS changes and opening live writes.

## Sources

- [Create buckets](https://developers.cloudflare.com/r2/buckets/create-buckets/)
- [R2 credentials and permissions](https://developers.cloudflare.com/r2/api/tokens/)
- [CORS configuration](https://developers.cloudflare.com/r2/buckets/cors/)
- [PHP S3 SDK configuration](https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/)
- [Object lifecycles](https://developers.cloudflare.com/r2/buckets/object-lifecycles/)
- [R2 pricing](https://developers.cloudflare.com/r2/pricing/)
- [Vercel Function limits](https://vercel.com/docs/functions/limitations)
- [Vercel container runtime](https://vercel.com/docs/functions/runtimes/container)
