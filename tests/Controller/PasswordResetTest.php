<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetTest extends WebTestCase
{
    private const EMAIL = 'jane@example.com';
    private const OLD_PASSWORD = 'correct horse battery staple';
    private const NEW_PASSWORD = 'an even better battery staple';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($this->entityManager))->createSchema($metadata);

        $this->user = (new User())
            ->setName('Jane Doe')
            ->setEmail(self::EMAIL);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->user->setPassword($hasher->hashPassword($this->user, self::OLD_PASSWORD));
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();

        $this->client->disableReboot();
    }

    public function testRequestFormUsesCsrfAndShowsTheSameResultForExistingAndUnknownEmails(): void
    {
        $crawler = $this->client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/reset-password"][method="post"]');
        self::assertSelectorExists('input[name="email"][required]');
        self::assertNotSame('', $crawler->filter('input[name="_csrf_token"]')->attr('value'));

        $this->client->submit($crawler->selectButton('Send reset link')->form(['email' => ' JANE@EXAMPLE.COM ']));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(1);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.auth-heading', 'If an account matches');
        self::assertSelectorTextNotContains('body', self::EMAIL);

        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->selectButton('Send reset link')->form(['email' => 'unknown@example.com']));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(0);
    }

    public function testRequestFormRejectsInvalidCsrfWithoutSendingMail(): void
    {
        $this->client->request('POST', '/reset-password', [
            'email' => self::EMAIL,
            '_csrf_token' => 'invalid',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'form expired');
        self::assertEmailCount(0);
    }

    public function testValidTokenResetsPasswordAndCannotBeReused(): void
    {
        $token = $this->requestToken();

        $crawler = $this->client->request('GET', '/reset-password/'.$token);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/reset-password/'.$token.'"][method="post"]');

        $this->client->submit($crawler->selectButton('Reset password')->form([
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]));

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.auth-notice', 'password has been reset');
        self::assertSame(0, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        self::assertInstanceOf(User::class, $user);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, self::NEW_PASSWORD));
        self::assertFalse($hasher->isPasswordValid($user, self::OLD_PASSWORD));

        $this->client->request('GET', '/reset-password/'.$token);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'invalid or has expired');
    }

    public function testResetFormValidatesCsrfStrengthAndConfirmation(): void
    {
        $token = $this->requestToken();

        $this->client->request('POST', '/reset-password/'.$token, [
            'password' => 'short',
            'password_confirmation' => 'different',
            '_csrf_token' => 'invalid',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'form expired');
        self::assertSelectorTextContains('#reset-password-error', 'at least 12');
        self::assertSelectorTextContains('#reset-password-confirmation-error', 'do not match');
        self::assertSame(1, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testRepeatedRequestsAreThrottledWithoutRevealingWhy(): void
    {
        $this->requestToken();

        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->selectButton('Send reset link')->form(['email' => self::EMAIL]));

        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(0);
        self::assertSame(1, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testInvalidAndExpiredTokensAreRejected(): void
    {
        $this->client->request('GET', '/reset-password/'.str_repeat('a', 40));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'invalid or has expired');

        $token = $this->requestToken();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE reset_password_request SET expires_at = :expired',
            ['expired' => '2000-01-01 00:00:00'],
        );
        $this->entityManager->clear();

        $this->client->request('GET', '/reset-password/'.$token);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'invalid or has expired');
    }

    private function requestToken(): string
    {
        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->selectButton('Send reset link')->form(['email' => self::EMAIL]));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(1);

        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'from', 'no-reply@dogs-diary.test');
        self::assertEmailAddressContains($message, 'to', self::EMAIL);
        self::assertEmailSubjectContains($message, 'Reset your Dogs Diary password');

        $html = $message->getHtmlBody();
        self::assertIsString($html);
        self::assertMatchesRegularExpression('#http://localhost/reset-password/[A-Za-z0-9]{40}#', $html);
        preg_match('#http://localhost/reset-password/([A-Za-z0-9]{40})#', $html, $matches);

        return $matches[1];
    }
}
