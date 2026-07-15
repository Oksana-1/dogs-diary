<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add treatment product and due date, and remove audit timestamps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatment RENAME COLUMN date TO treatment_date');
        $this->addSql("ALTER TABLE treatment ADD product_name VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE treatment ADD due_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE treatment DROP created_at');
        $this->addSql('ALTER TABLE treatment DROP updated_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE treatment RENAME COLUMN treatment_date TO date');
        $this->addSql('ALTER TABLE treatment DROP product_name');
        $this->addSql('ALTER TABLE treatment DROP due_date');
        $this->addSql('ALTER TABLE treatment ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE treatment ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
    }
}
