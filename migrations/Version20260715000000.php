<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link treatments to dogs with a foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatment ADD CONSTRAINT FK_99297E8D5A8A6C8D FOREIGN KEY (dog_id) REFERENCES dog (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_99297E8D5A8A6C8D ON treatment (dog_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatment DROP CONSTRAINT FK_99297E8D5A8A6C8D');
        $this->addSql('DROP INDEX IDX_99297E8D5A8A6C8D');
    }
}
