<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;

/**
 * @psalm-api
 */
class MovementPriceVO extends MoneyVO
{
    /** @var numeric-string */
    protected string $valueMin = '0';
    protected int $maxDecimals = 4;
    protected string $numberPropertyName = 'movement';
}
