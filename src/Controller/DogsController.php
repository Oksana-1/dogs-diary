<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Model\Dog;

class DogsController extends AbstractController
{
    #[Route('/api/dogs')]
    public function getCollection(): Response
    {
        $dogs = [
            new Dog(1, 'Sharik', '15-08-2017'),
            new Dog(2, 'Rusty', '15-09-2021')
        ];

        return $this->json($dogs);
    }
}
