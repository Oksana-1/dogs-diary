# Dogs Diary

Dogs Diary is a small Symfony 7.4 LTS application for keeping dog profiles, treatment history, and related photos or videos.

The UI is server-rendered with Twig and enhanced by Vue 3 islands. Data is exposed through a JSON API and persisted in PostgreSQL with Doctrine ORM.

## Features

- Create, view, update, and delete dog profiles.
- Record treatments and their due dates, notes, and treatment types.
- Upload images and videos to a dog's media library.
- Choose separate thumbnail and profile media for each dog.
- Attach up to five images to each treatment.

## Technology

- PHP 8.2+
- Symfony 7.4 LTS
- Twig
- Vue 3 through AssetMapper and ImportMap (no Node build step)
- Doctrine ORM and Doctrine Migrations
- PostgreSQL 16 through Docker Compose

## Local Development

### Prerequisites

- PHP 8.2 or newer with Composer
- Docker with Docker Compose
- Symfony CLI for the documented development server command

### Setup

1. Install the PHP dependencies:

   ```bash
   composer install
   ```

2. Start PostgreSQL:

   ```bash
   docker compose up -d
   ```

3. If necessary, override `DATABASE_URL` in `.env.local`. The committed default connects to the Docker Compose database on `127.0.0.1:5432`.

4. Create the database and apply the schema:

   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. Start the application:

   ```bash
   symfony serve
   ```

AssetMapper serves the development assets directly. A Node.js install or frontend bundler is not required.

## Application Structure

- `src/Controller/Web` contains the Twig page entrypoints.
- `src/Controller/Api` contains the dog, treatment, and media JSON APIs. Request DTOs live in its `Dto` directory.
- `src/Application` contains the dog, treatment, and media use cases.
- `src/Entity` and `src/Repository` contain the Doctrine model and persistence layer for dogs, treatments, dog media, and treatment media.
- `src/View` normalizes entities for API responses.
- `src/Infrastructure/Media` stores uploaded media on the local filesystem.
- `assets/js/modules/dogsDiary` contains the Vue UI, composables, entities, and API repositories.
- `templates` contains the shared Twig layout and Vue mount points.
- `migrations` contains the PostgreSQL schema history.

The `/` and `/dog/{id}` routes render lightweight Twig shells. `assets/app.js` mounts the matching `AppDogList` or `AppDogDetail` Vue island, and the island loads and updates data through the JSON API. Vue is the only frontend interaction layer; Stimulus is not installed.

## Routes

### Web

- `GET /` — dogs list and dog creation.
- `GET /dog/{id}` — dog details, editing, treatments, and media management.

### Dogs

- `GET /api/dogs` — list dogs.
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
- `POST /api/dogs/{dogId}/media` — upload media using the multipart field `file`.
- `DELETE /api/dogs/{dogId}/media/{id}` — delete a media item.
- `PUT /api/dogs/{dogId}/media/thumbnail` — select an image thumbnail using `{"mediaId": 123}`.
- `DELETE /api/dogs/{dogId}/media/thumbnail` — clear the selected thumbnail.
- `PUT /api/dogs/{dogId}/media/profile` — select image or video profile media using `{"mediaId": 123}`.
- `DELETE /api/dogs/{dogId}/media/profile` — clear the selected profile media.

Dog media supports JPEG, PNG, and WebP images up to 10 MB, plus MP4 and WebM videos up to 100 MB. Only images can be selected as thumbnails.

### Treatment Media

- `GET /api/dogs/{dogId}/treatments/{treatmentId}/media` — list a treatment's images.
- `POST /api/dogs/{dogId}/treatments/{treatmentId}/media` — upload an image using the multipart field `file`.
- `DELETE /api/dogs/{dogId}/treatments/{treatmentId}/media/{id}` — delete a treatment image.

Treatment media supports JPEG, PNG, and WebP images up to 10 MB, with a maximum of five images per treatment.

## Media Storage

Uploaded files are stored under `public/uploads` and exposed at `/uploads`. Database records keep media metadata and the generated storage key; deleting a dog or treatment cascades to its media records and stored files through the application services.

Audit the database and filesystem without changing either:

```bash
php bin/console app:media:audit
```

Add `--delete-orphans` only when orphaned files should be permanently removed.

## Development Commands

Check PHP formatting without modifying files:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Apply PHP formatting:

```bash
vendor/bin/php-cs-fixer fix
```

Validate Doctrine mappings:

```bash
php bin/console doctrine:schema:validate
```

Compile assets for deployment:

```bash
php bin/console asset-map:compile
```

The repository does not currently include an automated test suite.
