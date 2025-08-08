<?php

namespace App\Model;

class Dog
{
    public function __construct(
        private int $id,
        private string $name,
        private string $birth_date,
    ) {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getBirthDate()
    {
        return $this->birth_date;
    }
}
