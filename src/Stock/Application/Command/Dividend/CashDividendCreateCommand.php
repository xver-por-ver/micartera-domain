<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Dividend;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPerShareVO;
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

    public function invoke(
        string $stockCode,
        \DateTime $datetimeutc,
        Number $dividendPerShareValue,
        string $accountIdentifier,
        ?Number $expensesValue = null
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
            new CashDividendPerShareVO($dividendPerShareValue, $account->getCurrency()),
            new CashDividendMoneyVO($expensesValue ?? new Number('0'), $account->getCurrency())
        );
    }
}
