# API error contract

Every unsuccessful response from an `/api/*` route uses JSON and the following envelope:

```json
{
  "error": {
    "code": "resource_not_found",
    "message": "Dog not found"
  }
}
```

`error.code` is a stable, machine-readable identifier. `error.message` is a human-readable English summary and may be shown by the current UI. Clients must make decisions from `code` and the HTTP status, not by matching `message`.

Validation failures add a `violations` array:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The request contains invalid values.",
    "violations": [
      {
        "field": "birthDate",
        "message": "Birth date cannot be in the future."
      }
    ]
  }
}
```

`field` is the request property path reported by Symfony Validator. It is an empty string for a request-level violation. More than one violation may target the same field, and their order is not part of the contract.

## Status and code mapping

| HTTP status | Code | Meaning |
|---:|---|---|
| 400 | `invalid_request` | Malformed JSON or an otherwise invalid request |
| 401 | `authentication_required` | Authentication is required or has expired |
| 403 | `access_denied` | The authenticated client cannot perform the operation |
| 404 | `resource_not_found` | The requested API route or resource does not exist |
| 405 | `method_not_allowed` | The route exists but does not accept the HTTP method |
| 409 | `conflict` | The request conflicts with current persisted state |
| 413 | `payload_too_large` | The request or uploaded file exceeds its limit |
| 415 | `unsupported_media_type` | The request content type or upload type is unsupported |
| 422 | `validation_failed` | A well-formed request contains invalid values |
| 429 | `too_many_requests` | The client has exceeded a rate limit |
| 500 | `internal_error` | An unexpected server failure occurred |

Other HTTP statuses use `request_failed`. The mapping can be extended without changing the envelope.

Unexpected failures always return the generic message `An unexpected error occurred.` Exception messages, stack traces, SQL, storage paths, and other internal details must never be exposed. They remain available through server-side logging.

Successful responses are unchanged. In particular, successful delete operations continue to return HTTP 204 with an empty body.
