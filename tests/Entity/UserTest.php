<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Dog;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testItNormalizesItsIdentityAndName(): void
    {
        $user = (new User())
            ->setName('  Jane Doe  ')
            ->setEmail('  JANE.DOE@Example.COM  ');

        self::assertSame('Jane Doe', $user->getName());
        self::assertSame('jane.doe@example.com', $user->getEmail());
        self::assertSame('jane.doe@example.com', $user->getUserIdentifier());
    }

    public function testEveryUserHasTheUserRole(): void
    {
        $user = (new User())->setRoles(['ROLE_EDITOR', 'ROLE_EDITOR']);

        self::assertSame(['ROLE_EDITOR', 'ROLE_USER'], $user->getRoles());
    }

    public function testSerializationDoesNotExposeThePasswordHash(): void
    {
        $passwordHash = '$2y$13$sensitive-password-hash';
        $user = (new User())->setPassword($passwordHash);

        self::assertStringNotContainsString($passwordHash, serialize($user));
    }

    public function testDogOwnershipIsSynchronizedOnBothSides(): void
    {
        $user = new User();
        $dog = new Dog();

        $dog->addOwner($user);

        self::assertTrue($dog->getOwners()->contains($user));
        self::assertTrue($user->getDogs()->contains($dog));

        $user->removeDog($dog);

        self::assertFalse($dog->getOwners()->contains($user));
        self::assertFalse($user->getDogs()->contains($dog));
    }
}
