# Private R2 media storage

Status: local/R2 adapters and direct browser uploads are implemented. Local
filesystem storage remains the default. Live R2 connectivity and synthetic
retained-object recovery passed; browser CORS and coordinated DB/media recovery
are still unverified. Production is live; the Vercel entrypoint enforces
fail-closed startup.
Startup alone does not establish authenticated media or coordinated recovery acceptance.

This bucket contains live photos and videos. Keep it separate from any existing
backup buckets or encrypted archives; those remain recovery material.

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
Production variable changes take effect in a new deployment. Use Vercel's
environment variable editor for keys; do not send them in chat, commit them, or put them in
command-line arguments. Credentials for existing backup archives remain separate
and are not used by the application.

The only setup information needed in chat is the bucket name, S3 endpoint,
production website origin, and whether the credentials are saved in Vercel.

Production launch and public HTTP checks passed on 16 October; see the
[launch record](operations/2026-10-16-launch-cleanup.md). Actual browser uploads,
CORS and coordinated recovery remain separate verification tasks.

## Application implementation

The existing product limits remain 10 MiB for images and 100 MiB for videos.
Vercel documents a 4.5 MB Function payload limit, including for its container
runtime's Function limits. Production uses direct browser uploads to R2; the
local multipart path cannot serve as the production large-file upload path.

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
The old filesystem/restic backup job has been removed. Retained copies are not
an independent backup; bucket retention alone is not demonstrated disaster recovery.

Existing archives stay private and intact. After the integration is tested,
prepare a controlled media import preserving storage keys; compare counts,
sizes, SHA-256 values and the database/media audit before changing live reads.
Source media are not deleted by the import. A database restore must be rehearsed
with the corresponding retained media.

## Verification after runtime or media changes

- Owner isolation and CSRF on ticket creation/finalization; unsigned bucket reads fail.
- Image/video size limits, content validation, corrupt files and forged metadata.
- Expired/foreign/replayed tickets, overwritten staging objects and partial uploads.
- Dog media CRUD, thumbnails/profile media and concurrent treatment-photo replacement.
- Download ranges, signed URL expiry, refresh and private cache behavior.
- Delete/replace recovery across the accepted six-hour database window.
- `bin/check`, the isolated PostgreSQL job in [CI.md](CI.md), and the current image build.
- A bounded R2 connectivity check with synthetic objects before private media migration.

Historical local and hosted synthetic verification from 15 October is preserved
in the [R2 verification record](operations/2026-10-15-r2-verification-history.md).
The temporary diagnostic deployment mode and standalone synthetic-check helper
have been removed; that record describes past verification only.

## Sources

- [Create buckets](https://developers.cloudflare.com/r2/buckets/create-buckets/)
- [R2 credentials and permissions](https://developers.cloudflare.com/r2/api/tokens/)
- [CORS configuration](https://developers.cloudflare.com/r2/buckets/cors/)
- [PHP S3 SDK configuration](https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/)
- [Object lifecycles](https://developers.cloudflare.com/r2/buckets/object-lifecycles/)
- [R2 pricing](https://developers.cloudflare.com/r2/pricing/)
- [Vercel Function limits](https://vercel.com/docs/functions/limitations)
- [Vercel container runtime](https://vercel.com/docs/functions/runtimes/container)
