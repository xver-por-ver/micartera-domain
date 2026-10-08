<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/**
 * @psalm-api
 */
class TransactionExpenseVO extends MoneyVO
{
    /** @var numeric-string */
    protected string $valueMin = '0';
    protected string $numberPropertyName = 'expense';

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value, Currency $currency)
    {
        $this->maxDecimals = $currency->getDecimals();
        parent::__construct($value, $currency);
    }
}
