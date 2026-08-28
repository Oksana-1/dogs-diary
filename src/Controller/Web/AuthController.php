<?php

namespace App\Controller\Web;

use App\Controller\Web\Dto\RegistrationData;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_main');
        }

        $notices = $request->getSession()->getFlashBag()->get('password_reset_success');

        if ('session_expired' === $request->query->getString('reason')) {
            $notices[] = 'Your session expired. Please log in again.';
        }

        return $this->render('auth/login.html.twig', [
            'last_email' => User::normalizeEmail($authenticationUtils->getLastUsername()),
            'login_error' => null !== $authenticationUtils->getLastAuthenticationError()
                ? 'Invalid email or password.'
                : null,
            'login_notices' => $notices,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('The firewall intercepts logout requests before this controller is called.');
    }

    #[Route('/sign-up', name: 'app_sign_up', methods: ['GET', 'POST'])]
    public function signUp(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_main');
        }

        $registration = new RegistrationData();

        if (!$request->isMethod('POST')) {
            return $this->renderSignUp($registration);
        }

        $registration->name = trim($request->request->getString('name'));
        $registration->email = User::normalizeEmail($request->request->getString('email'));
        $registration->password = $request->request->getString('password');
        $registration->passwordConfirmation = $request->request->getString('password_confirmation');
        $registration->termsAccepted = $request->request->has('terms');

        /** @var array<string, list<string>> $errors */
        $errors = [];

        if (!$this->isCsrfTokenValid('registration', $request->request->getString('_csrf_token'))) {
            $errors['global'][] = 'Your registration form expired. Please try again.';
        }

        foreach ($validator->validate($registration) as $violation) {
            $field = match ($violation->getPropertyPath()) {
                'passwordConfirmation' => 'password_confirmation',
                'termsAccepted' => 'terms',
                default => $violation->getPropertyPath(),
            };
            $errors[$field][] = $violation->getMessage();
        }

        if (!isset($errors['global'], $errors['email']) && null !== $userRepository->loadUserByIdentifier($registration->email)) {
            $errors['email'][] = 'An account already exists for this email address.';
        }

        if ([] !== $errors) {
            return $this->renderSignUp($registration, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = (new User())
            ->setName($registration->name)
            ->setEmail($registration->email);
        $user->setPassword($passwordHasher->hashPassword($user, $registration->password));

        try {
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $errors['email'][] = 'An account already exists for this email address.';

            return $this->renderSignUp($registration, $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $security->login($user) ?? $this->redirectToRoute('app_main');
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderSignUp(
        RegistrationData $registration,
        array $errors = [],
        int $status = Response::HTTP_OK,
    ): Response {
        return $this->render('auth/sign_up.html.twig', [
            'registration_values' => [
                'name' => $registration->name,
                'email' => $registration->email,
                'terms' => $registration->termsAccepted,
            ],
            'registration_errors' => $errors,
        ], new Response(status: $status));
    }
}
