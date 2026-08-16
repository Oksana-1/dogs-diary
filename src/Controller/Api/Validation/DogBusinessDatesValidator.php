<?php

namespace App\Controller\Api\Validation;

use App\Controller\Api\Dto\CreateDogPayload;
use App\Controller\Api\Dto\UpdateDogPayload;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class DogBusinessDatesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DogBusinessDates) {
            throw new UnexpectedTypeException($constraint, DogBusinessDates::class);
        }

        if (!$value instanceof CreateDogPayload && !$value instanceof UpdateDogPayload) {
            throw new UnexpectedValueException($value, CreateDogPayload::class.' or '.UpdateDogPayload::class);
        }

        $birthDate = $this->parseDate($value->birthDate);
        if (null === $birthDate) {
            return;
        }

        if ($birthDate > new \DateTimeImmutable('today')) {
            $this->context->buildViolation('Birth date cannot be in the future.')
                ->atPath('birthDate')
                ->addViolation();
        }

        $adoptDate = $this->parseDate($value->adoptDate);
        if (null !== $adoptDate && $adoptDate < $birthDate) {
            $this->context->buildViolation('Adoption date cannot be before birth date.')
                ->atPath('adoptDate')
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
