<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DogController extends AbstractController
{
    #[Route('/dogs/{id<\d+>}', name: 'app_dog_index')]
    public function index(int $id, DogsRepository $repository): Response
    {
        $dog = $repository->find($id);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->render('dogs/index.html.twig', ['dog' => $dog]);
    }
}
