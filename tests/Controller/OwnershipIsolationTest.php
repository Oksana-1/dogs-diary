<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Dog;
use App\Entity\DogMedia;
use App\Entity\Treatment;
use App\Entity\TreatmentMedia;
use App\Entity\User;
use App\Enum\MediaTypeEnum;
use App\Enum\TreatmentTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class OwnershipIsolationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $alice;
    private Dog $aliceDog;
    private Dog $bobDog;
    private Dog $sharedDog;
    private Treatment $aliceTreatment;
    private Treatment $bobTreatment;
    private DogMedia $aliceDogMedia;
    private DogMedia $bobDogMedia;
    private TreatmentMedia $aliceTreatmentMedia;
    private TreatmentMedia $bobTreatmentMedia;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($this->entityManager))->createSchema(
            $this->entityManager->getMetadataFactory()->getAllMetadata(),
        );

        $this->alice = $this->createUser('Alice', 'alice@example.com');
        $bob = $this->createUser('Bob', 'bob@example.com');
        $this->aliceDog = $this->createDog('Alice Dog', $this->alice);
        $this->bobDog = $this->createDog('Bob Dog', $bob);
        $this->sharedDog = $this->createDog('Shared Dog', $this->alice, $bob);
        $this->entityManager->flush();

        $this->aliceTreatment = $this->createTreatment($this->aliceDog, 'Alice Treatment');
        $this->bobTreatment = $this->createTreatment($this->bobDog, 'Bob Treatment');
        $this->entityManager->flush();

        $this->aliceDogMedia = $this->createDogMedia($this->aliceDog, 'a');
        $this->bobDogMedia = $this->createDogMedia($this->bobDog, 'b');
        $this->aliceTreatmentMedia = $this->createTreatmentMedia($this->aliceTreatment, 'c');
        $this->bobTreatmentMedia = $this->createTreatmentMedia($this->bobTreatment, 'd');
        $this->entityManager->flush();

        $this->client->loginUser($this->alice);
        $this->client->disableReboot();

        $crawler = $this->client->request('GET', '/');
        $csrfToken = $crawler->filter('meta[name="csrf-token"]')->attr('content');
        self::assertNotSame('', $csrfToken);
        $this->client->setServerParameter('HTTP_X_CSRF_TOKEN', $csrfToken);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testDogListContainsOnlyOwnedAndSharedDogs(): void
    {
        $this->client->request('GET', '/api/dogs');

        self::assertResponseIsSuccessful();
        $names = array_column($this->jsonResponse(), 'name');
        self::assertSame(['Alice Dog', 'Shared Dog'], $names);

        $this->client->request('GET', '/api/dogs/'.$this->id($this->sharedDog));
        self::assertResponseIsSuccessful();
        self::assertSame('Shared Dog', $this->jsonResponse()['name']);
    }

    public function testForeignDogCannotBeReadFromApiOrWebPage(): void
    {
        $this->client->request('GET', '/api/dogs/'.$this->id($this->bobDog));
        $this->assertJsonNotFound();

        $this->client->request('GET', '/dog/'.$this->id($this->bobDog));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/dog/'.$this->id($this->aliceDog));
        self::assertResponseIsSuccessful();
    }

    public function testCreatedDogBelongsToCurrentUserAndOwnedDogCanBeChanged(): void
    {
        $this->client->jsonRequest('POST', '/api/dogs', $this->dogPayload('New Dog'));

        self::assertResponseStatusCodeSame(201);
        $createdId = $this->jsonResponse()['id'];
        $createdDog = $this->entityManager->find(Dog::class, $createdId);
        self::assertInstanceOf(Dog::class, $createdDog);
        self::assertCount(1, $createdDog->getOwners());
        self::assertSame($this->id($this->alice), $this->id($createdDog->getOwners()->first()));

        $this->client->jsonRequest('PUT', '/api/dogs/'.$createdId, $this->dogPayload('Updated Dog'));
        self::assertResponseIsSuccessful();
        self::assertSame('Updated Dog', $this->jsonResponse()['name']);

        $this->client->request('DELETE', '/api/dogs/'.$createdId);
        self::assertResponseStatusCodeSame(204);
        self::assertNull($this->entityManager->find(Dog::class, $createdId));
    }

    public function testForeignDogCannotBeUpdatedOrDeleted(): void
    {
        $bobDogId = $this->id($this->bobDog);

        $this->client->jsonRequest('PUT', '/api/dogs/'.$bobDogId, $this->dogPayload('Stolen Dog'));
        $this->assertJsonNotFound();
        self::assertSame('Bob Dog', $this->bobDog->getName());

        $this->client->request('DELETE', '/api/dogs/'.$bobDogId);
        $this->assertJsonNotFound();
        self::assertNotNull($this->entityManager->find(Dog::class, $bobDogId));
    }

    public function testTreatmentsRequireTheCompleteOwnedDogRelationship(): void
    {
        $aliceDogId = $this->id($this->aliceDog);
        $bobDogId = $this->id($this->bobDog);
        $bobTreatmentId = $this->id($this->bobTreatment);

        $this->client->request('GET', '/api/dogs/'.$aliceDogId.'/treatments');
        self::assertResponseIsSuccessful();
        self::assertSame(['Alice Treatment'], array_column($this->jsonResponse(), 'productName'));

        $this->client->request('GET', '/api/dogs/'.$bobDogId.'/treatments');
        $this->assertJsonNotFound();

        $this->client->request('GET', '/api/dogs/'.$aliceDogId.'/treatments/'.$bobTreatmentId);
        $this->assertJsonNotFound();

        $this->client->request('GET', '/api/dogs/'.$bobDogId.'/treatments/'.$bobTreatmentId);
        $this->assertJsonNotFound();
    }

    public function testForeignTreatmentCannotBeCreatedUpdatedOrDeleted(): void
    {
        $bobDogId = $this->id($this->bobDog);
        $bobTreatmentId = $this->id($this->bobTreatment);

        $this->client->jsonRequest('POST', '/api/dogs/'.$bobDogId.'/treatments', $this->treatmentPayload());
        $this->assertJsonNotFound();

        $this->client->jsonRequest(
            'PUT',
            '/api/dogs/'.$bobDogId.'/treatments/'.$bobTreatmentId,
            $this->treatmentPayload('Stolen Treatment'),
        );
        $this->assertJsonNotFound();
        self::assertSame('Bob Treatment', $this->bobTreatment->getProductName());

        $this->client->request('DELETE', '/api/dogs/'.$bobDogId.'/treatments/'.$bobTreatmentId);
        $this->assertJsonNotFound();
        self::assertNotNull($this->entityManager->find(Treatment::class, $bobTreatmentId));
    }

    public function testOwnerCanCreateUpdateAndDeleteTreatment(): void
    {
        $dogId = $this->id($this->aliceDog);
        $this->client->jsonRequest('POST', '/api/dogs/'.$dogId.'/treatments', $this->treatmentPayload());

        self::assertResponseStatusCodeSame(201);
        $treatmentId = $this->jsonResponse()['id'];

        $this->client->jsonRequest(
            'PUT',
            '/api/dogs/'.$dogId.'/treatments/'.$treatmentId,
            $this->treatmentPayload('Updated Treatment'),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('Updated Treatment', $this->jsonResponse()['productName']);

        $this->client->request('DELETE', '/api/dogs/'.$dogId.'/treatments/'.$treatmentId);
        self::assertResponseStatusCodeSame(204);
        self::assertNull($this->entityManager->find(Treatment::class, $treatmentId));
    }

    public function testDogMediaCannotBeAccessedThroughForeignOrMismatchedIds(): void
    {
        $aliceDogId = $this->id($this->aliceDog);
        $bobDogId = $this->id($this->bobDog);
        $bobMediaId = $this->id($this->bobDogMedia);

        $this->client->request('GET', '/api/dogs/'.$aliceDogId.'/media');
        self::assertResponseIsSuccessful();
        self::assertSame([$this->id($this->aliceDogMedia)], array_column($this->jsonResponse(), 'id'));

        $this->client->request('GET', '/api/dogs/'.$bobDogId.'/media');
        $this->assertJsonNotFound();

        $this->client->request('DELETE', '/api/dogs/'.$aliceDogId.'/media/'.$bobMediaId);
        $this->assertJsonNotFound();

        $this->client->request('DELETE', '/api/dogs/'.$bobDogId.'/media/'.$bobMediaId);
        $this->assertJsonNotFound();
        self::assertNotNull($this->entityManager->find(DogMedia::class, $bobMediaId));
    }

    public function testForeignDogMediaCannotBeSelectedClearedOrUploaded(): void
    {
        $aliceDogId = $this->id($this->aliceDog);
        $bobDogId = $this->id($this->bobDog);
        $bobMediaId = $this->id($this->bobDogMedia);

        $this->client->jsonRequest(
            'PUT',
            '/api/dogs/'.$aliceDogId.'/media/profile',
            ['mediaId' => $bobMediaId],
        );
        $this->assertJsonNotFound();

        $this->client->jsonRequest(
            'PUT',
            '/api/dogs/'.$bobDogId.'/media/profile',
            ['mediaId' => $bobMediaId],
        );
        $this->assertJsonNotFound();

        $this->client->request('DELETE', '/api/dogs/'.$bobDogId.'/media/profile');
        $this->assertJsonNotFound();

        $this->client->jsonRequest(
            'PUT',
            '/api/dogs/'.$bobDogId.'/media/thumbnail',
            ['mediaId' => $bobMediaId],
        );
        $this->assertJsonNotFound();

        $this->client->request('DELETE', '/api/dogs/'.$bobDogId.'/media/thumbnail');
        $this->assertJsonNotFound();

        $this->client->request(
            'POST',
            '/api/dogs/'.$bobDogId.'/media',
            files: ['file' => $this->uploadedImage()],
        );
        $this->assertJsonNotFound();
    }

    public function testTreatmentMediaRequiresDogTreatmentMediaAndOwnerToMatch(): void
    {
        $aliceDogId = $this->id($this->aliceDog);
        $bobDogId = $this->id($this->bobDog);
        $aliceTreatmentId = $this->id($this->aliceTreatment);
        $bobTreatmentId = $this->id($this->bobTreatment);
        $bobMediaId = $this->id($this->bobTreatmentMedia);

        $this->client->request('GET', '/api/dogs/'.$aliceDogId.'/treatments/'.$aliceTreatmentId.'/media');
        self::assertResponseIsSuccessful();
        self::assertSame([$this->id($this->aliceTreatmentMedia)], array_column($this->jsonResponse(), 'id'));

        $this->client->request('GET', '/api/dogs/'.$bobDogId.'/treatments/'.$bobTreatmentId.'/media');
        $this->assertJsonNotFound();

        $this->client->request(
            'DELETE',
            '/api/dogs/'.$aliceDogId.'/treatments/'.$aliceTreatmentId.'/media/'.$bobMediaId,
        );
        $this->assertJsonNotFound();

        $this->client->request(
            'DELETE',
            '/api/dogs/'.$bobDogId.'/treatments/'.$bobTreatmentId.'/media/'.$bobMediaId,
        );
        $this->assertJsonNotFound();
        self::assertNotNull($this->entityManager->find(TreatmentMedia::class, $bobMediaId));
    }

    public function testForeignTreatmentMediaCannotBeUploaded(): void
    {
        $this->client->request(
            'POST',
            '/api/dogs/'.$this->id($this->bobDog).'/treatments/'.$this->id($this->bobTreatment).'/media',
            files: ['file' => $this->uploadedImage()],
        );

        $this->assertJsonNotFound();
    }

    private function createUser(string $name, string $email): User
    {
        $user = (new User())
            ->setName($name)
            ->setEmail($email)
            ->setPassword('test-password-hash');
        $this->entityManager->persist($user);

        return $user;
    }

    private function createDog(string $name, User ...$owners): Dog
    {
        $dog = (new Dog())
            ->setName($name)
            ->setBirthDate(new \DateTimeImmutable('2020-01-01'));
        foreach ($owners as $owner) {
            $dog->addOwner($owner);
        }
        $this->entityManager->persist($dog);

        return $dog;
    }

    private function createTreatment(Dog $dog, string $productName): Treatment
    {
        $treatment = (new Treatment())
            ->setDog($dog)
            ->setType([TreatmentTypeEnum::FLEA_TICK])
            ->setProductName($productName)
            ->setTreatmentDate(new \DateTime('2026-01-01'));
        $this->entityManager->persist($treatment);

        return $treatment;
    }

    private function createDogMedia(Dog $dog, string $hex): DogMedia
    {
        $media = new DogMedia(
            $dog,
            MediaTypeEnum::IMAGE,
            'dogs/'.$this->id($dog).'/'.str_repeat($hex, 32).'.png',
            'photo.png',
            'image/png',
            68,
            1,
            1,
        );
        $this->entityManager->persist($media);

        return $media;
    }

    private function createTreatmentMedia(Treatment $treatment, string $hex): TreatmentMedia
    {
        $media = new TreatmentMedia(
            $treatment,
            'treatments/'.$this->id($treatment).'/'.str_repeat($hex, 32).'.png',
            'photo.png',
            'image/png',
            68,
            1,
            1,
            1,
        );
        $this->entityManager->persist($media);

        return $media;
    }

    /**
     * @return array<string, mixed>
     */
    private function dogPayload(string $name): array
    {
        return [
            'name' => $name,
            'birthDate' => '2020-01-01',
            'gender' => null,
            'adoptDate' => null,
            'status' => null,
            'weight' => null,
            'height' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function treatmentPayload(string $productName = 'New Treatment'): array
    {
        return [
            'types' => [TreatmentTypeEnum::FLEA_TICK->value],
            'productName' => $productName,
            'treatmentDate' => '2026-02-01',
            'dueDate' => null,
            'note' => null,
        ];
    }

    private function uploadedImage(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'dogs-diary-ownership-');
        if (false === $path) {
            self::fail('Unable to create a temporary upload.');
        }

        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, 'photo.png', 'image/png', null, true);
    }

    /**
     * @return array<mixed>
     */
    private function jsonResponse(): array
    {
        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );
    }

    private function assertJsonNotFound(): void
    {
        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame('resource_not_found', $this->jsonResponse()['error']['code']);
    }

    private function id(object $entity): int
    {
        $id = $entity->getId();
        self::assertIsInt($id);

        return $id;
    }
}
