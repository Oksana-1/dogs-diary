<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/')]
    public function homepage(): Response
    {
        $dogsName = 'Sharik';

        return $this->render('main/homepage.html.twig', [
            'dogsName' => $dogsName
        ]);
    }
}
