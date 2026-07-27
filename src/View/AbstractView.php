<?php

namespace App\View;

abstract readonly class AbstractView implements \JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
