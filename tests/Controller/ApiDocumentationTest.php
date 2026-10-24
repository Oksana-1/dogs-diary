<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Fixtures\DevelopmentApiDocsKernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

final class ApiDocumentationTest extends WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        if ('dev' === ($options['environment'] ?? null)) {
            return new DevelopmentApiDocsKernel('dev', false);
        }

        return parent::createKernel($options);
    }

    #[DataProvider('environmentProvider')]
    public function testDocumentationRoutesOnlyExistInDevelopment(string $environment, bool $available): void
    {
        $kernel = self::bootKernel(['environment' => $environment, 'debug' => false]);
        $routes = $kernel->getContainer()->get('router')->getRouteCollection();

        self::assertSame($available, null !== $routes->get('app_dev_api_docs'));
        self::assertSame($available, null !== $routes->get('app_dev_api_spec'));
    }

    public static function environmentProvider(): iterable
    {
        yield 'development' => ['dev', true];
        yield 'ordinary tests' => ['test', false];
        yield 'production and preview' => ['prod', false];
    }

    public function testSpecificationCoversAllDiaryOperations(): void
    {
        self::bootKernel();
        $specification = Yaml::parseFile(dirname(__DIR__, 2).'/docs/adr/tooling/backend/openapi.yaml');
        $routes = self::getContainer()->get(RouterInterface::class)->getRouteCollection();
        $actual = [];
        foreach ($routes as $route) {
            if (!str_starts_with($route->getPath(), '/api/dogs')) {
                continue;
            }
            foreach ($route->getMethods() as $method) {
                $actual[] = $method.' '.$route->getPath();
            }
        }

        $documented = [];
        $operationIds = [];
        foreach ($specification['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'], true)) {
                    continue;
                }
                $documented[] = strtoupper($method).' '.$path;
                $operationIds[] = $operation['operationId'];
                if (in_array($method, ['post', 'put', 'patch', 'delete'], true)) {
                    self::assertSame([['browserSession' => [], 'csrfToken' => []]], $operation['security']);
                }
            }
        }

        sort($actual);
        sort($documented);
        self::assertSame($actual, $documented);
        self::assertCount(count($operationIds), array_unique($operationIds));
        self::assertSame([['url' => '/', 'description' => 'Current local application']], $specification['servers']);
    }

    public function testDevelopmentPageUsesSessionCsrfForTryItOut(): void
    {
        $client = self::createClient(['environment' => 'dev']);
        $client->disableReboot();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $user = (new User())->setName('API reader')->setEmail('api-reader@example.test')->setPassword('test-hash');
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/api/docs');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'Dogs Diary API');
        self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('private'));
        $token = $crawler->filter('meta[name="csrf-token"]')->attr('content');
        self::assertNotSame('', $token);

        $client->request('GET', '/api/docs/openapi.json');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        $specification = json_decode($client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('3.0.3', $specification['openapi']);
        self::assertArrayHasKey('/api/dogs', $specification['paths']);

        $client->jsonRequest('POST', '/api/dogs', ['name' => 'Luna', 'birthDate' => '2020-06-15']);
        self::assertResponseStatusCodeSame(403);
        $client->setServerParameter('HTTP_X_CSRF_TOKEN', $token);
        $client->jsonRequest('POST', '/api/dogs', ['name' => 'Luna', 'birthDate' => '2020-06-15']);
        self::assertResponseStatusCodeSame(201);
    }

    public function testDevelopmentDocumentationUsesExistingLoginRequirement(): void
    {
        $client = self::createClient(['environment' => 'dev']);
        $client->request('GET', '/api/docs');

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('content-type', 'application/json');
    }
}
