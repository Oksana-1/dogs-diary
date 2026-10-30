<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Localization\LocalePolicy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
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

    #[DataProvider('locales')]
    public function testRequestFormUsesCsrfAndShowsTheSameResultForExistingAndUnknownEmails(string $locale): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, $locale));
        $crawler = $this->client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('meta[name="viewport"][content="width=device-width, initial-scale=1"]');
        self::assertSelectorExists('form[action="/reset-password"][method="post"]');
        self::assertSelectorExists('input[name="email"][required]');
        self::assertNotSame('', $crawler->filter('input[name="_csrf_token"]')->attr('value'));

        $action = 'uk' === $locale ? 'Надіслати посилання' : 'Send reset link';
        $this->client->submit($crawler->selectButton($action)->form(['email' => ' JANE@EXAMPLE.COM ']));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(1);

        $this->client->followRedirect();
        self::assertSelectorTextContains('.auth-heading', 'uk' === $locale ? 'Якщо обліковий запис' : 'If an account matches');
        self::assertResponseHeaderSame('Content-Language', $locale);
        self::assertSelectorTextNotContains('body', self::EMAIL);
        $existingAccountConfirmation = $this->client->getCrawler()->filter('.auth-card')->text(normalizeWhitespace: true);

        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->selectButton($action)->form(['email' => 'unknown@example.com']));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(0);

        $this->client->followRedirect();
        self::assertSame(
            $existingAccountConfirmation,
            $this->client->getCrawler()->filter('.auth-card')->text(normalizeWhitespace: true),
        );
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

    public function testPasswordResetInvalidatesAnExistingRememberMeCookie(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->selectButton('Login')->form([
            'email' => self::EMAIL,
            'password' => self::OLD_PASSWORD,
            'remember_me' => '1',
        ]));
        self::assertResponseRedirects('/');

        $rememberMeCookie = $this->client->getCookieJar()->get('REMEMBERME');
        self::assertNotNull($rememberMeCookie);

        $token = $this->requestToken();
        $crawler = $this->client->request('GET', '/reset-password/'.$token);
        $this->client->submit($crawler->selectButton('Reset password')->form([
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]));
        self::assertResponseRedirects('/login');

        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set($rememberMeCookie);
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    #[DataProvider('locales')]
    public function testRepeatedRequestsAreThrottledWithoutRevealingWhy(string $locale): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, $locale));
        $this->requestToken();
        $this->client->followRedirect();
        $confirmation = $this->client->getCrawler()->filter('.auth-card')->text(normalizeWhitespace: true);

        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->filter('form.auth-form')->form(['email' => self::EMAIL]));

        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(0);
        self::assertSame(1, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));
        $this->client->followRedirect();
        self::assertSame($confirmation, $this->client->getCrawler()->filter('.auth-card')->text(normalizeWhitespace: true));
    }

    #[DataProvider('locales')]
    public function testInvalidAndExpiredTokensAreRejected(string $locale): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, $locale));
        $error = 'uk' === $locale ? 'Посилання для відновлення пароля недійсне' : 'invalid or has expired';
        $this->client->request('GET', '/reset-password/'.str_repeat('a', 40));
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', $error);

        $token = $this->requestToken();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE reset_password_request SET expires_at = :expired',
            ['expired' => '2000-01-01 00:00:00'],
        );
        $this->entityManager->clear();

        $this->client->request('GET', '/reset-password/'.$token);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', $error);
        self::assertSelectorNotExists('input[name="password"]');
    }

    public function testResetPagesFollowThisBrowserAndEmailReceivesItsLocaleContext(): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'uk'));
        $token = $this->requestToken();
        $message = self::getMailerMessage();
        self::assertInstanceOf(TemplatedEmail::class, $message);
        self::assertSame('uk', $message->getLocale());
        self::assertStringContainsString('lang="uk"', $message->getHtmlBody());
        self::assertStringContainsString('Виберіть новий пароль', $message->getHtmlBody());

        $this->client->request('GET', '/reset-password/'.$token);
        self::assertSelectorExists('html[lang="uk"]');
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'en'));
        $this->client->request('GET', '/reset-password/'.$token);
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextSame('h1', 'Choose a new password');
        self::assertStringContainsString('Виберіть новий пароль', $message->getHtmlBody());
    }

    public function testUkrainianRequestValidationPreservesEmailAndDoesNotSendMail(): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'uk'));
        $this->client->request('POST', '/reset-password', ['email' => ' INVALID@ ', '_csrf_token' => 'invalid']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('[role="alert"]', 'Термін дії форми відновлення пароля');
        self::assertSelectorTextContains('[role="alert"]', 'Введіть коректну електронну адресу.');
        self::assertSelectorExists('input[name="email"][value="invalid@"][aria-invalid="true"]');
        self::assertSelectorTextSame('button[type="submit"]', 'Надіслати посилання');
        self::assertEmailCount(0);
        self::assertSame(0, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));
    }

    public function testUkrainianRecoveryValidatesResetsAndRejectsReusedToken(): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'uk'));
        $token = $this->requestToken();
        $this->client->request('POST', '/reset-password/'.$token, [
            'password' => 'short', 'password_confirmation' => 'different', '_csrf_token' => 'invalid',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'Термін дії форми');
        self::assertSelectorTextContains('#reset-password-error', 'щонайменше 12');
        self::assertSelectorTextContains('#reset-password-confirmation-error', 'Паролі не збігаються');
        $crawler = $this->client->request('GET', '/reset-password/'.$token);
        $this->client->submit($crawler->selectButton('Змінити пароль')->form([
            'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD,
        ]));
        self::assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.auth-notice', 'Пароль змінено');
        $this->client->submit($crawler->selectButton('Увійти')->form([
            'email' => self::EMAIL, 'password' => self::NEW_PASSWORD,
        ]));
        self::assertResponseRedirects('/');
        $this->client->request('GET', '/reset-password/'.$token);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'Посилання для відновлення пароля недійсне');
    }

    public static function locales(): iterable
    {
        yield 'English' => ['en'];
        yield 'Ukrainian' => ['uk'];
    }

    private function requestToken(): string
    {
        $crawler = $this->client->request('GET', '/reset-password');
        $this->client->submit($crawler->filter('form.auth-form')->form(['email' => self::EMAIL]));
        self::assertResponseRedirects('/reset-password/check-email');
        self::assertEmailCount(1);

        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'from', 'no-reply@dogs-diary.test');
        self::assertEmailAddressContains($message, 'to', self::EMAIL);
        self::assertEmailSubjectContains($message, $message instanceof TemplatedEmail && 'uk' === $message->getLocale() ? 'Відновлення пароля Dogs Diary' : 'Reset your Dogs Diary password');

        $html = $message->getHtmlBody();
        self::assertIsString($html);
        self::assertMatchesRegularExpression('#http://localhost/reset-password/[A-Za-z0-9]{40}#', $html);
        preg_match('#http://localhost/reset-password/([A-Za-z0-9]{40})#', $html, $matches);

        return $matches[1];
    }
}
