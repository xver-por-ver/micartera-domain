<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;

/** @psalm-api */
final class CashDividendMoneyVO extends MoneyVO
{
    public function __construct(Number $value, Currency $currency)
    {
        $this->maxDecimals = $currency->getDecimals();
        parent::__construct($value, $currency);
    }
}
