# Dogs Diary

Small Symfony 7.3 app for tracking dogs and their treatments.

## Stack

- PHP 8.2+
- Symfony 7.3
- Twig (server-rendered pages)
- Vue 3 islands for interactive features
- Doctrine ORM + Doctrine Migrations
- PostgreSQL (via Docker Compose)
- AssetMapper (no Webpack/Vite)

## Project Structure

- `src/Controller` - Web and API controllers.
- `src/Controller/Api/Dto` - HTTP request payload DTOs for API endpoints.
- `src/Application/Dog` - Application service (`DogService`) for dog use-cases.
- `src/Entity` - Doctrine entities (`Dog`, `Treatment`).
- `src/Repository` - Doctrine repositories.
- `templates` - Twig page shells and shared server-rendered layout.
- `assets` - AssetMapper JS/CSS entrypoints and Vue islands.
- `migrations` - Doctrine migration files.

## Local Run

1. Install dependencies:
   ```bash
   composer install
   ```
2. Start PostgreSQL:
   ```bash
   docker compose up -d
   ```
3. Ensure `DATABASE_URL` in `.env` points to your local DB.
4. Run migrations:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
5. Start Symfony server:
   ```bash
   symfony serve
   ```

## Main Routes

### Web

- `GET /` - Homepage with dogs list.
- `GET /dog/{id}` - Dog details page.

### API

- `GET /api/dogs` - List dogs.
- `GET /api/dogs/{id}` - Get dog by id.
- `POST /api/dogs` - Create dog.
- `PUT /api/dogs/{id}` - Update dog.
- `DELETE /api/dogs/{id}` - Delete dog.
- `GET /api/dogs/{dogId}/treatments` - List a dog's treatments.
- `GET /api/dogs/{dogId}/treatments/{id}` - Get a treatment.
- `POST /api/dogs/{dogId}/treatments` - Create a treatment.
- `PUT /api/dogs/{dogId}/treatments/{id}` - Update a treatment.
- `DELETE /api/dogs/{dogId}/treatments/{id}` - Delete a treatment.

## Current Architecture Notes

- Dog persistence uses `App\Entity\Dog` with Doctrine and `App\Application\Dog\DogService`.
- API controller is thin and delegates CRUD to `App\Application\Dog\DogService`.
- API payload validation is handled by:
  - `App\Controller\Api\Dto\CreateDogPayload`
  - `App\Controller\Api\Dto\UpdateDogPayload`
- Treatment CRUD is exposed through the nested dog API and owned by the `DogDetail` Vue island.
- The dog detail Twig template only provides the `DogDetail` island mount point; Vue owns its interactive DOM.
- Vue is the only frontend interaction layer; Stimulus is not installed.
- Dog data is Doctrine-backed; there is no legacy in-memory dog repository in this checkout.

## Useful Commands

Run code style checks:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Auto-fix style:

```bash
vendor/bin/php-cs-fixer fix
```

Reassemble assets
```bash
php bin/console asset-map:compile
```
