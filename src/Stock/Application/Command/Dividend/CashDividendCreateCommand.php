<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Dividend;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;

/** @psalm-api */
class CashDividendCreateCommand
{
    public function __construct(
        private CashDividendPersistenceInterface $cashDividendPersistence,
        private AcquisitionPersistenceInterface $acquisitionPersistence,
        private LiquidationPersistenceInterface $liquidationPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private StockPersistenceInterface $stockPersistence
    ) {}

    /**
     * @psalm-param numeric-string $dividendPerShareValue
     * @psalm-param numeric-string $expensesValue
     */
    public function invoke(
        string $stockCode,
        \DateTime $datetimeutc,
        string $dividendPerShareValue,
        string $accountIdentifier,
        string $expensesValue = '0'
    ): CashDividend {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->stockPersistence->getRepository()->findByIdOrThrowException($stockCode);

        return new CashDividend(
            $this->cashDividendPersistence,
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $stock,
            $account,
            $datetimeutc,
            new CashDividendMoneyVO($dividendPerShareValue, $account->getCurrency()),
            new CashDividendMoneyVO($expensesValue, $account->getCurrency())
        );
    }
}
