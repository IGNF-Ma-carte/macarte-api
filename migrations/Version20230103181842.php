<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230103181842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE map_view_tracking (map_id INT NOT NULL, ip VARCHAR(255) NOT NULL, date DATE NOT NULL, PRIMARY KEY(map_id, ip))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE map_view_tracking');
    }
}
