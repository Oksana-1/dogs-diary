<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($entityManager))->createSchema($metadata);
        $this->client->disableReboot();
    }

    public function testSignUpPageContainsAStandardPostForm(): void
    {
        $crawler = $this->client->request('GET', '/sign-up');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/sign-up"][method="post"]');
        self::assertNotSame('', $crawler->filter('input[name="_csrf_token"]')->attr('value'));
    }

    public function testRegistrationValidatesInputAndPreservesSafeValues(): void
    {
        $this->client->request('POST', '/sign-up', [
            'name' => 'J',
            'email' => '  INVALID@  ',
            'password' => 'short',
            'password_confirmation' => 'different',
            '_csrf_token' => 'invalid',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.auth-errors', 'form expired');
        self::assertSelectorExists('input[name="name"][value="J"]');
        self::assertSelectorExists('input[name="email"][value="invalid@"]');
        self::assertSelectorExists('#sign-up-email-error');
        self::assertSelectorExists('#sign-up-password-error');
        self::assertSelectorExists('#sign-up-password-confirmation-error');
        self::assertSelectorExists('input[name="terms"][aria-invalid="true"][aria-describedby="sign-up-terms-error"]');
        self::assertSelectorExists('#sign-up-terms-error');
    }

    public function testItRegistersAndAuthenticatesANewUser(): void
    {
        $crawler = $this->client->request('GET', '/sign-up');
        $form = $crawler->selectButton('Create account')->form([
            'name' => '  Jane Doe  ',
            'email' => '  JANE.DOE@Example.COM  ',
            'password' => 'correct horse battery staple',
            'password_confirmation' => 'correct horse battery staple',
            'terms' => '1',
        ]);

        $this->client->submit($form);

        self::assertResponseRedirects('/');

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $user = $users->loadUserByIdentifier('jane.doe@example.com');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('Jane Doe', $user->getName());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNotSame('correct horse battery staple', $user->getPassword());
        self::assertSame(
            'jane.doe@example.com',
            self::getContainer()->get('security.token_storage')->getToken()?->getUserIdentifier(),
        );

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($user, 'correct horse battery staple'));
    }

    public function testItRejectsAnExistingNormalizedEmail(): void
    {
        $existingUser = (new User())
            ->setName('Existing User')
            ->setEmail('existing@example.com')
            ->setPassword('irrelevant-test-hash');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($existingUser);
        $entityManager->flush();

        $crawler = $this->client->request('GET', '/sign-up');
        $form = $crawler->selectButton('Create account')->form([
            'name' => 'Another User',
            'email' => ' EXISTING@EXAMPLE.COM ',
            'password' => 'correct horse battery staple',
            'password_confirmation' => 'correct horse battery staple',
            'terms' => '1',
        ]);

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#sign-up-email-error', 'already exists');
        self::assertSame(1, $entityManager->getRepository(User::class)->count([]));
    }
}
