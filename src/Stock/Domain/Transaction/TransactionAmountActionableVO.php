<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\MiCartera\Domain\Number\Domain\Number;

/**
 * @psalm-api
 */
class TransactionAmountActionableVO extends Number
{
    /** @var numeric-string */
    protected string $valueMin = '0';

    /** @var numeric-string */
    protected string $valueMax = '999999999.999999999';
    protected int $maxDecimals = 9;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
