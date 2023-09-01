<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220503135005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_v4 (
            id INT NOT NULL, 
            updated_by_id INT NOT NULL, 
            position SMALLINT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            content TEXT NOT NULL, 
            category VARCHAR(255) NOT NULL, 
            visible BOOLEAN NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            tags TEXT DEFAULT NULL, 
            img_url VARCHAR(255) DEFAULT NULL, 
            link_text VARCHAR(255) DEFAULT NULL, 
            link_url VARCHAR(255) DEFAULT NULL, 
            PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D4A5D9F6896DBBDE ON article_v4 (updated_by_id)');
        $this->addSql('COMMENT ON COLUMN article_v4.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN article_v4.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN article_v4.tags IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE article_v4 ADD CONSTRAINT FK_D4A5D9F6896DBBDE FOREIGN KEY (updated_by_id) REFERENCES utilisateurs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE SEQUENCE article_v4_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE article_v4');
    }
}
