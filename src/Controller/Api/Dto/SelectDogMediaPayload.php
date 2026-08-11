<?php

namespace App\Controller\Api\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SelectDogMediaPayload
{
    public function __construct(
        #[Assert\Positive]
        public int $mediaId,
    ) {
    }
}
