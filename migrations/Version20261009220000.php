<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261009220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE doctrine_migration_versions SET version = \'DoctrineMigrations\\Version20261009220000\' WHERE version = \'DoctrineMigrations\\VersionInitial\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE doctrine_migration_versions SET version = \'DoctrineMigrations\\Version20261009220000\' WHERE version = \'DoctrineMigrations\\VersionInitial\'');
    }
}
