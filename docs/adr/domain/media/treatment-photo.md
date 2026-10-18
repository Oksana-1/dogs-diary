# Keep one replaceable photo per treatment

Status: Accepted

## Context

A treatment can carry a visual attachment alongside its product, dates, and note.

## Decision

Allow zero or one image per treatment. Uploading another image replaces the
existing photo while preserving its media record ID. Treatment media rejects
videos. Removing the photo leaves the treatment intact.

Entity rules and database constraints enforce the single-photo limit.
Replacement is serialized on the treatment.

## Consequences

The treatment exposes one current photo, with no application-level photo history.
Replacement changes the content behind the existing media identity. Storage
retention remains an operational recovery mechanism.

## References

- [Treatment photo model](../../../../src/Entity/Treatment.php)
- [Photo invariants](../../../../src/Entity/TreatmentMedia.php)
- [Replacement behavior](../../../../src/Application/TreatmentMedia/TreatmentMediaService.php)
- [Replacement and video-rejection scenarios](../../../../tests/Controller/DirectMediaUploadTest.php)
