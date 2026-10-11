# Railway production deployment

This is the operator runbook for the first Dogs Diary production deployment.
Complete the steps in order and record the deployment ID, release tag, backup
timestamp, and smoke-test result in the release notes.

## 1. Release gate

- Deploy only from `main` after both required GitHub checks pass:
  `Tests and static checks` and `PostgreSQL migrations and schema`.
- Enable Railway's **Wait for CI** setting on the GitHub deployment trigger.
- Confirm the release tag resolves to the commit being deployed.
- Confirm no uncommitted application changes are being treated as part of the
  release.

For the first release, the known-good application tree is tagged `v1.0.0`.

## 2. Create Railway services

1. Create a Railway project and production environment.
2. Add a PostgreSQL service. Keep it private; the application does not need its
   public TCP proxy for normal operation.
3. Add the GitHub repository as the application service, select branch `main`,
   and verify Railway detects the root `Dockerfile`.
4. Generate a public HTTPS domain for the application.
5. Attach an application Volume at exactly `/app/var/uploads` before accepting
   uploads. Keep the application at one replica while it uses local media
   storage.

## 3. Application variables

Set these variables on the application service. If the database service is not
named `Postgres`, substitute its actual Railway service name in the reference.

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<unique-random-value-with-at-least-32-characters>
DATABASE_URL=${{Postgres.DATABASE_URL}}
MAILER_DSN=<real-production-mailer-dsn>
MAILER_FROM_ADDRESS=<verified-sender-address>
MAILER_FROM_NAME=Dogs Diary
APP_BASE_URL=https://<generated-or-custom-domain>
```

Generate `APP_SECRET` locally with:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Railway Free, Trial, and Hobby plans do not provide outbound SMTP. This project
therefore uses the Resend HTTPS API transport in production:

```dotenv
MAILER_DSN=resend+api://<resend-api-key>@default
MAILER_FROM_ADDRESS=no-reply@<verified-domain>
```

Do not store the bare `re_...` API key in `MAILER_DSN`; without the
`resend+api://` scheme Symfony cannot construct the mail transport.

Verify the sender domain in Resend before deploying, and store the DSN only as
a sealed Railway service variable. Do not commit the API key.

Seal `APP_SECRET`, `DATABASE_URL`, and `MAILER_DSN` after configuration. Do not
set `PORT`; Railway injects it and the Caddy configuration already consumes it.
The image entrypoint runs this check automatically before starting FrankenPHP:

```bash
php bin/console app:production:check --no-debug
```

## 4. Deployment settings

Configure the application service with:

| Setting | Value |
|---|---|
| Builder | Dockerfile |
| Pre-deploy command | `php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration` |
| Pre-deploy timeout | `300` seconds |
| Healthcheck path | `/healthz` |
| Healthcheck timeout | `300` seconds |
| Restart policy | On Failure, maximum 10 retries |
| Replicas | `1` |

The pre-deploy step runs in a separate container with environment variables and
private networking, but without the application Volume. Keep it as the single
migration command shown above; the image entrypoint performs the production
configuration check separately. A non-zero exit blocks the release. Migrations
must therefore remain database-only; file changes belong in the normal
application process or a separately supervised maintenance task.

The healthcheck accepts any `2xx`; `/healthz` returns `204`. Railway uses it only
to gate a new deployment, not as continuous uptime monitoring. Because the app
has a Volume, expect a short downtime while Railway moves the mount between
deployments.

## 5. Migration and backup policy

The optional offsite backup implementation uses private Cloudflare R2 storage.
See [R2 backup setup and restore verification](R2_BACKUPS.md) for configuration,
retention, scheduling, and activation. Its production success must be recorded;
do not infer it from the presence of scripts or environment variables.

The current four migrations have been tested from an empty PostgreSQL 16
database in CI. Their forward path creates the baseline schema, adds users and
password-reset storage, and tightens treatment-media cardinality. The tightening
migration aborts safely if legacy data has more than one photo per treatment.

For every production Volume, open the owning service's **Backups** tab and
enable both schedules:

| Schedule | Railway retention |
|---|---|
| Daily | 6 days |
| Weekly | 27 days |

The PostgreSQL data Volume and the application Volume mounted at
`/app/var/uploads` are separate backup targets; configuring one does not protect
the other. Trigger and record a manual backup for each Volume before the first
restore drill. Railway currently limits a manual backup to 50% of the Volume's
capacity, and wiping a Volume also deletes its snapshots. Keep portable offsite
copies for recovery from project or Volume loss.

For the initial empty database:

1. Enable scheduled backups on the PostgreSQL Volume before production data is
   created. Daily plus weekly retention is the minimum target.
2. Enable scheduled backups on the `/app/var/uploads` Volume as well.
3. Let the pre-deploy command apply the complete migration chain.
4. Check deployment logs for a successful migration and startup preflight.

For every later migration:

1. Review generated SQL and classify it as additive or destructive.
2. Exercise the complete chain against a clean PostgreSQL 16 database in CI.
3. Prefer expand/contract changes that remain compatible with the previous app.
4. Before a destructive migration, create a manual PostgreSQL Volume backup and
   a portable `pg_dump --format=custom --no-owner` backup; record both timestamps.
5. Do not automatically execute `doctrine:migrations:migrate prev` during an app
   rollback. Restore data only through an explicit, verified recovery decision.

Enable PostgreSQL point-in-time recovery when the production recovery objective
requires restoration between scheduled snapshots. Test a logical dump restore
into a scratch database before relying on it.

### Non-production restore drill

Do not click **Restore** on a production Volume merely to test it. Railway stages
a replacement Volume at the original mount path and redeploys the service after
the change is applied. Use a disposable environment or service for a snapshot
restore. Use portable copies to verify the data without replacing production:

