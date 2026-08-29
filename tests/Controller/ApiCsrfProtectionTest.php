<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Dog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class ApiCsrfProtectionTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($this->entityManager))->createSchema(
            $this->entityManager->getMetadataFactory()->getAllMetadata(),
        );

        $user = (new User())
            ->setName('Alice')
            ->setEmail('alice@example.com')
            ->setPassword('test-password-hash');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->disableReboot();
    }

    public function testAuthenticatedPageExposesApiCsrfTokenAndValidTokenAllowsMutation(): void
    {
        $crawler = $this->client->request('GET', '/');
        $token = $crawler->filter('meta[name="csrf-token"]')->attr('content');
        self::assertNotSame('', $token);

        $this->client->setServerParameter('HTTP_X_CSRF_TOKEN', $token);
        $this->client->jsonRequest('POST', '/api/dogs', $this->dogPayload());

        self::assertResponseStatusCodeSame(201);
        self::assertSame(1, $this->entityManager->getRepository(Dog::class)->count([]));
    }

    public function testEveryApiMutationRejectsMissingCsrfToken(): void
    {
        foreach ($this->mutationRequests() as $route => [$method, $path]) {
            $this->client->request($method, $path);
            $this->assertInvalidCsrfResponse($route.': '.$method.' '.$path);
        }
    }

    public function testEveryApiMutationRejectsInvalidCsrfToken(): void
    {
        $this->client->setServerParameter('HTTP_X_CSRF_TOKEN', 'invalid');

        foreach ($this->mutationRequests() as $route => [$method, $path]) {
            $this->client->request($method, $path);
            $this->assertInvalidCsrfResponse($route.': '.$method.' '.$path);
        }
    }

    public function testCsrfMatrixCoversEveryCurrentMutatingApiRoute(): void
    {
        $coveredRoutes = array_keys(iterator_to_array($this->mutationRequests()));
        $expectedRoutes = [];

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        foreach ($router->getRouteCollection() as $name => $route) {
            if (!str_starts_with($route->getPath(), '/api/')) {
                continue;
            }

            if ([] !== array_intersect($route->getMethods(), [
                Request::METHOD_POST,
                Request::METHOD_PUT,
                Request::METHOD_PATCH,
                Request::METHOD_DELETE,
            ])) {
                $expectedRoutes[] = $name;
            }
        }

        sort($coveredRoutes);
        sort($expectedRoutes);

        self::assertSame($expectedRoutes, $coveredRoutes);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    private function mutationRequests(): iterable
    {
        yield 'app_api_dogapi_createitem' => ['POST', '/api/dogs'];
        yield 'app_api_dogapi_updateitem' => ['PUT', '/api/dogs/1'];
        yield 'app_api_dogapi_deleteitem' => ['DELETE', '/api/dogs/1'];
        yield 'app_api_treatmentapi_createitem' => ['POST', '/api/dogs/1/treatments'];
        yield 'app_api_treatmentapi_updateitem' => ['PUT', '/api/dogs/1/treatments/1'];
        yield 'app_api_treatmentapi_deleteitem' => ['DELETE', '/api/dogs/1/treatments/1'];
        yield 'app_api_dogmediaapi_upload' => ['POST', '/api/dogs/1/media'];
        yield 'app_api_dogmediaapi_delete' => ['DELETE', '/api/dogs/1/media/1'];
        yield 'app_api_dogmediaapi_selectthumbnail' => ['PUT', '/api/dogs/1/media/thumbnail'];
        yield 'app_api_dogmediaapi_clearthumbnail' => ['DELETE', '/api/dogs/1/media/thumbnail'];
        yield 'app_api_dogmediaapi_selectprofile' => ['PUT', '/api/dogs/1/media/profile'];
        yield 'app_api_dogmediaapi_clearprofile' => ['DELETE', '/api/dogs/1/media/profile'];
        yield 'app_api_treatmentmediaapi_upload' => ['POST', '/api/dogs/1/treatments/1/media'];
        yield 'app_api_treatmentmediaapi_delete' => ['DELETE', '/api/dogs/1/treatments/1/media/1'];
    }

    /**
     * @return array<string, mixed>
     */
    private function dogPayload(): array
    {
        return [
            'name' => 'Protected Dog',
            'birthDate' => '2020-01-01',
            'gender' => null,
            'adoptDate' => null,
            'status' => null,
            'weight' => null,
            'height' => null,
        ];
    }

    private function assertInvalidCsrfResponse(string $request): void
    {
        self::assertResponseStatusCodeSame(403, $request);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame([
            'error' => [
                'code' => 'invalid_csrf_token',
                'message' => 'The CSRF token is missing or invalid.',
            ],
        ], json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        ), $request);
    }
}
