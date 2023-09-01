<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220114165122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_497B315EF85E0677 ON utilisateurs (username)');
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_497B315EE7927C74 ON utilisateurs (email)');
        $this->addSql('ALTER TABLE utilisateurs ALTER username_canonical DROP NOT NULL');
        $this->addSql('ALTER TABLE utilisateurs ALTER email_canonical DROP NOT NULL');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_497B315EF85E0677');
        $this->addSql('DROP INDEX UNIQ_497B315EE7927C74');
        $this->addSql('ALTER TABLE utilisateurs ALTER username_canonical SET NOT NULL');
        $this->addSql('ALTER TABLE utilisateurs ALTER email_canonical SET NOT NULL');

    }
}
