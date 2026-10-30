<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Dog;
use App\Entity\Treatment;
use App\Entity\User;
use App\Enum\TreatmentTypeEnum;
use App\Localization\LocalePolicy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LanguageTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $this->client->disableReboot();
    }

    public function testPublicSwitchPersistsAcrossNavigationAndReloadAndSharesTheVueCatalogue(): void
    {
        $crawler = $this->client->request('GET', '/login?reason=session_expired');
        $this->client->submit($crawler->filter('form[action="/language"]')->form(['locale' => 'uk']));
        self::assertResponseRedirects('/login?reason=session_expired', 303);
        $cookie = $this->client->getCookieJar()->get(LocalePolicy::COOKIE);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('lax', $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertGreaterThan(time() + LocalePolicy::COOKIE_LIFETIME - 60, $cookie->getExpiresTime());
        self::assertLessThanOrEqual(time() + LocalePolicy::COOKIE_LIFETIME, $cookie->getExpiresTime());

        foreach (['/login?reason=session_expired', '/sign-up', '/reset-password', '/reset-password/check-email', '/login'] as $path) {
            $crawler = $this->client->request('GET', $path, server: ['HTTP_ACCEPT_LANGUAGE' => 'en']);
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Language', 'uk');
            self::assertContains('Cookie', $this->client->getResponse()->getVary());
            self::assertContains('Accept-Language', $this->client->getResponse()->getVary());
            self::assertSelectorExists('html[lang="uk"]');
            self::assertSelectorExists('select[name="locale"] option[value="uk"][selected]');
            self::assertSelectorExists('select[name="locale"][aria-label="Мова"]');
            self::assertSelectorTextSame('select[name="locale"] option[value="en"]', 'АНГЛ');
            self::assertSelectorTextSame('select[name="locale"] option[value="uk"]', 'УКР');
            self::assertSelectorNotExists('.language-selector button, .language-selector label');
            $context = json_decode($crawler->filter('meta[name="diary-i18n"]')->attr('content'), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('uk', $context['locale']);
            self::assertSame('en', $context['fallbackLocale']);
            self::assertSame('Мова', $context['messages']['uk']['language.label']);
        }
        self::assertSelectorTextSame('#login-title', $context['messages']['uk']['auth.login_title']);

        $this->client->request('GET', '/api/dogs');
        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('Content-Language', 'uk');
    }

    public function testPreferenceSurvivesAuthenticationChangesAndCanSwitchBackToEnglish(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = (new User())->setName('Locale owner')->setEmail('locale@example.com')->setPassword('unused');
        $em->persist($user);
        $em->flush();

        $crawler = $this->client->request('GET', '/login');
        $this->client->submit($crawler->filter('form[action="/language"]')->form(['locale' => 'uk']));
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/');
        self::assertSelectorExists('html[lang="uk"]');

        $this->client->submit($crawler->filter('form[action="/logout"]')->form());
        self::assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        self::assertSelectorExists('html[lang="uk"]');

        $this->client->submit($crawler->filter('form[action="/language"]')->form(['locale' => 'en']));
        self::assertResponseRedirects('/login', 303);
        $this->client->followRedirect();
        self::assertSelectorExists('html[lang="en"]');
        self::assertSelectorTextSame('#login-title', 'Login to Dogs Diary');
        $this->client->loginUser($user);
        $this->client->request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'uk']);
        self::assertSelectorExists('html[lang="en"]');
        self::assertSame('en', $this->client->getCookieJar()->get(LocalePolicy::COOKIE)?->getValue());
    }

    public function testBrowserDetectionAndInvalidStoredLocaleUseTheDocumentedFallbacks(): void
    {
        $this->client->request('GET', '/login', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr,uk-UA;q=0.9,en;q=0.5']);
        self::assertSelectorExists('html[lang="uk"]');
        self::assertNull($this->client->getCookieJar()->get(LocalePolicy::COOKIE));
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'unsupported'));
        $this->client->request('GET', '/login', server: ['HTTP_ACCEPT_LANGUAGE' => 'uk']);
        self::assertSelectorExists('html[lang="en"]');
    }

    public function testUkrainianLoginShowsSessionExpiryAndGenericAuthenticationFailure(): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'uk'));
        $crawler = $this->client->request('GET', '/login?reason=session_expired');
        self::assertSelectorTextSame('[role="status"]', 'Термін дії сеансу закінчився. Увійдіть знову.');
        $this->client->submit($crawler->selectButton('Увійти')->form([
            'email' => ' UNKNOWN@EXAMPLE.TEST ', 'password' => 'incorrect-password',
        ]));
        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextSame('[role="alert"]', 'Неправильна електронна адреса або пароль.');
        self::assertSelectorExists('input[name="email"][value="unknown@example.test"]');
        self::assertSelectorExists('input[name="password"]:not([value])');
        self::assertNull(self::getContainer()->get('security.token_storage')->getToken()?->getUser());
    }

    public function testInvalidCsrfAndUnsupportedLocaleLeavePreferenceUntouched(): void
    {
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'en'));
        $this->client->request('POST', '/language', ['locale' => 'uk', '_csrf_token' => 'invalid']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame('en', $this->client->getCookieJar()->get(LocalePolicy::COOKIE)?->getValue());

        $crawler = $this->client->request('GET', '/login');
        $token = $crawler->filter('form[action="/language"] input[name="_csrf_token"]')->attr('value');
        $this->client->request('POST', '/language', ['locale' => 'fr', '_csrf_token' => $token]);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('en', $this->client->getCookieJar()->get(LocalePolicy::COOKIE)?->getValue());
    }

    public function testUnsafeRedirectsStayInTheApplicationAndHttpsCookieIsSecure(): void
    {
        foreach (['https://example.com', '//example.com', '/\\example.com', "/\texample.com"] as $returnTo) {
            $crawler = $this->client->request('GET', 'https://localhost/login');
            $token = $crawler->filter('form[action="/language"] input[name="_csrf_token"]')->attr('value');
            $this->client->request('POST', 'https://localhost/language', ['locale' => 'uk', 'returnTo' => $returnTo, '_csrf_token' => $token]);
            self::assertResponseRedirects('/', 303);
            self::assertTrue($this->client->getCookieJar()->get(LocalePolicy::COOKIE)?->isSecure());
        }
    }

    public function testAuthenticatedSwitchUsesTheSamePreference(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = (new User())->setName('Locale owner')->setEmail('locale@example.com')->setPassword('unused');
        $em->persist($user);
        $em->flush();
        $this->client->loginUser($user);
        $crawler = $this->client->request('GET', '/');
        $this->client->submit($crawler->filter('form[action="/language"]')->form(['locale' => 'uk']));
        self::assertResponseRedirects('/', 303);
        $this->client->followRedirect();
        self::assertSelectorExists('html[lang="uk"]');
        self::assertSelectorTextContains('.header-user', 'Locale owner');
        $this->client->request('GET', '/api/dogs');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Language', 'uk');
    }

    public function testSymfonyAndVueBootstrapShareTranslatedMessages(): void
    {
        $this->client->request('GET', '/login');
        $translator = self::getContainer()->get(TranslatorInterface::class);
        self::assertInstanceOf(TranslatorBagInterface::class, $translator);
        $english = 'Keep every walk, treatment, and happy memory close at hand.';
        self::assertSame('Зберігайте спогади про прогулянки, процедури та щасливі миті поруч.', $translator->trans('auth.login_intro', locale: 'uk'));
        $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, 'uk'));
        $crawler = $this->client->request('GET', '/login');
        $context = json_decode($crawler->filter('meta[name="diary-i18n"]')->attr('content'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($english, $context['messages']['en']['auth.login_intro']);
    }

    public function testLanguageChangesPreserveApiDatesAndOwnerIsolation(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $owner = (new User())->setName('Locale owner')->setEmail('owner@example.test')->setPassword('unused');
        $otherOwner = (new User())->setName('Other owner')->setEmail('other@example.test')->setPassword('unused');
        $dog = (new Dog())->setName('Leap dog')->addOwner($owner)
            ->setBusinessDates(new \DateTimeImmutable('2024-02-29'), new \DateTimeImmutable('2025-01-01'));
        $foreignDog = (new Dog())->setName('Foreign dog')->addOwner($otherOwner)
            ->setBirthDate(new \DateTimeImmutable('2024-01-01'));
        $treatment = (new Treatment())->setDog($dog)->setType([TreatmentTypeEnum::FLEA_TICK])
            ->setProductName('Test treatment')->setBusinessDates(new \DateTime('2024-12-31'), new \DateTime('2025-01-01'));
        foreach ([$owner, $otherOwner, $dog, $foreignDog, $treatment] as $entity) {
            $em->persist($entity);
        }
        $em->flush();
        $this->client->loginUser($owner);
        $em->clear();

        foreach (['en', 'uk'] as $locale) {
            $this->client->getCookieJar()->set(new Cookie(LocalePolicy::COOKIE, $locale));
            $this->client->request('GET', '/api/dogs/'.$dog->getId());
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Language', $locale);
            self::assertContains('Cookie', $this->client->getResponse()->getVary());
            self::assertContains('Accept-Language', $this->client->getResponse()->getVary());
            $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('2024-02-29', $data['birthDate']);
            self::assertSame('2025-01-01', $data['adoptDate']);
            self::assertSame('2024-12-31', $data['treatments'][0]['treatmentDate']);
            self::assertSame('2025-01-01', $data['treatments'][0]['dueDate']);
            self::assertSame(['flea_tick'], $data['treatments'][0]['types']);

            $this->client->request('GET', '/api/dogs/'.$foreignDog->getId());
            self::assertResponseStatusCodeSame(404);
            self::assertResponseHeaderSame('Content-Language', $locale);
            $error = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
            self::assertSame('resource_not_found', $error['error']['code']);
        }
    }
}
