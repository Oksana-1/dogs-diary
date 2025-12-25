<?php

namespace App\Model;

readonly class Dog
{
    public function __construct(
        private int $id,
        private string $name,
        private string $birth_date,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBirthDate(): string
    {
        return $this->birth_date;
    }
}
