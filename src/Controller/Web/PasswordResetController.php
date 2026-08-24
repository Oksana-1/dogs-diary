<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
final class PasswordResetController extends AbstractController
{
    private const REQUEST_CSRF_ID = 'reset_password_request';
    private const RESET_CSRF_ID = 'reset_password';

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%app.mailer.sender_address%')]
        private readonly string $senderAddress,
        #[Autowire('%app.mailer.sender_name%')]
        private readonly string $senderName,
        #[Autowire('%app.base_url%')]
        private readonly string $appBaseUrl,
    ) {
    }

    #[Route('', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $email = '';
        $errors = [];

        if ($request->isMethod('POST')) {
            $email = User::normalizeEmail($request->request->getString('email'));

            if (!$this->isCsrfTokenValid(self::REQUEST_CSRF_ID, $request->request->getString('_csrf_token'))) {
                $errors[] = 'Your password reset form expired. Please try again.';
            }
            if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if ([] === $errors) {
                $this->sendResetEmailIfPossible($email, $mailer, $urlGenerator);

                return $this->redirectToRoute('app_check_email');
            }
        }

        return $this->renderRequestPage($email, $errors, [] === $errors ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/check-email', name: 'app_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('auth/reset_password_check_email.html.twig', [
            'token_lifetime_minutes' => (int) ceil($this->resetPasswordHelper->getTokenLifetime() / 60),
        ]);
    }

    #[Route('/{token}', name: 'app_reset_password', methods: ['GET', 'POST'], requirements: ['token' => '[A-Za-z0-9]+'])]
    public function reset(
        string $token,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            return $this->renderRequestPage('', [
                'This password reset link is invalid or has expired. Please request a new one.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$user instanceof User) {
            throw new \LogicException(sprintf('Password reset token returned an unsupported user of type %s.', get_debug_type($user)));
        }

        $errors = [];
        if ($request->isMethod('POST')) {
            $password = $request->request->getString('password');
            $confirmation = $request->request->getString('password_confirmation');

            if (!$this->isCsrfTokenValid(self::RESET_CSRF_ID, $request->request->getString('_csrf_token'))) {
                $errors['global'][] = 'Your password reset form expired. Please try again.';
            }
            if (mb_strlen($password) < 12) {
                $errors['password'][] = 'Your password must be at least 12 characters.';
            }
            if ($password !== $confirmation) {
                $errors['password_confirmation'][] = 'The passwords do not match.';
            }

            if ([] === $errors) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $this->resetPasswordHelper->removeResetRequest($token);
                $this->entityManager->flush();

                $this->addFlash('password_reset_success', 'Your password has been reset. You can now log in.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/reset_password.html.twig', [
            'reset_token' => $token,
            'reset_errors' => $errors,
        ], new Response(status: [] === $errors ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * The response deliberately stays neutral for unknown accounts and throttled requests.
     */
    private function sendResetEmailIfPossible(
        string $email,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
    ): void {
        $user = $this->users->loadUserByIdentifier($email);
        if (null === $user) {
            return;
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return;
        }

        $resetPath = $urlGenerator->generate('app_reset_password', [
            'token' => $resetToken->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
        $resetUrl = rtrim($this->appBaseUrl, '/').$resetPath;

        $mailer->send(
            (new TemplatedEmail())
                ->from(new Address($this->senderAddress, $this->senderName))
                ->to($user->getEmail())
                ->subject('Reset your Dogs Diary password')
                ->htmlTemplate('auth/reset_password_email.html.twig')
                ->context([
                    'user' => $user,
                    'reset_url' => $resetUrl,
                    'expires_at' => $resetToken->getExpiresAt(),
                    'token_lifetime_minutes' => (int) ceil($this->resetPasswordHelper->getTokenLifetime() / 60),
                ]),
        );
    }

    /**
     * @param list<string> $errors
     */
    private function renderRequestPage(string $email, array $errors, int $status): Response
    {
        return $this->render('auth/reset_password_request.html.twig', [
            'request_email' => $email,
            'request_errors' => $errors,
        ], new Response(status: $status));
    }
}
