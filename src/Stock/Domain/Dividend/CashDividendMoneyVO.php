<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/** @psalm-api */
final class CashDividendMoneyVO extends MoneyVO
{
    /** @psalm-param numeric-string $value */
    public function __construct(string $value, Currency $currency)
    {
        $this->maxDecimals = $currency->getDecimals();
        parent::__construct($value, $currency);
    }
}
