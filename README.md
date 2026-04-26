# Dogs Diary

Small Symfony 7.3 app for tracking dogs and their treatments.

## Stack

- PHP 8.2+
- Symfony 7.3
- Twig (server-rendered pages)
- Doctrine ORM + Doctrine Migrations
- PostgreSQL (via Docker Compose)
- AssetMapper (no Webpack/Vite)

## Project Structure

- `src/Controller` - Web and API controllers.
- `src/Controller/Api/Dto` - HTTP request payload DTOs for API endpoints.
- `src/Application/Dog` - Application service (`DogService`) for dog use-cases.
- `src/Entity` - Doctrine entities (`Dog`, `Treatment`).
- `src/Repository` - Doctrine repositories and legacy in-memory `DogsRepository`.
- `templates` - Twig templates for HTML pages.
- `assets` - AssetMapper JS/CSS entrypoints.
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
- `GET /dogs/{id}` - Dog details page.

### API

- `GET /api/dogs` - List dogs.
- `GET /api/dogs/{id}` - Get dog by id.
- `POST /api/dogs` - Create dog.
- `PUT /api/dogs/{id}` - Update dog.
- `DELETE /api/dogs/{id}` - Delete dog.

## Current Architecture Notes

- There are two dog models in the codebase:
  - `App\Model\Dog` (used by legacy in-memory repository path).
  - `App\Entity\Dog` (Doctrine persistence model used by `DogService`).
- API controller is thin and delegates CRUD to `App\Application\Dog\DogService`.
- API payload validation is handled by:
  - `App\Controller\Api\Dto\CreateDogPayload`
  - `App\Controller\Api\Dto\UpdateDogPayload`
- `Treatment` persistence exists as Doctrine entity and is being connected to `Dog`.
- If you work on persistence, prefer Doctrine entities/repositories over the legacy in-memory repository.

## Useful Commands

Run code style checks:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Auto-fix style:

```bash
vendor/bin/php-cs-fixer fix
```
