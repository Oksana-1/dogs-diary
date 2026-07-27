<?php

namespace App\Controller\Web;

use App\Application\Dog\DogService;
use App\View\DogView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DogController extends AbstractController
{
    #[Route('/dog/{id<\d+>}', name: 'app_dog_index')]
    public function index(int $id, DogService $dogService): Response
    {
        $dog = $dogService->get($id);
        if (!$dog) {
            throw $this->createNotFoundException('Dog not found');
        }

        return $this->render('dogs/index.html.twig', ['dog' => DogView::from($dog)->toArray()]);
    }
}
