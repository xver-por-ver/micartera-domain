<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Number\Domain;

final class NumberOperation
{
    public function add(int $decimals, NumberInterface $operand1, NumberInterface $operand2): Number
    {
        return new Number(bcadd($operand1->getValue(), $operand2->getValue(), $decimals));
    }

    public function subtract(int $decimals, NumberInterface $operand1, NumberInterface $operand2): Number
    {
        return new Number(bcsub($operand1->getValue(), $operand2->getValue(), $decimals));
    }

    public function multiply(int $decimals, NumberInterface $operand1, NumberInterface $operand2, \RoundingMode $roundingMode = \RoundingMode::HalfAwayFromZero): Number
    {
        return new Number(bcround(
            bcmul($operand1->getValue(), $operand2->getValue(), $decimals + 1),
            $decimals,
            $roundingMode
        ));
    }

    public function divide(int $decimals, NumberInterface $dividend, NumberInterface $divisor, \RoundingMode $roundingMode = \RoundingMode::HalfAwayFromZero): Number
    {
        return new Number(bcround(
            bcdiv($dividend->getValue(), $divisor->getValue(), $decimals + 1),
            $decimals,
            $roundingMode
        ));
    }

    private function compare(int $decimals, NumberInterface $operand1, NumberInterface $operand2): int
    {
        return bccomp($operand1->getValue(), $operand2->getValue(), $decimals);
    }

    public function same(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 0 === $this->compare($decimals, $operand1, $operand2);
    }

    public function greater(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 1 === $this->compare($decimals, $operand1, $operand2);
    }

    public function greaterOrEqual(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        $result = $this->compare($decimals, $operand1, $operand2);

        return 1 === $result || 0 === $result;
    }

    public function smaller(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return -1 === $this->compare($decimals, $operand1, $operand2);
    }

    public function smallerOrEqual(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        $result = $this->compare($decimals, $operand1, $operand2);

        return -1 === $result || 0 === $result;
    }

    public function different(int $decimals, NumberInterface $operand1, NumberInterface $operand2): bool
    {
        return 0 !== $this->compare($decimals, $operand1, $operand2);
    }

    public function percentageDifference(
        int $operandsDecimals,
        int $outputDecimals,
        NumberInterface $operand1,
        NumberInterface $operand2
    ): Number {
        $result = null;

        if ($this->same($operandsDecimals, new Number('0'), $operand1) && $this->same($operandsDecimals, new Number('0'), $operand2)) {
            $result = $this->round(
                $outputDecimals,
                new Number('0')
            );
        } elseif ($this->different($operandsDecimals, new Number('0'), $operand1) && $this->same($operandsDecimals, new Number('0'), $operand2)) {
            $result = $this->round(
                $outputDecimals,
                new Number('-100')
            );
        } elseif ($this->same($operandsDecimals, new Number('0'), $operand1) && $this->different($operandsDecimals, new Number('0'), $operand2)) {
            $result = $this->round(
                $outputDecimals,
                new Number('100')
            );
        } else {

            $difference = $this->subtract($operandsDecimals + 3, $operand2, $operand1);
            $divResult = $this->divide($operandsDecimals + 3, $difference, $operand1);
            $mulResult = $this->multiply($operandsDecimals + 3, $divResult, new Number('100'));
            $result = $this->round($outputDecimals, $mulResult);
        }

        return $result;
    }

    public function round(int $decimals, NumberInterface $number, \RoundingMode $mode = \RoundingMode::HalfAwayFromZero): Number
    {
        return new Number(bcround($number->getValue(), $decimals, $mode));
    }
}