1. Record the source backup timestamps and start time. Never print connection
   strings or store them in the repository.
2. Create an encrypted offsite custom-format dump with PostgreSQL's
   `pg_dump --format=custom --no-owner` and download or copy the uploads tree
   while it is in a consistent state.
3. Create an empty scratch PostgreSQL database and scratch uploads Volume. They
   must not be referenced by the production application.
4. Restore the dump with `pg_restore --no-owner --exit-on-error` and restore the
   uploads copy to `/app/var/uploads` on the scratch service.
5. Point a disposable application deployment at the scratch resources, then
   run:

   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
   php bin/console doctrine:schema:validate
   php bin/console app:media:audit --no-interaction --no-debug
   ```

   Do not pass `--delete-orphans` during a drill or scheduled audit.
6. Compare source and restored counts for `app_user`, `dog`, `treatment`,
   `dog_media`, and `treatment_media`. Inspect several highest-ID records and
   confirm their referenced image/video files can be downloaded from the
   disposable application.
7. Record the finish time, duration, counts, migration/schema result, media
   audit result, and operator. Delete scratch resources only after the record is
   complete; retain the offsite copies according to the agreed retention policy.

### Backup and restore record

Copy this table into the release notes for every drill:

| Field | Recorded value |
|---|---|
| UTC date and operator | |
| Application commit/tag | |
| PostgreSQL manual snapshot | |
| Uploads manual snapshot | |
| Offsite database dump | |
| Offsite uploads copy | |
| Scratch database/Volume | |
| users / dogs / treatments | |
| dog media / treatment media | |
| Migration and schema validation | |
| Media audit and sampled files | |
| Total restore duration | |
| Result and follow-up | |

## 6. Continuous monitoring and media audit

Railway calls `/healthz` while bringing up a deployment, but does not use that
healthcheck for continuous uptime monitoring. Configure an external monitor
against the production HTTPS URL with these minimum settings:

- `GET https://<production-domain>/healthz` every 5 minutes;
- accept only a `2xx` response (the application normally returns `204`);
- alert after two consecutive failures and notify again on recovery;
- route alerts to a channel that is checked outside Railway;
- keep at least 30 days of incident history.

Run the media audit daily as a short-lived scheduled task that exits after the
command completes:

```bash
php bin/console app:media:audit --no-interaction --no-debug
```

The task needs the production database and the same uploads filesystem. Do not
create a Railway cron service that can see only the database: that would report
every media row as missing. If the uploads Volume cannot be safely mounted by a
separate scheduled service, use a trusted external scheduler to run the command
inside the active application deployment through `railway ssh`, or defer
automation and record a daily manual run. Treat a non-zero exit as an alert.
Never schedule `--delete-orphans`.

Record the monitor URL, interval, alert destination, audit schedule, latest
successful run, and owner in the release notes. Do not mark monitoring complete
until a deliberate test failure has delivered an alert and a recovery notice.

## 7. First-deploy smoke test

### Platform and transport

- [ ] Deployment build, pre-deploy, and runtime logs contain no errors or secret
      values.
- [ ] `GET /healthz` returns `204` over the Railway domain.
- [ ] HTTP redirects to HTTPS and the certificate is valid.
- [ ] A normal HTTPS response contains `Strict-Transport-Security`,
      `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and
      `Permissions-Policy`.
- [ ] An unknown web path shows the custom 404 page without debug information.
- [ ] An unknown authenticated API path returns the JSON error envelope.

### Authentication and isolation

- [ ] Register user A, log out, reject an invalid login, and log back in.
- [ ] Request a password reset and confirm the production email arrives with an
      HTTPS link on the Railway domain.
- [ ] Confirm an invalid logout CSRF token is rejected with `403`.
- [ ] Register user B and confirm user B cannot see user A's dogs, treatments,
      or media; direct foreign URLs must return `404`.

### Core data and uploads

- [ ] As user A, create, edit, and delete a temporary dog.
- [ ] Create, edit, and delete a treatment on a retained dog.
- [ ] Upload an image, select it as profile and thumbnail, then download it.
- [ ] Upload a video and confirm playback/range requests work.
- [ ] Confirm unsupported and oversized uploads are rejected without a `500`.
- [ ] Record one retained dog's ID, treatment ID, and media URLs.

### Persistence and rollback drill

1. Redeploy/restart the application with the same database and upload Volume.
2. Confirm the retained dog, treatment, image, and video still exist and load.
3. From deployment history, roll back to the first successful `v1.0.0`
   deployment and confirm `/healthz`, login, retained data, and media again.
4. Return to the intended deployment only after the rollback check passes.

Application rollback means selecting the previous healthy Railway deployment or
deploying the release tag's commit. It does not roll back PostgreSQL or uploaded
files. If a schema change is incompatible with the old application, fix forward
or explicitly restore a verified backup rather than guessing.

## 8. Go/no-go record

Do not enable production traffic unless every item below is known:

- Release tag and full commit SHA
- Successful GitHub check URLs
- Railway deployment ID and public HTTPS origin
- PostgreSQL and uploads Volume backup status
- Migration result
- Smoke-test operator and timestamp
- Rollback target and completed rollback drill

Railway references used for this runbook:

- <https://docs.railway.com/deployments/pre-deploy-command>
- <https://docs.railway.com/deployments/healthchecks>
- <https://docs.railway.com/deployments/github-autodeploys>
- <https://docs.railway.com/databases/postgresql>
- <https://docs.railway.com/guides/postgres-backups-restores>
- <https://docs.railway.com/volumes/backups>
- <https://docs.railway.com/deployments/healthchecks>
- <https://docs.railway.com/cron-jobs>
- <https://docs.railway.com/cli/ssh>
- <https://docs.railway.com/guides/roll-back-bad-deploy>
