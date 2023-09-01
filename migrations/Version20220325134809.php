<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325134809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateurs ADD  registered_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateurs ADD cover_picture VARCHAR(180) DEFAULT NULL');


    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateurs DROP registered_at');
        $this->addSql('ALTER TABLE utilisateurs DROP cover_picture');
    }
}
