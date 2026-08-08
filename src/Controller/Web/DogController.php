<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DogController extends AbstractController
{
    #[Route('/dog/{id<\d+>}', name: 'app_dog_index')]
    public function index(int $id): Response
    {
        return $this->render('dogs/index.html.twig', ['dogId' => $id]);
    }
}
