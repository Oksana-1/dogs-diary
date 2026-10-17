# Dogs Diary

Dogs Diary is a small Symfony 7.4 LTS application for keeping dog profiles, treatment history, and related photos or videos.

The UI is server-rendered with Twig and enhanced by Vue 3 islands. Data is exposed through a JSON API and persisted in PostgreSQL with Doctrine ORM.

## Features

- Create, view, update, and delete dog profiles.
- Record treatments and their due dates, notes, and treatment types.
- Upload images and videos to a dog's media library.
- Choose separate thumbnail and profile media for each dog.
- Attach one photo to each treatment and view it from the treatment table.

## Technology

- PHP 8.2+
- Symfony 7.4 LTS
- Twig
- Vue 3 through AssetMapper and ImportMap (no Node build step)
- Doctrine ORM and Doctrine Migrations
- PostgreSQL 18.6 through Docker Compose (matching the hosted database)

## Local Development

### Prerequisites

- Docker with Docker Compose

### Setup

The local stack uses one application database, `dogs_diary_dev_20261012`,
restored from the Monday archive in `~/DogsDiaryBackups/2026-10-12`.
Compose and host Symfony commands target that same database. PostgreSQL's
built-in `postgres` and template databases are not application copies.
Uploads use the existing external volume `dogs_diary_dev_20261012_var`.

These instructions resume the restored stack on this machine. A fresh machine
must restore the private DB/uploads archive and create that uploads volume
before starting the app; starting an empty PostgreSQL volume does not import
the backup automatically. Keep the archive private and never use it in Preview.

The local copy preserves the production users and password hashes from Monday's
backup: use the same application email/password recorded at that backup date.
Email delivery stays in local Mailpit. Automatic synchronization is on hold;
local changes are not sent to the hosted database.

1. Build and start the application, PostgreSQL 18.6, and Mailpit:

   ```bash
   docker compose up --build
   ```

   The application is available at <http://localhost:8080>. Mailpit's local
   inbox is available at <http://localhost:18025> (avoids the other project's Mailhog).

2. Run Symfony or Composer commands inside the application container:

   ```bash
   docker compose exec app php bin/console about
   docker compose exec app composer require vendor/package
   ```

3. Stop the stack when finished:

   ```bash
   docker compose down
   ```

The container installs the locked Composer dependencies and applies pending
Doctrine migrations on startup. Project sources are bind-mounted, so PHP, Twig,
and JavaScript changes are available without rebuilding the image. The named
volumes preserve PostgreSQL data, uploads, and container-managed dependencies.
Use `docker compose down` without `--volumes` to retain data. The external
uploads volume is deliberately protected from Compose volume removal, but
`down --volumes` would still delete the database and dependency volumes.

The migration history is squashed into `Version20261013180000`. It creates a
fresh schema or adopts Monday's four-version backup / the old five-version
schema without replacing application data. Normal migration startup handles
the conversion; never manually mark the baseline as executed. See
[`docs/MIGRATIONS.md`](docs/MIGRATIONS.md) for restore compatibility and checks.
Do not use schema reset commands for cleanup.
Tests continue to use isolated in-memory SQLite, never this development data.

The PostgreSQL 18 data volume is `dogs-diary_database_data_pg18`, mounted at
`/var/lib/postgresql` (the image uses version-specific `PGDATA`). A major upgrade
requires dump/restore into a fresh volume, not attaching a PostgreSQL 16 data
directory to the new image. See the [upgrade record](docs/operations/2026-10-14-hosting.md).

The development target inherits the production PHP 8.4, FrankenPHP, Caddy,
extensions, and PHP limits from the same `Dockerfile`. Development-only error
display and timestamp validation remain enabled locally. AssetMapper serves the
development assets directly; a Node.js install or frontend bundler is not
required for the application runtime.

## Application Structure

- `src/Controller/Web` contains the Twig page entrypoints.
- `src/Controller/Api` contains the dog, treatment, and media JSON APIs. Request DTOs live in its `Dto` directory.
- `src/Application` contains the dog, treatment, and media use cases.
- `src/Entity` and `src/Repository` contain the Doctrine model and persistence layer for dogs, treatments, dog media, and treatment media.
- `src/View` normalizes entities for API responses.
- `src/Infrastructure/Media` provides local filesystem and private R2 media storage.
- `assets/js/modules/dogsDiary` contains the Vue UI, composables, entities, and API repositories.
- `templates` contains the shared Twig layout and Vue mount points.
- `migrations` contains the PostgreSQL schema history.

The `/` and `/dog/{id}` routes render lightweight Twig shells. `assets/app.js` mounts the matching `AppDogList` or `AppDogDetail` Vue island, and the island loads and updates data through the JSON API. Vue is the only frontend interaction layer; Stimulus is not installed.

## Routes

### Web

- `GET /` — dogs list and dog creation.
- `GET /dog/{id}` — dog details, editing, treatments, and media management.

### Dogs

- `GET /api/dogs` — list dog summaries with card fields and selected thumbnails, without treatment history.
- `GET /api/dogs/{id}` — get one dog.
- `POST /api/dogs` — create a dog.
- `PUT /api/dogs/{id}` — update a dog.
- `DELETE /api/dogs/{id}` — delete a dog.

Dog create and update requests accept `name`, `birthDate`, `gender`, `adoptDate`, `status`, `weight`, and `height`. `name` and `birthDate` are required. Dates use `YYYY-MM-DD`; gender may be `male` or `female`.

### Treatments

- `GET /api/dogs/{dogId}/treatments` — list a dog's treatments.
- `GET /api/dogs/{dogId}/treatments/{id}` — get one treatment.
- `POST /api/dogs/{dogId}/treatments` — create a treatment.
- `PUT /api/dogs/{dogId}/treatments/{id}` — update a treatment.
- `DELETE /api/dogs/{dogId}/treatments/{id}` — delete a treatment.

