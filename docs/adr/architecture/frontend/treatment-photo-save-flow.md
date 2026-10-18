# Save treatment details before changing the photo

Status: Accepted

## Context

The treatment form combines diary fields and an optional photo, but treatment
records and media have separate API operations and failure paths.

## Decision

Create or update the treatment first, then upload, replace, or remove its photo
if requested. Do not roll back saved treatment details when the photo operation
fails. Keep the form open and display the error.

After a successful creation response, retain the treatment as the active form
record before starting the photo operation. Retrying within that form updates
the same treatment instead of creating another one.

## Consequences

Saving the form is not atomic: an error can follow a successful treatment save.
Retry protection depends on retaining the returned identity; it does not cover
a creation request whose successful response never reaches the browser.

## References

- [Save orchestration](../../../../assets/js/modules/dogsDiary/view/composables/useDogTreatments.js)
- [Modal error and retry state](../../../../assets/js/modules/dogsDiary/view/composables/useAsyncModal.js)
- [Treatment persistence](../../../../src/Application/Treatment/TreatmentService.php)
- [Photo policy](../../domain/media/treatment-photo.md)
- [Upload protocol](../backend/staged-direct-uploads.md)
