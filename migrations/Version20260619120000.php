<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cash dividend records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE stockCashDividend (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', datetimeutc DATETIME NOT NULL, amount NUMERIC(18, 9) UNSIGNED NOT NULL, dividend_per_share_value NUMERIC(16, 4) UNSIGNED NOT NULL, total_dividend_payment_value NUMERIC(16, 4) UNSIGNED NOT NULL, expenses_value NUMERIC(16, 4) UNSIGNED NOT NULL, stock_code VARCHAR(4) NOT NULL, account_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_CASH_DIVIDEND_STOCK (stock_code), INDEX IDX_CASH_DIVIDEND_ACCOUNT (account_id), UNIQUE INDEX UNIQ_CASH_DIVIDEND_ACCOUNT_STOCK_DATETIME (account_id, stock_code, datetimeutc), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE stockCashDividend ADD CONSTRAINT FK_CASH_DIVIDEND_STOCK FOREIGN KEY (stock_code) REFERENCES stock (code)');
        $this->addSql('ALTER TABLE stockCashDividend ADD CONSTRAINT FK_CASH_DIVIDEND_ACCOUNT FOREIGN KEY (account_id) REFERENCES account (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stockCashDividend DROP FOREIGN KEY FK_CASH_DIVIDEND_STOCK');
        $this->addSql('ALTER TABLE stockCashDividend DROP FOREIGN KEY FK_CASH_DIVIDEND_ACCOUNT');
        $this->addSql('DROP TABLE stockCashDividend');
    }
}
