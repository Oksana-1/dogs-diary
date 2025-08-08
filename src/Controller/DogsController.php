<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DogsController extends AbstractController
{
    #[Route('/api/dogs')]
    public function getCollection(DogsRepository $repository): Response
    {
        return $this->json($repository->findAll());
    }
}
