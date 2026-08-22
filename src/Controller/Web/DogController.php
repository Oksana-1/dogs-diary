<?php

namespace App\Controller\Web;

use App\Application\Dog\DogService;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class DogController extends AbstractController
{
    #[Route('/dog/{id<\d+>}', name: 'app_dog_index')]
    public function index(int $id, #[CurrentUser] User $owner, DogService $dogService): Response
    {
        if (!$dogService->get($id, $owner)) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->render('dogs/index.html.twig', ['dogId' => $id]);
    }
}
