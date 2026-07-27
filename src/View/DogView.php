<?php

namespace App\View;

use App\Entity\Dog;

final readonly class DogView extends AbstractView
{
    public function __construct(private Dog $dog) {}

    public static function from(Dog $dog): self
    {
        return new self($dog);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->dog->getId(),
            'name' => $this->dog->getName(),
            'birthDate' => $this->dog->getBirthDate()?->format('Y-m-d'),
            'adoptDate' => $this->dog->getAdoptDate()?->format('Y-m-d'),
            'weight' => $this->dog->getWeight(),
            'height' => $this->dog->getHeight(),
            'status' => $this->dog->getStatus(),
            'avatar' => $this->dog->getAvatar(),
        ];
    }
}
