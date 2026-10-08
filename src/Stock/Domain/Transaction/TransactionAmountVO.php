<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\MiCartera\Domain\Number\Domain\Number;

/**
 * @psalm-api
 */
class TransactionAmountVO extends Number
{
    /** @var numeric-string */
    final public const string VALUE_MIN = '0.000000001';

    /** @var numeric-string */
    final public const string VALUE_MAX = '999999999.999999999';

    /** @var numeric-string */
    protected string $valueMin = self::VALUE_MIN;

    /** @var numeric-string */
    protected string $valueMax = self::VALUE_MAX;
    protected int $maxDecimals = 9;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}
