<?php

namespace App\Controller;

use App\Repository\DogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/')]
    public function homepage(DogsRepository $dogsRepository): Response
    {
        $dogsName = 'Sharik';
        $dogs = $dogsRepository->findAll();

        return $this->render('main/homepage.html.twig', [
            'dogsName' => $dogsName,
            'dogs' => $dogs,
        ]);
    }
}
