<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725215000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add adopt date to dogs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dog ADD adopt_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dog DROP adopt_date');
    }
}
