<?php

namespace App\Controller\Web;

use App\Repository\DogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/')]
    public function homepage(DogRepository $dogRepository): Response
    {
        $dogsName = 'Sharik';
        $dogs = $dogRepository->findAll();
        $myDog = $dogRepository->findOneBy([], ['id' => 'ASC']);

        return $this->render('main/homepage.html.twig', [
            'dogsName' => $dogsName,
            'dogs' => $dogs,
            'myDog' => $myDog,
        ]);
    }
}
