<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260620120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase cash dividend per share precision to 10 decimal places';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockCashDividend MODIFY dividend_per_share_value NUMERIC(16, 10) UNSIGNED NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockCashDividend MODIFY dividend_per_share_value NUMERIC(16, 4) UNSIGNED NOT NULL');
    }
}
