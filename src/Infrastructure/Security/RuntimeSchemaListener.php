<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\Lock\Store\DoctrineDbalStore;

/** Include infrastructure tables in schema validation in every environment. */
#[AsDoctrineListener(event: 'postGenerateSchema')]
final class RuntimeSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $connection = $event->getEntityManager()->getConnection();
        $schema = $event->getSchema();
        $schema = (new PdoSessionHandler($connection->getNativeConnection()))->configureSchema($schema);
        $schema = (new DoctrineDbalStore($connection))->configureSchema($schema, static fn () => true);
        if (!$schema->hasTable('security_rate_limits')) {
            $table = $schema->createTable('security_rate_limits');
            $table->addColumn('id', 'string', ['length' => 64]);
            $table->addColumn('state', 'text');
            $table->addColumn('expires_at', 'integer', ['notnull' => false]);
            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
            $table->addIndex(['expires_at'], 'security_rate_limits_expiry_idx');
        }
        if (method_exists($event, 'setSchema')) {
            $event->setSchema($schema);
        }
    }
}
