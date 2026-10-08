<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;

/** Reconciles cash dividends affected by a changed stock position. */
final class CashDividendSynchronizer
{
    public function __construct(
        private readonly CashDividendPersistenceInterface $cashDividendPersistence,
        private readonly AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly LiquidationPersistenceInterface $liquidationPersistence
    ) {}

    public function synchronize(Account $account, Stock $stock, \DateTime $transactionDate): void
    {
        $cashDividends = $this->cashDividendPersistence->getRepository()->findByAccountStockAtOrAfter(
            $account,
            $stock,
            $transactionDate
        );
        if ($cashDividends->isEmpty()) {
            return;
        }

        $hasChanges = false;
        foreach ($cashDividends as $cashDividend) {
            try {
                $cashDividend->setAmountAndTotalDividendPayment(
                    new CashDividendAmountVO(
                        $this->acquisitionPersistence->getRepository()->totalAmountForAccountStockAtOrBefore(
                            $account,
                            $stock,
                            $cashDividend->getDateTimeUtc()
                        ),
                        $this->liquidationPersistence->getRepository()->totalAmountForAccountStockAtOrBefore(
                            $account,
                            $stock,
                            $cashDividend->getDateTimeUtc()
                        )
                    )
                );
            } catch (CashDividendAmountException $th) {
                $this->cashDividendPersistence->remove($cashDividend);
                $hasChanges = true;
                continue;
            }

            $this->cashDividendPersistence->persist($cashDividend);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->cashDividendPersistence->flush();
        }
    }
}
