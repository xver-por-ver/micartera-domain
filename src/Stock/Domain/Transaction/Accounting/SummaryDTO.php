<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @psalm-api
 */
class SummaryDTO
{
    public function __construct(
        public readonly MovementPriceVO $acquisitionsPrice,
        public readonly TransactionExpenseVO $acquisitionsExpenses,
        public readonly MovementPriceVO $liquidationsPrice,
        public readonly TransactionExpenseVO $liquidationsExpenses
    ) {}
}
