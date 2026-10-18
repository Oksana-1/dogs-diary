# Keep production media in private R2 storage

Status: Accepted

## Context

The hosted runtime's filesystem is not durable media storage. Dog and treatment
media must remain private and accessible only through owner-authorized requests.

## Decision

Use private R2 storage in production and local filesystem storage for development
and isolated tests, behind a shared storage interface. Keep metadata and storage
keys in the database. Expose authenticated application URLs; after authorization,
redirect R2 downloads to short-lived signed URLs. Production startup requires R2.

## Consequences

Media survives instance replacement. Local development retains filesystem support.
Issued signed URLs remain bearer links until expiry, even after logout.
Database and media recovery must be coordinated; external storage alone does not
prove recoverability.

## References

- [Storage interface](../../../../src/Application/Media/MediaStorageInterface.php)
- [R2 adapter](../../../../src/Infrastructure/Media/R2MediaStorage.php)
- [Production startup](../../../../docker/vercel-entrypoint.sh)
- [Staged uploads](../backend/staged-direct-uploads.md)
