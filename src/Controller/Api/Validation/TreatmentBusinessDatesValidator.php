<?php

namespace App\Controller\Api\Validation;

use App\Controller\Api\Dto\CreateTreatmentPayload;
use App\Controller\Api\Dto\UpdateTreatmentPayload;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class TreatmentBusinessDatesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TreatmentBusinessDates) {
            throw new UnexpectedTypeException($constraint, TreatmentBusinessDates::class);
        }

        if (!$value instanceof CreateTreatmentPayload && !$value instanceof UpdateTreatmentPayload) {
            throw new UnexpectedValueException($value, CreateTreatmentPayload::class.' or '.UpdateTreatmentPayload::class);
        }

        $treatmentDate = $this->parseDate($value->treatmentDate);
        $dueDate = $this->parseDate($value->dueDate);
        if (null !== $treatmentDate && null !== $dueDate && $dueDate < $treatmentDate) {
            $this->context->buildViolation('Due date cannot be before treatment date.')
                ->atPath('dueDate')
                ->addViolation();
        }
    }

    private function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false !== $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
