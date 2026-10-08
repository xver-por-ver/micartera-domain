<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @psalm-api
 */
class LiquidationCreateCommand
{
    public function __construct(
        private LiquidationPersistenceInterface $liquidationPersistence,
        private AcquisitionPersistenceInterface $acquisitionPersistence,
        private MovementPersistenceInterface $movementPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private StockPersistenceInterface $stockPersistence
    ) {}

    /**
     * @psalm-param numeric-string $amount
     * @psalm-param numeric-string $priceValue
     * @psalm-param numeric-string $expensesValue
     */
    public function invoke(
        string $stockCode,
        \DateTime $datetimeutc,
        string $amount,
        string $priceValue,
        string $expensesValue,
        string $accountIdentifier
    ): Liquidation {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $stock = $this->stockPersistence->getRepository()->findByIdOrThrowException($stockCode);

        return new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $stock,
            new StockPriceVO(
                $priceValue,
                $account->getCurrency()
            ),
            $datetimeutc,
            new TransactionAmountVO($amount),
            new TransactionExpenseVO(
                $expensesValue,
                $account->getCurrency()
            ),
            $account
        );
    }
}
