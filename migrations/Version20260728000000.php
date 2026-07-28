<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add treatment table with dummy data for today';
    }

    public function up(Schema $schema): void
    {
        // Create treatment table
        $this->addSql(<<<SQL
            CREATE TABLE treatment (
                id SERIAL PRIMARY KEY,
                dog_id INTEGER NOT NULL,
                type VARCHAR(255) NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                treatment_date DATE NOT NULL,
                due_date DATE NULL,
                note VARCHAR(255) NULL,
                CONSTRAINT fk_treatment_dog FOREIGN KEY (dog_id) REFERENCES dog(id) ON DELETE RESTRICT
            )
        SQL);

        // Create index for dog_id
        $this->addSql('CREATE INDEX idx_treatment_dog_id ON treatment(dog_id)');

        // Insert dummy data for today
        $today = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));

        $this->addSql(sprintf(
            "INSERT INTO treatment (dog_id, type, product_name, treatment_date, due_date, note) VALUES (1, 'flea_tick', 'Frontline Plus', '%s', '%s', 'Monthly flea and tick treatment')",
            $today,
            $nextWeek
        ));

        $this->addSql(sprintf(
            "INSERT INTO treatment (dog_id, type, product_name, treatment_date, note) VALUES (1, 'anti_worm', 'Drontal Plus', '%s', 'Quarterly worm treatment')",
            $today
        ));

        $this->addSql(sprintf(
            "INSERT INTO treatment (dog_id, type, product_name, treatment_date, due_date) VALUES (2, 'flea_tick', 'Simparica Trio', '%s', '%s')",
            $today,
            $nextWeek
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE treatment');
    }
}
