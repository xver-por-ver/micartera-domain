<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;

/**
 * @psalm-api
 */
class CashDividendAmountVO extends Number
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

    public function __construct(
        private readonly Number $amountAcquisition,
        private readonly Number $amountLiquidation
    ) {
        parent::__construct(
            $this->calculate()->getValue()
        );
    }

    private function calculate(): Number
    {
        $numberOperation = new NumberOperation();
        $amountAtTs = new Number(
            $numberOperation->subtract(
                $this->amountAcquisition->getMaxDecimals(),
                $this->amountAcquisition,
                $this->amountLiquidation
            )
        );

        if ($numberOperation->smallerOrEqual($this->maxDecimals, $amountAtTs, new Number('0'))) {
            throw new CashDividendAmountException();
        }

        return $amountAtTs;
    }
}
