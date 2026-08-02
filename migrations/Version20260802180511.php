<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802180511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dog ADD gender VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dog ALTER adopt_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE treatment ALTER product_name DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_99297e8d5a8a6c8d RENAME TO IDX_98013C31634DFEB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dog DROP gender');
        $this->addSql('ALTER TABLE dog ALTER adopt_date TYPE DATE');
        $this->addSql('ALTER TABLE treatment ALTER product_name SET DEFAULT \'\'');
        $this->addSql('ALTER INDEX idx_98013c31634dfeb RENAME TO idx_99297e8d5a8a6c8d');
    }
}
