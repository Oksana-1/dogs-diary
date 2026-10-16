# Dogs Diary — production follow-up and cleanup plan

> Updated 16 October 2026: production is live at https://dogsdiary.app.
> The user confirmed a successful launch; independent public HTTP checks passed.
> Current work is local cleanup and the remaining production acceptance checks.
> Earlier schedules below are historical; do not repeat imports or create databases.

## Today — 16 October

- [x] Confirm the current production deployment and assigned domains through
  Vercel CLI: `dpl_F5bkwDfjFmp5MAQECZ62fmuLbPh3`, `READY / PROMOTED`,
  `main` commit `f2ef743895a4c22e85f7fc296601809c4e866592`.
- [x] Check https://dogsdiary.app: `/healthz` 204, `/login` 200, six sampled
  CSS/JavaScript assets 200, `/api/dogs` 401 without authentication, `/` 302
  to `/login`. Login includes CSRF; session cookies are Secure, HttpOnly and
  SameSite=Lax. HTML/API responses use private cache headers; HSTS is present.
- [x] Inventory local deployment/setup files and begin the user-requested cleanup.
- [x] Remove the R2-only diagnostic deployment mode, archive the remaining one-off
  `.vercel/` helpers, and separate historical plans/reports from active instructions.
- [x] Remove locally the retired filesystem/restic backup mechanism, its HTTP write lock,
  related tests, Docker packages and activation instructions. Keep the Python
  Vercel entrypoint tests and cross-instance security smoke.
  See [backup retirement](docs/operations/2026-10-16-retire-backup.md).
  This source change has not been committed, pushed or deployed.
- [ ] Confirm required credentials are retrievable from the password manager
  before removing local secret copies.
- [ ] Finish authenticated production checks: CRUD, media upload/read/video,
  owner isolation, sessions and actual mail/reset delivery.
- [ ] Establish existing-media import/audit evidence and coordinated DB/media
  recovery evidence. A successful launch alone does not establish either.

See [launch verification and cleanup](docs/operations/2026-10-16-launch-cleanup.md).

## Current verified state

- Production is now live on https://dogsdiary.app; `www.dogsdiary.app` and
  `dogs-diary.vercel.app` are also assigned to the promoted deployment. All
  three domains are verified in Vercel; HTTP checks above cover the apex only.
  Today's release came from Git, superseding the earlier deployment-pending
  status. This is startup evidence, not authenticated UI/media/recovery acceptance.
- A protected Vercel Preview was deployed successfully:
  https://dogs-diary-d7tlsowag-4438488-gmailcoms-projects.vercel.app.
  Deployment `dpl_GrZNKZyWdy4gjAZ4rLFLrZXWs2jj` reached READY; health returned
  204, assets/login returned 200, and login CSRF/secure cookie flags were checked.
  This is startup evidence, not a full production CRUD/media/recovery acceptance.
- The Vercel entrypoint supports fail-closed Production startup; only isolated
  Preview startup disables mail. The retired R2-only diagnostic deployment mode
  has been removed locally; `php ops/r2_check.php` remains an opt-in manual check.
- Local filesystem remains the default; the R2 adapter, private signed reads,
  owner-scoped upload tickets and direct browser staging uploads are implemented.
  Hosted synthetic R2 upload/read/range/retention/restore/cleanup passed using
  saved Vercel secrets. Browser behavior and coordinated DB recovery remain unverified.
- Provider-specific application references and the obsolete deployment runbook were removed.
- R2 quality checks, the disposable PostgreSQL migration/schema/baseline job and
  Vercel image build passed locally. See `docs/R2_MEDIA.md` for remaining live
  verification and storage-cost/recovery caveats.
- Subsequent local verification on 15 October used the existing development DB:
  R2 migration, schema validation and media audit passed. Two Composer audit
  findings were then fixed with approved `http-foundation`/`yaml` 7.4.21 patches;
  the full `bin/check --local` gate and eight migration scenarios pass again.
  The user also confirmed manual local behavior before the patch update. See
  [local verification](docs/operations/2026-10-15-local-verification.md).
