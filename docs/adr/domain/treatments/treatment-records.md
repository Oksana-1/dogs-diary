# Record treatments per dog with multiple categories

Status: Accepted

## Context

The diary needs to record which product was used, when, and which treatment
categories it covers.

## Decision

Each treatment belongs to one dog. Require a product name, treatment date, and
at least one supported category: `flea_tick` or `anti_worm`. A single record can
include both categories. Product names are free text.

The due date and note are optional. The due date is entered explicitly; the
record stores no recurrence rule. List treatments by treatment date, newest first.

## Consequences

One record can represent a product covering both categories. Adding a category
requires coordinated enum, validation, and UI changes. The model records dates
without calculating a treatment schedule.

## References

- [Treatment model](../../../../src/Entity/Treatment.php)
- [Required fields and categories](../../../../src/Controller/Api/Dto/CreateTreatmentPayload.php)
- [Treatment ordering](../../../../src/Repository/TreatmentRepository.php)
- [Category selection](../../../../assets/js/modules/dogsDiary/view/ui/modals/TreatmentFormModal.js)
