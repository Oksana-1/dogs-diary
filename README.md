# Dogs Diary

Small Symfony 7.4 LTS app for tracking dogs and their treatments.

## Stack

- PHP 8.2+
- Symfony 7.4 LTS
- Twig (server-rendered pages)
- Vue 3 apps for interactive pages
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
- `assets` - AssetMapper JS/CSS entrypoints and Vue apps.
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
- Treatment CRUD is exposed through the nested dog API and owned by the dog-detail Vue app.
- `assets/app.js` mounts `AppDogList` or `AppDogDetail` when the matching `#dog-list-app` or `#dog-detail-app` element is present.
- Twig provides the Vue mount elements and dog ID; the detail app loads its dog through the JSON API.
- Pages use normal full-document navigation, so there is no Turbo-specific mounting lifecycle.
- Vue is the only frontend interaction layer; Stimulus is not installed.
- Dog data is Doctrine-backed; there is no legacy in-memory dog repository in this checkout.

## Dog Details Page

The dog details page must include a full-width dog information section.

### Layout

- Use a two-column desktop layout.
- The left column occupies `40%` of the section width.
- The right column occupies the remaining `60%`.
- On smaller screens, stack the media and information columns vertically.

### Media Column

- Keep the media container at a `1:1` aspect ratio.
- For now, display `assets/images/rusty.mp4`.
- The video must cover the entire container without distortion.
- The video should autoplay, loop, remain muted, and play inline.

### Information Column

- Use `var(--bg-light)` as the column background.
- Display the dog's name as the section title.
- Place two white information cards below the title.
- Display the cards side by side on desktop and stack them on smaller screens.

The first card contains:

- Status
- Date of birth
- Date of adoption

The second card contains:

- Gender
- Height
- Weight

- Display `—` when a value is unavailable.

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
