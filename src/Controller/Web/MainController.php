<?php

namespace App\Controller\Web;

use App\Application\Dog\DogService;
use App\View\DogView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function homepage(DogService $dogService): Response
    {
        $dogsName = 'Sharik';
        $dogs = array_map(fn ($dog) => DogView::from($dog)->toArray(), $dogService->list());
        $myDog = $dogService->list()[0] ?? null;
        $myDogView = $myDog ? DogView::from($myDog)->toArray() : null;

        return $this->render('main/homepage.html.twig', [
            'dogsName' => $dogsName,
            'dogs' => $dogs,
            'myDog' => $myDogView,
        ]);
    }
}
