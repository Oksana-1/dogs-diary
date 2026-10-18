# Dogs Diary

Dogs Diary manages dog profiles, treatments, and private photos or videos.
Symfony serves Twig pages enhanced by Vue 3 islands through AssetMapper and
ImportMap. A JSON API connects the UI to application services, Doctrine entities,
and PostgreSQL. Media uses local storage in development and private R2 in production.

All documents are listed below. They live under `adr/architecture`, `adr/domain`,
`adr/testing`, or `adr/tooling`, with specialization subfolders where useful.

## Architecture

Accepted ADRs record current decisions, not deployment verification.
Changed decisions get a successor record.

| Decision | Status | Area |
| --- | --- | --- |
| [Separate diary API responsibilities](adr/architecture/backend/separate-backend-responsibilities.md) | Accepted | Diary API controllers, application services, persistence, API views |
| [Share session authentication between pages and API](adr/architecture/backend/authentication.md) | Accepted | Authentication, sessions, password recovery |
| [Enforce resource access through owner-scoped queries](adr/architecture/backend/authorization.md) | Accepted | Authorization, application services, repositories |
| [Share security state in PostgreSQL](adr/architecture/db/shared-security-state.md) | Accepted | Database, authentication, runtime |
| [Keep production media in private R2 storage](adr/architecture/devops/private-production-media.md) | Accepted | Media storage, authorization, runtime |
| [Upload to staging before publishing media](adr/architecture/backend/staged-direct-uploads.md) | Accepted | Dog media, treatment media, frontend uploads |
| [Commit record changes before cleaning up media files](adr/architecture/backend/media-cleanup-after-commit.md) | Accepted | Diary deletion, photo replacement, storage consistency |
| [Use Vue islands without a Node build step](adr/architecture/frontend/vue-islands-without-node-build.md) | Accepted | Frontend asset delivery, page rendering |
| [Save treatment details before changing the photo](adr/architecture/frontend/treatment-photo-save-flow.md) | Accepted | Treatment form, photo operations, partial saves and retries |

Implementation and runtime guides:

- [API error contract](adr/architecture/backend/api-errors.md)
- [Production security](adr/architecture/backend/production-safety.md)
- [Shared state and hosted runtime](adr/architecture/devops/stateless-runtime.md)

## Domain

| Decision | Status | Area |
| --- | --- | --- |
| [Scope diary access through dog ownership](adr/domain/dogs/dog-ownership.md) | Accepted | Dogs, owners, nested records |
| [Record treatments per dog with multiple categories](adr/domain/treatments/treatment-records.md) | Accepted | Treatments, products, due dates |
| [Keep business dates chronologically consistent](adr/domain/business-dates.md) | Accepted | Dog milestones, treatment dates |
| [Select thumbnail and profile media independently](adr/domain/media/dog-media-roles.md) | Accepted | Dog media, list cards, profiles |
| [Keep one replaceable photo per treatment](adr/domain/media/treatment-photo.md) | Accepted | Treatments, images |
| [Delete dependent records with their diary entry](adr/domain/dependent-record-lifecycle.md) | Accepted | Dogs, treatments, media lifecycle |

## Testing

Plans describe proposed work and a historical baseline, separately from accepted ADRs.

- [Testing strategy](adr/testing/README.md)
- [Backend testing](adr/testing/backend/README.md)
- [Frontend testing](adr/testing/frontend/README.md)
- [Integration and browser testing](adr/testing/integrations/README.md)

## Tooling

- [Database migrations and restore compatibility](adr/tooling/db/migrations.md)
- [CI and local quality checks](adr/tooling/devops/ci.md)
- [Production Docker images](adr/tooling/devops/production-docker.md)
- [Production configuration](adr/tooling/devops/production-configuration.md)
- [R2 setup, uploads, retention, and recovery](adr/tooling/devops/r2-media.md)
