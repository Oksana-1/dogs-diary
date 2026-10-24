<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginLogoutTest extends WebTestCase
{
    private const PASSWORD = 'correct horse battery staple';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($entityManager))->createSchema($metadata);

        $user = (new User())
            ->setName('Jane Doe')
            ->setEmail('jane@example.com');
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, self::PASSWORD));
        $entityManager->persist($user);
        $entityManager->flush();

        $this->client->disableReboot();
    }

    public function testLoginPageContainsSymfonyFormFieldsAndCsrfToken(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('meta[name="viewport"][content="width=device-width, initial-scale=1"]');
        self::assertSelectorExists('form[action="/login"][method="post"]');
        self::assertSelectorExists('input[name="email"][required]');
        self::assertSelectorExists('input[name="password"][required]');
        self::assertSelectorExists('input[name="remember_me"][type="checkbox"]');
        self::assertNotSame('', $crawler->filter('input[name="_csrf_token"]')->attr('value'));
    }

    public function testLoginExplainsWhenTheFrontendSessionHasExpired(): void
    {
        $this->client->request('GET', '/login?reason=session_expired');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.auth-notice', 'Your session expired. Please log in again.');
    }

    public function testHeaderSharesNavigationAndKeepsUnavailablePagesInactive(): void
    {
        $this->client->request('GET', '/login');
        $crawler = new Crawler(self::getContainer()->get('twig')->render('_header.html.twig'));

        self::assertCount(1, $crawler->filter('nav[aria-label="Main navigation"]'));
        self::assertCount(1, $crawler->filter('button[type="button"][aria-label="Toggle navigation"][aria-controls="header-menu-panel"][aria-expanded="true"][hidden]'));
        self::assertCount(1, $crawler->filter('#header-menu-panel > .header-auth:first-child'));
        self::assertCount(1, $crawler->filter('#header-menu-panel > .header-auth + nav'));
        self::assertSame('Health Diary', $crawler->filter('#header-navigation a[href="/"]')->text());
        self::assertCount(1, $crawler->filter('#header-navigation a'));
        self::assertCount(2, $crawler->filter('#header-navigation [aria-disabled="true"]'));
        self::assertSame(
            ['Gallery Coming soon', 'Walk Map Coming soon'],
            $crawler->filter('#header-navigation [aria-disabled="true"]')->each(
                static fn (Crawler $item): string => $item->text(),
            ),
        );
        self::assertCount(1, $crawler->filter('.header-auth a[href="/login"]'));
        self::assertCount(1, $crawler->filter('.header-auth a[href="/sign-up"]'));
    }

    public function testLoginUsesTheSavedTargetAndCreatesRememberMeCookie(): void
    {
        $this->client->request('GET', '/dog/42');
        self::assertResponseRedirects('/login');

        $crawler = $this->client->followRedirect();
        $this->submitLogin($crawler, self::PASSWORD, true);

        self::assertResponseRedirects('/dog/42');
        self::assertNotNull($this->client->getCookieJar()->get('REMEMBERME'));
        self::assertSame(
            'jane@example.com',
            self::getContainer()->get('security.token_storage')->getToken()?->getUserIdentifier(),
        );
    }

    public function testInvalidCredentialsShowAGenericErrorAndPreserveEmail(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->submitLogin($crawler, 'wrong password');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        self::assertSelectorTextSame('#login-error', 'Invalid email or password.');
        self::assertSelectorExists('input[name="email"][value="jane@example.com"]');
        self::assertSelectorExists('input[name="password"]:not([value])');
    }

    public function testInvalidLoginCsrfTokenIsRejected(): void
    {
        $this->client->request('POST', '/login', [
            'email' => 'jane@example.com',
            'password' => self::PASSWORD,
            '_csrf_token' => 'invalid',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextSame('#login-error', 'Invalid email or password.');
        self::assertNull(self::getContainer()->get('security.token_storage')->getToken()?->getUser());
    }

    public function testAuthenticatedUserIsRedirectedAwayFromLoginAndSignUp(): void
    {
        $this->login();

        $this->client->request('GET', '/login');
        self::assertResponseRedirects('/');

        $this->client->request('GET', '/sign-up');
        self::assertResponseRedirects('/');
    }

    public function testLogoutRequiresCsrfAndEndsTheSession(): void
    {
        $this->login();
        $crawler = $this->client->request('GET', '/');

        self::assertSelectorExists('meta[name="viewport"][content="width=device-width, initial-scale=1"]');
        self::assertSelectorTextContains('.header-user', 'Jane Doe');
        self::assertSelectorNotExists('.header-auth a[href="/login"]');
        self::assertSelectorExists('header nav[aria-label="Main navigation"] a[href="/"]');
        self::assertSelectorExists('.header-logout[action="/logout"][method="post"] input[name="_csrf_token"]');
        self::assertSelectorCount(1, '#header-menu-panel > .header-auth:first-child form[action="/logout"]');

        $this->client->submit($crawler->selectButton('Logout')->form());
        self::assertResponseRedirects('/login');

        $this->client->request('GET', '/');
        self::assertResponseRedirects('/login');
    }

    public function testInvalidLogoutCsrfDoesNotEndTheSession(): void
    {
        $this->login();

        $this->client->request('POST', '/logout', ['_csrf_token' => 'invalid']);
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.header-user', 'Jane Doe');
    }

    private function login(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->submitLogin($crawler, self::PASSWORD);
        self::assertResponseRedirects('/');
    }

    private function submitLogin(Crawler $crawler, string $password, bool $rememberMe = false): void
    {
        $values = [
            'email' => ' JANE@EXAMPLE.COM ',
            'password' => $password,
        ];

        if ($rememberMe) {
            $values['remember_me'] = '1';
        }

        $this->client->submit($crawler->selectButton('Login')->form($values));
    }
}
