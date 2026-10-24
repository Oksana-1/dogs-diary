# Commit record changes before cleaning up media files

Status: Accepted

## Context

Diary records and media files live in separate storage systems. A database
transaction cannot atomically commit changes in both.

## Decision

When deleting dogs, treatments, or media, commit database deletion before
requesting file cleanup. When replacing a treatment photo, commit the new file
reference before cleaning up the previous file.

Application services attempt cleanup immediately after commit. Log storage
failures without rolling back the committed record changes.

## Consequences

Cleanup failures do not undo a successful diary change. Database and storage
can temporarily disagree; leftover files require an audit and cleanup. This
ordering does not provide atomic deletion or a durable retry queue.

## References

- [Dependent record lifecycle](../../domain/dependent-record-lifecycle.md)
- [Dog deletion](../../../../src/Application/Dog/DogService.php)
- [Treatment deletion](../../../../src/Application/Treatment/TreatmentService.php)
- [Dog media deletion](../../../../src/Application/DogMedia/DogMediaService.php)
- [Photo replacement and deletion](../../../../src/Application/TreatmentMedia/TreatmentMediaService.php)
