# Delete dependent records with their diary entry

Status: Accepted

## Context

Treatments and media belong to their parent diary entries and must not remain
as detached application records after deletion.

## Decision

Deleting a dog removes its treatments, dog media, treatment photos, and ownership
links. Deleting a treatment removes its photo while retaining the dog. These
operations delete database records; the application has no archive or recycle bin.

## Consequences

Under current permissions, any owner can delete a shared dog for all its owners.
Storage retention alone cannot reconstruct deleted diary records.

## References

- [Database and file cleanup ordering](../architecture/backend/media-cleanup-after-commit.md)
- [Dog deletion](../../../src/Application/Dog/DogService.php)
- [Treatment deletion](../../../src/Application/Treatment/TreatmentService.php)
- [Dog relationships](../../../src/Entity/Dog.php)
- [Treatment relationships](../../../src/Entity/Treatment.php)
