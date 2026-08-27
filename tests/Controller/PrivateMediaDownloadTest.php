<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Application\Media\MediaStorageInterface;
use App\Entity\Dog;
use App\Entity\DogMedia;
use App\Entity\Treatment;
use App\Entity\TreatmentMedia;
use App\Entity\User;
use App\Enum\MediaTypeEnum;
use App\Enum\TreatmentTypeEnum;
use App\Infrastructure\Media\LocalMediaStorage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PrivateMediaDownloadTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $mediaDirectory;
    private User $alice;
    private Dog $aliceDog;
    private Dog $aliceVideoDog;
    private Dog $bobDog;
    private Treatment $aliceTreatment;
    private Treatment $bobTreatment;
    private DogMedia $aliceImage;
    private DogMedia $aliceVideo;
    private DogMedia $bobImage;
    private TreatmentMedia $aliceTreatmentImage;
    private TreatmentMedia $bobTreatmentImage;

    protected function setUp(): void
    {
        $this->mediaDirectory = sys_get_temp_dir().'/dogs-diary-private-media-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->mediaDirectory, 0777, true));

        $this->client = static::createClient();
        self::getContainer()->set(
            MediaStorageInterface::class,
            new LocalMediaStorage($this->mediaDirectory),
        );

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($this->entityManager))->createSchema(
            $this->entityManager->getMetadataFactory()->getAllMetadata(),
        );

        $this->alice = $this->createUser('Alice', 'alice-private-media@example.com');
        $bob = $this->createUser('Bob', 'bob-private-media@example.com');
        $this->aliceDog = $this->createDog('Alice Dog', $this->alice);
        $this->aliceVideoDog = $this->createDog('Alice Video Dog', $this->alice);
        $this->bobDog = $this->createDog('Bob Dog', $bob);
        $this->entityManager->flush();

        $this->aliceTreatment = $this->createTreatment($this->aliceDog, 'Alice Treatment');
        $this->bobTreatment = $this->createTreatment($this->bobDog, 'Bob Treatment');
        $this->entityManager->flush();

        $this->aliceImage = $this->createDogMedia($this->aliceDog, MediaTypeEnum::IMAGE, 'a', 'image/png', 'alice-image');
        $this->aliceVideo = $this->createDogMedia($this->aliceVideoDog, MediaTypeEnum::VIDEO, 'b', 'video/mp4', '0123456789');
        $this->bobImage = $this->createDogMedia($this->bobDog, MediaTypeEnum::IMAGE, 'c', 'image/png', 'bob-image');
        $this->aliceTreatmentImage = $this->createTreatmentMedia($this->aliceTreatment, 'd', 'alice-treatment');
        $this->bobTreatmentImage = $this->createTreatmentMedia($this->bobTreatment, 'e', 'bob-treatment');
        $this->entityManager->flush();

        $this->client->loginUser($this->alice);
        $this->client->disableReboot();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->mediaDirectory);
        parent::tearDown();
    }

    public function testApiUsesAuthorizedRoutesInsteadOfPublicStoragePaths(): void
    {
        $this->client->request('GET', '/api/dogs/'.$this->id($this->aliceDog).'/media');

        self::assertResponseIsSuccessful();
        $dogMedia = $this->jsonResponse();
        self::assertSame(
            '/api/dogs/'.$this->id($this->aliceDog).'/media/'.$this->id($this->aliceImage),
            $dogMedia[0]['url'],
        );
        self::assertStringNotContainsString('/uploads/', $dogMedia[0]['url']);
        self::assertStringNotContainsString($this->aliceImage->getStorageKey(), $dogMedia[0]['url']);

        $this->client->request(
            'GET',
            '/api/dogs/'.$this->id($this->aliceDog).'/treatments/'.$this->id($this->aliceTreatment).'/media',
        );

        self::assertResponseIsSuccessful();
        self::assertSame(
            '/api/dogs/'.$this->id($this->aliceDog)
                .'/treatments/'.$this->id($this->aliceTreatment)
                .'/media/'.$this->id($this->aliceTreatmentImage),
            $this->jsonResponse()[0]['url'],
        );
    }

    public function testOwnerCanDownloadDogAndTreatmentMediaWithPrivateHeaders(): void
    {
        $this->client->request('GET', $this->dogMediaUrl($this->aliceDog, $this->aliceImage));

        self::assertResponseIsSuccessful();
        self::assertSame('alice-image', $this->binaryResponseContent());
        self::assertResponseHeaderSame('content-type', 'image/png');
        self::assertResponseHeaderSame('accept-ranges', 'bytes');
        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
        self::assertStringContainsString('private', (string) $this->client->getResponse()->headers->get('cache-control'));
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('cache-control'));

        $this->client->request('GET', $this->treatmentMediaUrl(
            $this->aliceDog,
            $this->aliceTreatment,
            $this->aliceTreatmentImage,
        ));

        self::assertResponseIsSuccessful();
        self::assertSame('alice-treatment', $this->binaryResponseContent());
    }

    public function testVideoDownloadSupportsByteRanges(): void
    {
        $this->client->request(
            'GET',
            $this->dogMediaUrl($this->aliceVideoDog, $this->aliceVideo),
            server: ['HTTP_RANGE' => 'bytes=2-5'],
        );

        self::assertResponseStatusCodeSame(206);
        self::assertResponseHeaderSame('content-range', 'bytes 2-5/10');
        self::assertResponseHeaderSame('content-length', '4');
        self::assertSame('2345', $this->binaryResponseContent());
    }

    public function testForeignAndMismatchedDogMediaDownloadsReturnNotFound(): void
    {
        $this->client->request('GET', $this->dogMediaUrl($this->bobDog, $this->bobImage));
        $this->assertJsonNotFound();

        $this->client->request('GET', $this->dogMediaUrl($this->aliceDog, $this->bobImage));
        $this->assertJsonNotFound();
    }

    public function testForeignAndMismatchedTreatmentMediaDownloadsReturnNotFound(): void
    {
        $this->client->request('GET', $this->treatmentMediaUrl(
            $this->bobDog,
            $this->bobTreatment,
            $this->bobTreatmentImage,
        ));
        $this->assertJsonNotFound();

        $this->client->request('GET', $this->treatmentMediaUrl(
            $this->aliceDog,
            $this->aliceTreatment,
            $this->bobTreatmentImage,
        ));
        $this->assertJsonNotFound();
    }

    public function testMissingStoredFileReturnsNotFound(): void
    {
        $path = $this->mediaDirectory.'/'.$this->aliceImage->getStorageKey();
        self::assertTrue(unlink($path));

        $this->client->request('GET', $this->dogMediaUrl($this->aliceDog, $this->aliceImage));

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

    private function createDog(string $name, User $owner): Dog
    {
        $dog = (new Dog())
            ->setName($name)
            ->setBirthDate(new \DateTimeImmutable('2020-01-01'))
            ->addOwner($owner);
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

    private function createDogMedia(
        Dog $dog,
        MediaTypeEnum $type,
        string $hex,
        string $mimeType,
        string $contents,
    ): DogMedia {
        $extension = MediaTypeEnum::VIDEO === $type ? 'mp4' : 'png';
        $storageKey = 'dogs/'.$this->id($dog).'/'.str_repeat($hex, 32).'.'.$extension;
        $this->writeStoredFile($storageKey, $contents);
        $media = new DogMedia(
            $dog,
            $type,
            $storageKey,
            'private-media.'.$extension,
            $mimeType,
            strlen($contents),
            MediaTypeEnum::IMAGE === $type ? 1 : null,
            MediaTypeEnum::IMAGE === $type ? 1 : null,
        );
        $this->entityManager->persist($media);

        return $media;
    }

    private function createTreatmentMedia(Treatment $treatment, string $hex, string $contents): TreatmentMedia
    {
        $storageKey = 'treatments/'.$this->id($treatment).'/'.str_repeat($hex, 32).'.png';
        $this->writeStoredFile($storageKey, $contents);
        $media = new TreatmentMedia(
            $treatment,
            $storageKey,
            'private-treatment.png',
            'image/png',
            strlen($contents),
            1,
            1,
            1,
        );
        $this->entityManager->persist($media);

        return $media;
    }

    private function writeStoredFile(string $storageKey, string $contents): void
    {
        $path = $this->mediaDirectory.'/'.$storageKey;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true));
        }
        self::assertSame(strlen($contents), file_put_contents($path, $contents));
    }

    private function dogMediaUrl(Dog $dog, DogMedia $media): string
    {
        return '/api/dogs/'.$this->id($dog).'/media/'.$this->id($media);
    }

    private function treatmentMediaUrl(Dog $dog, Treatment $treatment, TreatmentMedia $media): string
    {
        return '/api/dogs/'.$this->id($dog)
            .'/treatments/'.$this->id($treatment)
            .'/media/'.$this->id($media);
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

    private function binaryResponseContent(): string
    {
        ob_start();
        $this->client->getResponse()->sendContent();
        $content = ob_get_clean();
        self::assertIsString($content);

        return $content;
    }

    private function id(object $entity): int
    {
        $id = $entity->getId();
        self::assertIsInt($id);

        return $id;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}
