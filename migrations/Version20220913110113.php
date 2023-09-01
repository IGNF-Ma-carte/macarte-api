<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220913110113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_v4 ADD status VARCHAR(255) NOT NULL DEFAULT \'draft\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE article_v4 DROP status');
    }
}
