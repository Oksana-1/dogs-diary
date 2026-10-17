# Backend testing plan

## Goal

Protect domain rules, application services, HTTP contracts, persistence,
security boundaries, and private media behavior with a layered PHPUnit suite.
Keep pure tests fast while retaining realistic Symfony and PostgreSQL checks for
behavior that depends on framework or database semantics.

## Current state

The backend suite currently passes 87 tests with 752 assertions. Its strongest
areas are:

- login, logout, registration, and password reset;
- public and protected route behavior;
- API CSRF enforcement;
- ownership isolation for dogs, treatments, and media;
- private media authorization, headers, missing files, and byte ranges;
- production error pages and security response headers;
- selected user entity, media validation, local storage, and production
  configuration behavior.

The suite already contains many functional tests even though it is commonly
described as the backend unit suite. These tests should remain: they verify
valuable request-to-response contracts that isolated unit tests cannot replace.

PHP code coverage is not currently available. CI configures PHP with
`coverage: none`, and the normal local runtime does not provide Xdebug or PCOV.

## Layering model

### Unit tests

Use plain PHPUnit tests for deterministic PHP behavior that needs neither the
Symfony kernel nor a database:

- value normalization and entity invariants;
- business-date validation rules;
- upload validation decisions;
- view mapping and URL generation;
- service branching with explicit collaborator doubles;
- storage-key validation and other pure security rules.

### Application and framework integration tests

Boot the kernel when testing dependency wiring or Symfony behavior:

- application services with real validators and serializers;
- request DTO validation and consistent API errors;
- event subscribers and security handlers;
- mail creation and password-reset integration;
- filesystem behavior using a unique temporary directory per test.

### HTTP functional tests

Use Symfony's browser client for externally visible contracts:

- status, headers, redirects, and response payloads;
- authentication, CSRF, and ownership boundaries;
- complete API mutations and validation failures;
- transactional behavior across entities and stored media.

Do not duplicate every controller scenario at unit level. Unit-test decisions
with meaningful branching and keep route wiring in functional tests.

### PostgreSQL tests

SQLite is appropriate for fast feedback but cannot prove all PostgreSQL
behavior. Retain the clean PostgreSQL migration and schema job, and add focused
PostgreSQL integration tests only for behavior that depends on database-specific
constraints, transactions, locking, or SQL semantics.

## Priority gaps

### Priority 1: application services and cleanup

- Add direct tests for dog, treatment, dog-media, and treatment-media services.
- Verify ownership is assigned during creation and checked during mutation.
- Verify replacing and deleting media keeps database rows and files consistent.
- Verify partial failure behavior: database failures must not silently orphan or
  delete the wrong file.
- Verify dog and treatment deletion cascades through both records and storage.

### Priority 2: validation and API contracts

- Cover dog and treatment business-date validators at boundary dates.
- Cover malformed JSON, wrong scalar types, unknown enum values, missing fields,
  conflicting dates, and empty treatment types.
- Assert the stable error envelope and field-violation shape documented in
  `docs/API_ERRORS.md`.
- Test successful create and update response bodies, not only access control.

### Priority 3: entities and views

- Cover dog, treatment, media, and reset-request invariants directly.
- Verify both sides of important Doctrine relationships remain synchronized.
- Verify API views do not leak private filesystem paths or sensitive account
  information.
- Cover media URL generation for each owner and media type.

### Priority 4: operational commands and failure paths

- Add non-destructive audit-command tests for missing files, orphan files, valid
  records, and exit status.
- Test deletion mode only against isolated temporary storage.
- Expand production-configuration tests when a new required secret or deployment
  invariant is introduced.
- Preserve friendly production failures without exposing stack traces or secret
  values.

## Coverage implementation

1. Enable PCOV or Xdebug in a dedicated coverage CI job.
2. Run PHPUnit with text output for humans and Clover or Cobertura output for
   CI reporting.
3. Include eligible code under `src/` and document any exclusions.
4. Record class, method, line, and branch coverage where the driver supports it.
5. Publish the baseline before setting a global threshold.
6. Start with regression protection and higher expectations for new code rather
   than forcing low-value tests across every legacy line.

The normal fast PHPUnit job may keep coverage disabled if instrumentation makes
it materially slower. The coverage job can run separately and still be a
required check once stable.

## Test-data and isolation rules

- Every test owns its users, dogs, treatments, media records, and files.
- Use factories or builders only when they clarify valid setup; do not hide the
  important values for the behavior under test.
- Use unique temporary directories and remove them after the test.
- Freeze time for expiry, birthday, throttling, and boundary-date behavior.
- Never rely on test order or persistent local database contents.
- Never log secrets, password hashes, reset tokens, or database connection
  strings in a failure message.

## Completion criteria

- The existing security and ownership suite remains green.
- Application services and business validators have direct behavioral tests.
- Critical write APIs cover successful, invalid, unauthorized, and forbidden
  outcomes.
- Media replacement and cleanup behavior is tested across database and storage
  boundaries.
- The clean PostgreSQL migration/schema job remains required.
- A complete `src/` coverage report is published and protected against
  regression.

