<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/**
 * @psalm-api
 */
class StockProfitVO extends MoneyVO
{
    /** @var numeric-string */
    protected string $valueMin = '-9999999999999.9999';

    /** @var numeric-string */
    protected string $valueMax = '9999999999999.9999';
    protected int $maxDecimals = 4;
    protected string $numberPropertyName = 'stockprofit';
}
