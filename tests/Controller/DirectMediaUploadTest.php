<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Application\Media\MediaStorageInterface;
use App\Entity\Dog;
use App\Entity\DogMedia;
use App\Entity\MediaUpload;
use App\Entity\Treatment;
use App\Entity\TreatmentMedia;
use App\Entity\User;
use App\Enum\TreatmentTypeEnum;
use App\Tests\Fixtures\Media\InMemoryDirectUploadStorage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Lock\LockFactory;

final class DirectMediaUploadTest extends WebTestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private InMemoryDirectUploadStorage $storage;
    private Dog $dog;
    private Dog $foreignDog;
    private Treatment $treatment;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->storage = new InMemoryDirectUploadStorage();
        self::getContainer()->set(MediaStorageInterface::class, $this->storage);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $alice = (new User())->setName('Alice')->setEmail('upload-alice@example.test')->setPassword('unused');
        $bob = (new User())->setName('Bob')->setEmail('upload-bob@example.test')->setPassword('unused');
        $this->em->persist($alice);
        $this->em->persist($bob);
        $this->dog = (new Dog())->setName('Alice Dog')->setBirthDate(new \DateTimeImmutable('2020-01-01'))->addOwner($alice);
        $this->foreignDog = (new Dog())->setName('Bob Dog')->setBirthDate(new \DateTimeImmutable('2020-01-01'))->addOwner($bob);
        $this->em->persist($this->dog);
        $this->em->persist($this->foreignDog);
        $this->treatment = (new Treatment())->setDog($this->dog)->setType([TreatmentTypeEnum::FLEA_TICK])
            ->setProductName('Test')->setTreatmentDate(new \DateTime('2026-01-01'));
        $this->em->persist($this->treatment);
        $this->em->flush();
        $this->client->loginUser($alice);
        $crawler = $this->client->request('GET', '/');
        $this->client->setServerParameter('HTTP_X_CSRF_TOKEN', $crawler->filter('meta[name="csrf-token"]')->attr('content'));
    }

    public function testDirectUploadValidatesPersistsAndReplaysWithoutAnotherUpload(): void
    {
        $path = $this->dogPath();
        $id = $this->initiate($path);
        $this->storage->staging[$id] = base64_decode(self::PNG);
        $this->client->jsonRequest('POST', $path.'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(201);
        $first = $this->response();
        self::assertCount(1, $this->storage->objects);
        $this->storage->staging[$id] = 'overwritten untrusted bytes';
        $this->client->jsonRequest('POST', $path.'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(201);
        self::assertSame($first['id'], $this->response()['id']);
        self::assertSame(1, $this->storage->downloads);
        self::assertSame(1, $this->em->getRepository(DogMedia::class)->count([]));
        self::assertSame(base64_decode(self::PNG), array_values($this->storage->objects)[0]);
        $this->client->request('GET', $first['url']);
        self::assertResponseStatusCodeSame(307);
        self::assertStringContainsString('no-store', $this->client->getResponse()->headers->get('Cache-Control'));
        $this->client->request('DELETE', $path.'/'.$first['id']);
        self::assertResponseStatusCodeSame(204);
        self::assertCount(1, $this->storage->retained);
        $this->em->clear();
        $this->client->jsonRequest('POST', $path.'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(410);
        self::assertSame(0, $this->em->getRepository(DogMedia::class)->count([]));
    }

    public function testForeignDogsAndTicketsCannotBeUsed(): void
    {
        $this->client->jsonRequest('POST', '/api/dogs/'.$this->foreignDog->getId().'/media/uploads', $this->payload());
        self::assertResponseStatusCodeSame(404);
        $id = $this->initiate($this->dogPath());
        $this->em->getConnection()->executeStatement('UPDATE media_upload SET owner_id = (SELECT id FROM app_user WHERE email = ?)', ['upload-bob@example.test']);
        $this->em->clear();
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(404);
        self::assertSame(0, $this->storage->downloads);
    }

    public function testExpiredAndCorruptUploadsDoNotCreateMedia(): void
    {
        $id = $this->initiate($this->dogPath());
        $this->em->getConnection()->executeStatement('UPDATE media_upload SET expires_at = ?', ['2000-01-01 00:00:00']);
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(410);
        $id = $this->initiate($this->dogPath());
        $this->storage->staging[$id] = 'invalid image';
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(415);
        self::assertSame(0, $this->em->getRepository(DogMedia::class)->count([]));
        self::assertCount(0, $this->storage->objects);
    }

    public function testTreatmentReplacementRetainsThePreviousImageAndRejectsVideos(): void
    {
        $path = $this->dogPath(true);
        $this->client->jsonRequest('POST', $path.'/uploads', [...$this->payload(), 'mimeType' => 'video/mp4']);
        self::assertResponseStatusCodeSame(415);
        $results = [];
        for ($i = 0; $i < 2; ++$i) {
            $id = $this->initiate($path);
            $this->storage->staging[$id] = base64_decode(self::PNG);
            $this->client->jsonRequest('POST', $path.'/uploads/'.$id.'/complete');
            self::assertResponseStatusCodeSame(201);
            $results[] = $this->response()['id'];
        }
        self::assertSame($results[0], $results[1]);
        self::assertSame(1, $this->em->getRepository(TreatmentMedia::class)->count([]));
        self::assertCount(1, $this->storage->objects);
        self::assertCount(1, $this->storage->retained);
    }

    public function testDeclaredImageAndVideoLimitsAreEnforcedBeforeIssuingTickets(): void
    {
        foreach ([['image/png', 10485761], ['video/mp4', 104857601]] as [$mime, $bytes]) {
            $this->client->jsonRequest('POST', $this->dogPath().'/uploads', [...$this->payload(), 'mimeType' => $mime, 'sizeBytes' => $bytes]);
            self::assertResponseStatusCodeSame(413);
        }
        self::assertSame(0, $this->em->getRepository(MediaUpload::class)->count([]));
    }

    public function testStorageFailureDoesNotCompleteTheTicketAndCanBeRetried(): void
    {
        $id = $this->initiate($this->dogPath());
        $this->storage->staging[$id] = base64_decode(self::PNG);
        $this->storage->failStore = true;
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(500);
        self::assertFalse($this->em->find(MediaUpload::class, $id)->isCompleted());
        self::assertSame(0, $this->em->getRepository(DogMedia::class)->count([]));
        $this->storage->failStore = false;
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
        self::assertResponseStatusCodeSame(201);
    }

    public function testConcurrentFinalizationDoesNotReadOrPublishTheFile(): void
    {
        $id = $this->initiate($this->dogPath());
        $lock = self::getContainer()->get(LockFactory::class)->createLock('media-upload:'.$id, 900);
        self::assertTrue($lock->acquire());
        try {
            $this->client->jsonRequest('POST', $this->dogPath().'/uploads/'.$id.'/complete');
            self::assertResponseStatusCodeSame(409);
            self::assertSame(0, $this->storage->downloads);
            self::assertCount(0, $this->storage->objects);
        } finally {
            $lock->release();
        }
    }

    public function testUploadTicketIssuanceIsRateLimited(): void
    {
        for ($attempt = 0; $attempt < 30; ++$attempt) {
            $this->initiate($this->dogPath());
        }
        $this->client->jsonRequest('POST', $this->dogPath().'/uploads', $this->payload());
        self::assertResponseStatusCodeSame(429);
        self::assertSame(30, $this->em->getRepository(MediaUpload::class)->count([]));
    }

    private function dogPath(bool $treatment = false): string
    {
        return '/api/dogs/'.$this->dog->getId().($treatment ? '/treatments/'.$this->treatment->getId() : '').'/media';
    }

    private function payload(): array
    {
        return ['name' => 'photo.png', 'mimeType' => 'image/png', 'sizeBytes' => strlen(base64_decode(self::PNG))];
    }

    private function initiate(string $path): string
    {
        $this->client->jsonRequest('POST', $path.'/uploads', $this->payload());
        self::assertResponseStatusCodeSame(200);
        self::assertSame('direct', $this->response()['mode']);

        return $this->response()['uploadId'];
    }

    private function response(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
