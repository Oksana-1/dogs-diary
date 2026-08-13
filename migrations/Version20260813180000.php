<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Limit each treatment to one photo';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.',
        );

        $duplicateTreatment = $this->connection->fetchOne(
            'SELECT treatment_id FROM treatment_media GROUP BY treatment_id HAVING COUNT(*) > 1 LIMIT 1',
        );
        $this->abortIf(
            false !== $duplicateTreatment,
            'Cannot limit treatment media to one photo while a treatment has multiple media records.',
        );

        $this->addSql('ALTER TABLE treatment_media DROP CONSTRAINT CHK_TREATMENT_MEDIA_POSITION');
        $this->addSql('ALTER TABLE treatment_media ADD CONSTRAINT CHK_TREATMENT_MEDIA_POSITION CHECK (position = 1)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql('ALTER TABLE treatment_media DROP CONSTRAINT CHK_TREATMENT_MEDIA_POSITION');
        $this->addSql('ALTER TABLE treatment_media ADD CONSTRAINT CHK_TREATMENT_MEDIA_POSITION CHECK (position BETWEEN 1 AND 5)');
    }
}
