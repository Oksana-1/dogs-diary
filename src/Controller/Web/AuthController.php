<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/sign-up', name: 'app_sign_up', methods: ['GET'])]
    public function signUp(): Response
    {
        return $this->render('auth/sign_up.html.twig');
    }

    #[Route('/reset-password', name: 'app_forgot_password_request', methods: ['GET'])]
    public function requestPasswordReset(): Response
    {
        return $this->render('auth/reset_password_request.html.twig');
    }

    #[Route('/reset-password/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('auth/reset_password_check_email.html.twig');
    }

    #[Route('/reset-password/new', name: 'app_reset_password', methods: ['GET'])]
    public function resetPassword(): Response
    {
        return $this->render('auth/reset_password.html.twig');
    }
}
