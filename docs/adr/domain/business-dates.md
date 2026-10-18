# Keep business dates chronologically consistent

Status: Accepted

## Context

Dog milestones and treatment dates must remain consistent when records are
created or edited.

## Decision

Accept API dates as `YYYY-MM-DD`. A dog's required birth date cannot be after
today. Its optional adoption date cannot precede birth. A treatment's optional
due date cannot precede its treatment date. Equal dates are allowed.

Validate each date pair together at the HTTP boundary and in the entity.

## Consequences

Changing one date can invalidate its relationship with the other. The birth-date
cutoff uses the server's current day. Current validation permits future adoption
and treatment dates and does not compare treatment dates with the dog's birth.

## References

- [Dog date rules](../../../src/Entity/Dog.php)
- [Treatment date rules](../../../src/Entity/Treatment.php)
- [Dog request validation](../../../src/Controller/Api/Validation/DogBusinessDatesValidator.php)
- [Treatment request validation](../../../src/Controller/Api/Validation/TreatmentBusinessDatesValidator.php)