Treatment create and update requests accept `types`, `productName`, `treatmentDate`, `dueDate`, and `note`. `types` must contain at least one of `flea_tick` or `anti_worm`; `productName` and `treatmentDate` are required.

### Dog Media

- `GET /api/dogs/{dogId}/media` — list a dog's media.
- `POST /api/dogs/{dogId}/media` — local-storage upload using the multipart field `file`.
- `POST /api/dogs/{dogId}/media/uploads` — initiate a direct R2 upload, or select local multipart mode.
- `POST /api/dogs/{dogId}/media/uploads/{uploadId}/complete` — validate and finalize a direct upload.
- `GET /api/dogs/{dogId}/media/{id}` — authorize and stream or redirect to private media.
- `DELETE /api/dogs/{dogId}/media/{id}` — delete a media item.
- `PUT /api/dogs/{dogId}/media/thumbnail` — select an image thumbnail using `{"mediaId": 123}`.
- `DELETE /api/dogs/{dogId}/media/thumbnail` — clear the selected thumbnail.
- `PUT /api/dogs/{dogId}/media/profile` — select image or video profile media using `{"mediaId": 123}`.
- `DELETE /api/dogs/{dogId}/media/profile` — clear the selected profile media.

Dog media supports JPEG, PNG, and WebP images up to 10 MB, plus MP4 and WebM videos up to 100 MB. Only images can be selected as thumbnails.

### Treatment Media

- `GET /api/dogs/{dogId}/treatments/{treatmentId}/media` — list a treatment's images.
- `POST /api/dogs/{dogId}/treatments/{treatmentId}/media` — local-storage image upload using the multipart field `file`.
- `POST /api/dogs/{dogId}/treatments/{treatmentId}/media/uploads` — initiate an image upload.
- `POST /api/dogs/{dogId}/treatments/{treatmentId}/media/uploads/{uploadId}/complete` — finalize a direct image upload.
- `GET /api/dogs/{dogId}/treatments/{treatmentId}/media/{id}` — stream an owner-authorized treatment image.
- `DELETE /api/dogs/{dogId}/treatments/{treatmentId}/media/{id}` — delete a treatment image.

Treatment media supports one JPEG, PNG, or WebP image up to 10 MB per treatment. Uploading another image replaces the existing treatment photo.

## Media Storage

Private R2 storage, direct browser uploads, retention and Cloudflare/Vercel setup
are documented in [`docs/R2_MEDIA.md`](docs/R2_MEDIA.md). Set `MEDIA_STORAGE=r2`
with the documented credentials to select R2. The default `local` driver uses
the filesystem behavior below. Production is live on Vercel with R2; authenticated
browser checks and coordinated recovery still need verification. This
feature does not migrate existing files automatically.

With local storage, uploaded files are stored under the private `var/uploads` directory. With R2, uploads go directly to private staging objects and are validated before finalization. API responses expose authenticated media routes rather than storage keys; local downloads are streamed and R2 downloads use short-lived signed URLs. Both paths enforce ownership and support video byte ranges. Database records keep media metadata and randomized storage keys; R2 deletion retains recoverable copies for the configured recovery window.

When upgrading an existing installation, move the contents of `public/uploads` to `var/uploads` before serving requests with the new configuration. The storage-key-relative directory structure must be preserved.

Audit the database and selected storage without changing either:

```bash
php bin/console app:media:audit
```

Add `--delete-orphans` only after reviewing the reported canonical keys. Local
files are permanently removed; R2 files are retained before canonical deletion.
Never use this option during a restore or an active import/upload operation.

## Development Commands

Production database/media recovery requirements are documented in
[`docs/STATELESS_RUNTIME.md`](docs/STATELESS_RUNTIME.md#recovery-boundary) and
[`docs/R2_MEDIA.md`](docs/R2_MEDIA.md). The old filesystem/restic backup scheduler
has been removed; coordinated hosted recovery still requires verification.

Run the local quality checks (PHP in the running app container, Node.js on the host):

```bash
bin/check
```

Use `bin/check php` or `bin/check frontend` for a focused run, or add `--local`
to use host PHP/Composer. See [`docs/CI.md`](docs/CI.md) for prerequisites,
failure behavior, and the separate PostgreSQL checks.

Production environment requirements and the fail-safe preflight command are
documented in [`docs/PRODUCTION_CONFIGURATION.md`](docs/PRODUCTION_CONFIGURATION.md).

The portable production image and persistent-volume runtime are documented in
[`docs/PRODUCTION_DOCKER.md`](docs/PRODUCTION_DOCKER.md).

Shared database sessions, login throttling, and Vercel Production/Preview
runtime are documented in [`docs/STATELESS_RUNTIME.md`](docs/STATELESS_RUNTIME.md).

The GitHub Actions quality gate and its exact local commands are documented in
[`docs/CI.md`](docs/CI.md).

Check PHP formatting without modifying files:

```bash
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

Apply PHP formatting:

```bash
docker compose exec app vendor/bin/php-cs-fixer fix
```

Validate Doctrine mappings:

```bash
docker compose exec app php bin/console doctrine:schema:validate
```

Compile assets for deployment:

```bash
docker compose exec app php bin/console asset-map:compile
```

Run the PHP test suite:

```bash
docker compose exec \
  -e DATABASE_URL='sqlite:///:memory:' \
  -e MAILER_DSN='null://null' \
  -e MAILER_FROM_ADDRESS='no-reply@dogs-diary.test' \
  -e APP_BASE_URL='http://localhost' \
  app php bin/phpunit
```

Run the frontend transport tests with Node.js:

```bash
node --test tests/JavaScript/FetchClient.test.mjs
```
