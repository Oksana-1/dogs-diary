# Share security state in PostgreSQL

Status: Accepted

## Context

Requests can reach different runtime instances. Local session, rate-limit, or
lock state cannot coordinate authentication and concurrent work across them.

## Decision

Store production sessions, rate-limit state, and lock leases in PostgreSQL.
Use Symfony's PDO session handler with transactional locking and a separate
connection from ORM transactions. Construct that connection through DBAL to
preserve TLS parameters. Keep limiter keys stable across builds and instances.

Development sessions remain local; ordinary functional tests use mock sessions.
Rate limits and locks use database storage in all environments.

## Consequences

Instances share security budgets and coordination state. Storage failures raise
errors rather than silently resetting limits. Session requests can occupy two
database connections, so hosting concurrency must respect connection limits.

## References

- [Session connection](../../../../src/Infrastructure/Security/DatabaseSessionHandlerFactory.php)
- [Session configuration](../../../../config/packages/framework.yaml)
- [Rate-limit configuration](../../../../config/packages/rate_limiter.yaml)
- [Lock configuration](../../../../config/packages/lock.yaml)
