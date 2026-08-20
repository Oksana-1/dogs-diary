<?php

declare(strict_types=1);

namespace App\Controller\Web\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationData
{
    #[Assert\NotBlank(message: 'Please enter your name.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Your name must be at least {{ limit }} characters long.',
        maxMessage: 'Your name cannot be longer than {{ limit }} characters.',
    )]
    public string $name = '';

    #[Assert\NotBlank(message: 'Please enter your email address.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    #[Assert\Length(max: 180, maxMessage: 'Your email address cannot be longer than {{ limit }} characters.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Please create a password.')]
    #[Assert\Length(
        min: 12,
        max: 4096,
        minMessage: 'Your password must be at least {{ limit }} characters long.',
        maxMessage: 'Your password is too long.',
    )]
    public string $password = '';

    #[Assert\NotBlank(message: 'Please confirm your password.')]
    #[Assert\EqualTo(propertyPath: 'password', message: 'The passwords do not match.')]
    public string $passwordConfirmation = '';

    #[Assert\IsTrue(message: 'You must accept the Terms of Service and Privacy Policy.')]
    public bool $termsAccepted = false;
}
