<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/healthz', name: 'app_health', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
