<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/**
 * @psalm-api
 */
class StockPriceVO extends MoneyVO
{
    /** @var numeric-string */
    final public const string VALUE_MIN = '0';

    /** @var numeric-string */
    final public const string VALUE_MAX = '999999.9999';

    /** @var numeric-string */
    protected string $valueMin = self::VALUE_MIN;

    /** @var numeric-string */
    protected string $valueMax = self::VALUE_MAX;
    protected int $maxDecimals = 4;
    protected string $numberPropertyName = 'stock';
}
