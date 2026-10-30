<?php

declare(strict_types=1);

namespace App\Controller\Web\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationData
{
    #[Assert\NotBlank(message: 'auth.validation.name_required')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'auth.validation.name_short',
        maxMessage: 'auth.validation.name_long',
    )]
    public string $name = '';

    #[Assert\NotBlank(message: 'auth.validation.email_required')]
    #[Assert\Email(message: 'auth.validation.email_invalid')]
    #[Assert\Length(max: 180, maxMessage: 'auth.validation.email_long')]
    public string $email = '';

    #[Assert\NotBlank(message: 'auth.validation.password_required')]
    #[Assert\Length(
        min: 12,
        max: 4096,
        minMessage: 'auth.validation.password_short',
        maxMessage: 'auth.validation.password_long',
    )]
    public string $password = '';

    #[Assert\NotBlank(message: 'auth.validation.confirmation_required')]
    #[Assert\EqualTo(propertyPath: 'password', message: 'auth.validation.password_mismatch')]
    public string $passwordConfirmation = '';

    #[Assert\IsTrue(message: 'auth.validation.terms_required')]
    public bool $termsAccepted = false;
}
