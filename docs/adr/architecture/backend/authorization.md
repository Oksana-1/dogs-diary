# Enforce resource access through owner-scoped queries

Status: Accepted

## Context

Authentication identifies a user but does not grant access to every diary.
The [dog ownership policy](../../domain/dogs/dog-ownership.md) must apply to
pages, API operations, and media delivery.

## Decision

Require `ROLE_USER` for application routes, with explicit public entry points.
Controllers obtain the user from Symfony Security and pass that identity to
application services, never taking the owner from request payloads.

Services use repository queries scoped to the owner and all parent resource IDs.
Missing or inaccessible resources return `404`. Authorize media access before
streaming a file or issuing a signed storage URL.

## Consequences

Queries enforce access while loading records and avoid disclosing foreign
resource existence. Every new resource operation must preserve owner and parent
scoping; the route-level role check alone is insufficient.

## References

- [Route access rules](../../../../config/packages/security.yaml)
- [Application service](../../../../src/Application/Dog/DogService.php)
- [Nested resource queries](../../../../src/Repository/TreatmentMediaRepository.php)
- [Ownership isolation scenarios](../../../../tests/Controller/OwnershipIsolationTest.php)