- With explicit user approval, the R2 upload-ticket migration was applied to
  the existing Neon `main` / `neondb` through the administrative direct TLS
  connection. Schema validation passed, existing-table counts and Preview object
  metadata matched, and the restricted runtime role's grants passed through
  direct and pooled connections. This did not deploy the application; see
  [hosted migration](docs/operations/2026-10-15-neon-r2-migration.md).
- The updated local `linux/amd64` Vercel image was built with both patched
  packages and the matching dependency lock. Offline image/startup checks passed,
  including private-file exclusions and synthetic Production cache warmup.
  Those checks describe the local image; today's Git-built deployment is a
  separate artifact, and image identity has not been compared. See
  [image verification](docs/operations/2026-10-15-vercel-image.md).

## Retained decisions

- Keep Symfony/FrankenPHP, Doctrine, Twig and Vue islands.
- Use the existing Vercel project, existing Neon database, and private Cloudflare
  R2 media storage. Do not introduce a replacement framework or another permanent DB.
- Keep the existing local development database, users and original archives.
  Local email stays in Mailpit; automatic hosted/local synchronization remains on hold.
- Keep live media separate from the restic backup bucket.
- Preserve product limits: images 10 MiB, videos 100 MiB; treatments accept images only.
- Database recovery requirement remains six hours. Deleted/replaced media must
  remain recoverable for at least that period after removal.

## 1. R2 setup — user

- [ ] Verify the existing live-media bucket is private Standard storage.
- [ ] Verify existing Object Read & Write credentials are bucket-scoped and
  retrievable from the password manager; production credentials are already in use.
- [x] Verify that all five `MEDIA_R2_*` names exist in Vercel Production and both
  key variables are sensitive (metadata only; values/access remain unverified).
- [x] Record the production HTTPS origin: `https://dogsdiary.app`.
- [ ] Record the exact S3 endpoint without credentials.
- [ ] Apply origin-specific CORS from [R2_MEDIA.md](docs/R2_MEDIA.md).
- [ ] Review actual lifecycle policies against the implemented staging/retention prefixes.

The setup guide includes the implemented `MEDIA_R2_*` variable names. Credentials
alone do not establish private-bucket policy or browser CORS behavior. The
production HTTPS origin is now `https://dogsdiary.app`; the remaining R2 setup
items need configuration or behavioral evidence before they can be checked off.
Only bucket name, endpoint and origin belong in chat.

## 2. R2 integration — implementation

- [x] Introduce an R2 adapter while retaining local filesystem storage for development.
- [x] Replace the filesystem-only `resolvePath()` download dependency with an
  abstraction supporting owner-authorized, short-lived signed downloads.
- [x] Add authenticated, CSRF-protected upload initiation/finalization and expiring
  owner/target-bound tickets. Upload bytes directly to R2 staging from the browser.
  Vercel documents a 4.5 MB Function payload limit; the old upload proxy cannot
  preserve our image/video limits.
- [x] Validate actual size, MIME, image/container signatures and sanitized names
  on finalization. Store validated bytes under immutable canonical keys;
  an old staging PUT URL must not overwrite accepted media.
- [x] Define replay/idempotency, interrupted uploads, database compensation,
  upload quotas and cleanup of abandoned staging objects.
- [x] Preserve dog media CRUD, thumbnail/profile selection, owner isolation and
  concurrent treatment-photo replacement. Update both Vue media repositories.
- [x] Adapt media audit to paginated remote keys and protect retained/staging
  objects from ordinary orphan deletion.
- [x] Implement delete/replace retention and a media recovery procedure matching
  the database recovery window before enabling expiration rules.
- [x] Rehearse retained-object restoration on synthetic R2 objects.
- [ ] Review staging-cost exposure before enabling lifecycle rules or opening
  public registration; coordinated DB/media recovery remains separate.

Acceptance tests cover valid/invalid files, 10/100 MiB boundaries, foreign and
expired tickets, replay, staging overwrites, replacement concurrency, video ranges,
URL expiry, database failures, and deletion/recovery. Frontend tests follow
`tests/JavaScript/TESTING.md`.

