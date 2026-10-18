# Separate diary API responsibilities

Status: Accepted

## Context

The diary API separates HTTP handling, application use cases, persistence, and
response representation. Keep each concern explicit and maintainable.

## Decision

Use controllers and request DTOs for HTTP mapping and validation, application
services for use cases, repositories for queries, entities for model state, and
API views for JSON response mapping. Controllers map request DTOs to application
data objects. Services coordinate Doctrine writes and transactions directly.

This describes the diary API, not every backend flow. Password recovery still
coordinates persistence and email delivery in its web controller.

## Consequences

Request validation, use-case behavior, queries, and response fields have clear
places to change. API views select exposed fields explicitly. Separate request
and application data objects require additional mapping; application services
retain their Doctrine dependency.

## References

- [HTTP mapping](../../../../src/Controller/Api/DogApiController.php)
- [Application service](../../../../src/Application/Dog/DogService.php)
- [Repository](../../../../src/Repository/DogRepository.php)
- [API view](../../../../src/View/DogView.php)
- [Password recovery exception](../../../../src/Controller/Web/PasswordResetController.php)
