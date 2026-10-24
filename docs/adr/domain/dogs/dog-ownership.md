# Scope diary access through dog ownership

Status: Accepted

## Context

A dog's treatments and media form a private diary. Access must follow the dog
through every nested record.

## Decision

Assign a newly created dog to the current user. The model supports multiple
owners, with the same access to the shared dog. Treatments and media inherit
access through that dog's owners.

## Consequences

Knowing a record ID does not grant access. Shared ownership can exist in the
data; the current API assigns the creator but has no ownership-management routes.

## References

- [Dog ownership](../../../../src/Entity/Dog.php)
- [Dog creation](../../../../src/Application/Dog/DogService.php)
- [Authorization enforcement](../../architecture/backend/authorization.md)
- [Ownership scenarios](../../../../tests/Controller/OwnershipIsolationTest.php)