## 3. Production runtime — implementation and configuration

- [x] Replace the Preview-only entrypoint with production startup and tests;
  remove obsolete Preview-specific rules only after their production replacements.
- [ ] Read real mail settings, stable Production `APP_SECRET`, canonical database
  URL with TLS, explicit `APP_BASE_URL`, `PORT=8080` and verified R2 configuration.
- [x] Remove the old filesystem backup scheduler. Production recovery still
  requires the chosen database recovery and media retention procedures.
- [x] Validate configuration before serving traffic. Run schema migrations
  separately through the direct connection; never on each runtime startup.
- [x] Verify secure session cookie flags and private cache headers on public
  login/API responses (16 October HTTP checks).
- [ ] Verify production mail/reset links, forwarded headers and authenticated
  private cache behavior. Local development continues to use its own data.

## 4. Verification, migration and release

- [x] Run `bin/check`, isolated PostgreSQL migration/schema/baseline checks from
  [CI.md](docs/CI.md), and the final Vercel image build.
- [x] Exercise R2 with bounded synthetic objects before importing private media.
- [x] Verify cross-instance runtime security using new disposable containers;
  restore and verify the original project alias after the diagnostic deployment.
- [ ] Prepare a controlled import preserving existing storage keys. Verify counts,
  sizes, SHA-256 and read-only media audit; preserve source files and archives.
- [ ] Verify database recovery settings and rehearse restoring the database with
  the corresponding retained photos/videos.
- [ ] Test authenticated CRUD/media behavior, ownership boundaries, sessions
  across instances/restarts, large files and video playback on the hosted runtime.
- [x] Confirm production deployment and domain assignment (16 October).
- [ ] Complete the rollback/recovery procedure and record final migration/import
  evidence. Further live data changes, deployments and DNS changes require
  authorization for those actions.

Production acceptance requires durable media, working email/authentication,
passing checks and a demonstrated DB/media recovery path. An unrun check is a
verification gap; the existing Preview startup smoke does not satisfy this gate.

## 5. Local cleanup — authorized and started 16 October

Requested on 15 October and explicitly started by the user on 16 October after
launch confirmation. Remove obsolete local material recoverably while keeping
credentials, original archives and operational tooling needed for the remaining
acceptance and rollback/recovery checks.

- [x] Inventory temporary credentials, setup/diagnostic scripts, downloaded env
  files, build artifacts and logs; identify anything still needed for operations.
- [ ] Save required Neon administrative/runtime credentials and other secrets in
  the password manager and verify they can be retrieved before removing local
  copies, including `.vercel/neon-admin.local` and `.vercel/production-role.local`.
- [x] Move obsolete one-off setup/diagnostic files and generated build/bootstrap
  artifacts out of the workspace; record a private recovery location.
- [ ] Remove obsolete local secret copies only after password-manager retrieval
  is confirmed. Retain Vercel project linkage and reusable operational tooling.
- [ ] Review temporary provider credentials and resources for revocation/removal
  only after checking active dependencies and authorizing the exact targets.
  Do not rotate active Production or Preview credentials as incidental cleanup.
- [ ] Keep original database/media archives and required recovery material.
  Record what was removed and verify local development and deployment tooling
  still work afterward. Never delete `.vercel/` wholesale.

Cleanup does not imply production acceptance. No provider credential revocation,
remote resource removal or Docker image/volume pruning is part of this pass.

## References and history

- [R2 media setup and implementation criteria](docs/R2_MEDIA.md)
- [Current Vercel runtime](docs/STATELESS_RUNTIME.md)
- [Production environment requirements](docs/PRODUCTION_CONFIGURATION.md)
- [Migration and private archive rules](docs/MIGRATIONS.md)
- [Configured checks](docs/CI.md)
- Historical local restore and hosting evidence remains in `docs/operations/`.

Previous schedules and proposals are preserved in the
[plan archive](docs/operations/archived-plans.md). They are historical reference,
not current deployment or restore instructions.
