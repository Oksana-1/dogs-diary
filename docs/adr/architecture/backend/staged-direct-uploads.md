# Upload to staging before publishing media

Status: Accepted

## Context

Supported media sizes exceed the hosted request payload limit. Browser
declarations cannot establish file validity, and reusable upload URLs must not
allow validated media to be overwritten.

## Decision

Issue an owner-scoped upload ticket and signed R2 staging URL. Upload bytes
directly from the browser, then finalize through the authenticated,
CSRF-protected API. Download and validate the staged bytes on the server before
writing them to a new canonical key. Persist media metadata and ticket completion
together. Serialize finalization with a database-backed lock. Completed replays
return existing media only while the ticket remains valid and its result still
exists. Local storage retains multipart uploads.

## Consequences

Initial uploads bypass the application's request payload limit. Validation still
uses server bandwidth and temporary disk. The flow requires staging cleanup and
handles retries. Application quotas do not prevent oversized staging uploads or
cap storage-provider spending.

## References

- [Upload orchestration](../../../../src/Application/Media/MediaUploadService.php)
- [Browser upload flow](../../../../assets/js/modules/dogsDiary/repositories/MediaUpload.js)
- [Shared locks](../db/shared-security-state.md)
- [Private storage](../devops/private-production-media.md)
