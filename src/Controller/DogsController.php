<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dogs')]
class DogsController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCollection(DogsRepository $repository): Response
    {
        return $this->json($repository->findAll());
    }

    #[Route('/{id<\d+>}')]
    public function getItem(int $id, DogsRepository $repository): Response
    {
        $dog = $repository -> find($id);
        if (!$dog) {
            throw $this -> createNotFoundException('Dog not found');
        }
        return $this -> json($dog);
    }
}
