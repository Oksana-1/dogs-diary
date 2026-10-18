<?php

declare(strict_types=1);

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;

#[Route('/api/docs', env: 'dev')]
final class ApiDocumentationController extends AbstractController
{
    #[Route('', name: 'app_dev_api_docs', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('api/docs.html.twig', response: new Response(headers: [
            'Cache-Control' => 'private, no-store',
        ]));
    }

    #[Route('/openapi.json', name: 'app_dev_api_spec', methods: ['GET'])]
    public function specification(
        #[Autowire('%kernel.project_dir%/docs/adr/tooling/backend/openapi.yaml')]
        string $specificationPath,
    ): JsonResponse {
        return new JsonResponse(Yaml::parseFile($specificationPath), headers: [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
