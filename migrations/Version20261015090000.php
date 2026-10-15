<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persist expiring owner-scoped media upload tickets and their completion results';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'PostgreSQL is required.');
        $this->addSql('CREATE TABLE media_upload (id VARCHAR(32) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size_bytes BIGINT NOT NULL, expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, completed BOOLEAN DEFAULT false NOT NULL, owner_id INT NOT NULL, dog_id INT NOT NULL, treatment_id INT DEFAULT NULL, dog_media_id INT DEFAULT NULL, treatment_media_id INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_ABC47CC17E3C61F9 ON media_upload (owner_id)');
        $this->addSql('CREATE INDEX IDX_ABC47CC1634DFEB ON media_upload (dog_id)');
        $this->addSql('CREATE INDEX IDX_ABC47CC1471C0366 ON media_upload (treatment_id)');
        $this->addSql('CREATE INDEX IDX_ABC47CC182C06054 ON media_upload (dog_media_id)');
        $this->addSql('CREATE INDEX IDX_ABC47CC17835952C ON media_upload (treatment_media_id)');
        $this->addSql('CREATE INDEX IDX_MEDIA_UPLOAD_EXPIRES ON media_upload (expires_at)');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC17E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC1634DFEB FOREIGN KEY (dog_id) REFERENCES dog (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC1471C0366 FOREIGN KEY (treatment_id) REFERENCES treatment (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC182C06054 FOREIGN KEY (dog_media_id) REFERENCES dog_media (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_upload ADD CONSTRAINT FK_ABC47CC17835952C FOREIGN KEY (treatment_media_id) REFERENCES treatment_media (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Keep completed upload tickets during rollback to prevent replay. Roll back application code without dropping this table.');
    }
}
