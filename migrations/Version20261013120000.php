<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist sessions, login rate limits and their locks across application instances';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL is required.');
        $this->addSql('CREATE TABLE sessions (sess_id VARCHAR(128) NOT NULL, sess_data BYTEA NOT NULL, sess_lifetime INT NOT NULL, sess_time INT NOT NULL, PRIMARY KEY(sess_id))');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
        $this->addSql('CREATE TABLE lock_keys (key_id VARCHAR(64) NOT NULL, key_token VARCHAR(44) NOT NULL, key_expiration INT NOT NULL, PRIMARY KEY(key_id))');
        $this->addSql('CREATE TABLE security_rate_limits (id VARCHAR(64) NOT NULL, state TEXT NOT NULL, expires_at INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX security_rate_limits_expiry_idx ON security_rate_limits (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Keep shared security state during rollback; dropping it would invalidate sessions and reset throttling.');
    }
}
