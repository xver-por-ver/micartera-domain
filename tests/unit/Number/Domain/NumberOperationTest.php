<?php

declare(strict_types=1);

namespace Tests\unit\Number\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;

/**
 * @internal
 */
#[CoversClass(NumberOperation::class)]
#[UsesClass(Number::class)]
class NumberOperationTest extends TestCase
{
    private static NumberOperation $numberOperation;

    public static function setUpBeforeClass(): void
    {
        self::$numberOperation = new NumberOperation();
    }

    #[DataProvider('addProvider')]
    public function testAdd(string $result, int $decimals, string $operand1, string $operand2): void
    {
        $this->assertSame(new Number($result)->getValue(), self::$numberOperation->add($decimals, new Number($operand1), new Number($operand2))->getValue());
    }

    public static function addProvider(): array
    {
        return [
            ['5.34', 2, '4', '1.34'],
            ['5.34', 2, '-4', '9.34'],
            ['5.66', 2, '4.663', '1'],
            ['5.66', 2, '4.667', '1'],
            ['5.34', 2, '4', '1.343'],
            ['5.34', 2, '4', '1.347'],
            ['5.35', 2, '4.004', '1.347'],
            ['5.34', 2, '4.002', '1.347'],
            ['5.3400', 4, '4', '1.34'],
            ['5.3411', 4, '4', '1.3411'],
        ];
    }

    #[DataProvider('subtractProvider')]
    public function testSubtract(string $result, int $decimals, string $operand1, string $operand2): void
    {
        $this->assertSame(new Number($result)->getValue(), self::$numberOperation->subtract($decimals, new Number($operand1), new Number($operand2))->getValue());
    }

    public static function subtractProvider(): array
    {
        return [
            ['3.59', 2, '6', '2.41'],
            ['-8.41', 2, '-6', '2.41'],
            ['4.12', 2, '6.127', '2'],
            ['4.12', 2, '6.122', '2'],
            ['4.41', 2, '6.413', '2'],
            ['4.41', 2, '6.417', '2'],
            ['3.58', 2, '6', '2.413'],
            ['3.58', 2, '6', '2.417'],
            ['3.5900', 4, '6', '2.41'],
            ['3.5845', 4, '6', '2.4155'],
        ];
    }

    #[DataProvider('multiplyProvider')]
    public function testMultiply(string $result, int $decimals, string $operand1, string $operand2, ?\RoundingMode $roundingMode): void
    {
        $roundingMode
        ? $this->assertSame(new Number($result)->getValue(), self::$numberOperation->multiply(
            $decimals,
            new Number($operand1),
            new Number($operand2),
            $roundingMode
        )->getValue())
        : $this->assertSame(new Number($result)->getValue(), self::$numberOperation->multiply(
            $decimals,
            new Number($operand1),
            new Number($operand2)
        )->getValue());
    }

    public static function multiplyProvider(): array
    {
        return [
            ['5.36', 2, '4', '1.34', null],
            ['-37.36', 2, '-4', '9.34', null],
            ['13.99', 2, '4.663', '3', null],
            ['14.00', 2, '4.667', '3', null],
            ['14.01', 2, '4.667', '3', \RoundingMode::AwayFromZero],
            ['5.37', 2, '4', '1.343', null],
            ['5.38', 2, '4', '1.343', \RoundingMode::AwayFromZero],
            ['5.39', 2, '4', '1.347', null],
            ['5.39', 2, '1.347', '4', null],
            ['5.3600', 4, '4', '1.34', null],
            ['5.3644', 4, '4', '1.3411', null],
        ];
    }

    #[DataProvider('sameProvider')]
    public function testSame(int $decimals, string $operand1, string $operand2, bool $assertTrue): void
    {
        if ($assertTrue) {
            $this->assertTrue(self::$numberOperation->same($decimals, new Number($operand1), new Number($operand2)));
        } else {
            $this->assertFalse(self::$numberOperation->same($decimals, new Number($operand1), new Number($operand2)));
        }
    }

    public static function sameProvider(): array
    {
        return [
            [2, '5.56', '5.56', true],
            [2, '5.56', '5.567', true],
            [2, '5.567', '5.56', true],
            [2, '5.56', '5.57', false],
            [2, '5', '5.00', true],
        ];
    }

    #[DataProvider('divideProvider')]
    public function testDivide(string $result, int $decimals, string $operand1, string $operand2, ?\RoundingMode $roundingMode): void
    {
        $roundingMode
        ? $this->assertSame(new Number($result)->getValue(), self::$numberOperation->divide(
            $decimals,
            new Number($operand1),
            new Number($operand2),
            $roundingMode
        )->getValue())
        : $this->assertSame(new Number($result)->getValue(), self::$numberOperation->divide(
            $decimals,
            new Number($operand1),
            new Number($operand2)
        )->getValue());
    }

    public static function divideProvider(): array
    {
        return [
            ['0.04', 2, '4', '100', null],
            ['-0.04', 2, '-4', '100', null],
            ['0.43', 2, '428', '1000', null],
            ['0.43', 2, '421', '1000', \RoundingMode::AwayFromZero],
            ['-0.43', 2, '-421', '1000', \RoundingMode::AwayFromZero],
            ['-0.43', 2, '-428', '1000', null],
            ['0.4280', 4, '428', '1000', null],
            ['0.00', 2, '0', '1000', null],
        ];
    }

    public function testDivideByZeroThrowException(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        self::$numberOperation->divide(
            2,
            new Number('10'),
            new Number('0')
        );
    }

    #[DataProvider('percentageProvider')]
    public function testPercentageDifference(string $result, int $decimals1, int $decimals2, string $operand1, string $operand2): void
    {
        $this->assertSame(new Number($result)->getValue(), self::$numberOperation->percentageDifference($decimals1, $decimals2, new Number($operand1), new Number($operand2))->getValue());
    }

    public static function percentageProvider(): array
    {
        return [
            ['100.00', 2, 2, '0', '10'],
            ['-100.00', 2, 2, '3', '0'],
            ['0.00', 2, 2, '0', '0'],
            ['233.33', 2, 2, '3', '10'],
            ['200.00', 2, 2, '3', '9'],
            ['181.33', 2, 2, '3', '8.44'],
            ['191.67', 2, 2, '3', '8.75'],
            ['145.50', 3, 2, '3.666', '9'],
            ['34.43', 2, 2, '2440', '3280'],
            ['21.74', 2, 2, '5879.743', '7158.25'],
            ['-11.92', 2, 2, '5872.33', '5172.6'],
        ];
    }

    #[DataProvider('comparisonsProvider')]
    public function testComparisons(bool $result, string $op, int $decimals, string $num1, string $num2): void
    {
        if ($result) {
            $this->assertTrue(self::$numberOperation->{$op}($decimals, new Number($num1), new Number($num2)));
        } else {
            $this->assertFalse(self::$numberOperation->{$op}($decimals, new Number($num1), new Number($num2)));
        }
    }

    public static function comparisonsProvider(): array
    {
        return [
            [true, 'same', 0, '1', '1'],
            [false, 'same', 0, '1', '2'],
            [true, 'same', 1, '1', '1'],
            [false, 'same', 1, '1', '2'],
            [true, 'same', 1, '1.0', '1'],
            [false, 'same', 1, '1.0', '2'],
            [true, 'greater', 0, '1', '0'],
            [false, 'greater', 0, '0', '1'],
            [true, 'greater', 1, '1.1', '0.4'],
            [false, 'greater', 1, '0.4', '1.1'],
            [true, 'greaterOrEqual', 0, '1', '0'],
            [false, 'greaterOrEqual', 0, '0', '1'],
            [true, 'greaterOrEqual', 1, '1.1', '0.4'],
            [false, 'greaterOrEqual', 1, '0.4', '1.1'],
            [true, 'greaterOrEqual', 0, '1', '1'],
            [true, 'greaterOrEqual', 1, '1.1', '1.1'],
            [true, 'smaller', 0, '0', '1'],
            [false, 'smaller', 0, '1', '0'],
            [true, 'smaller', 1, '0.4', '1.1'],
            [false, 'smaller', 1, '1.1', '0.4'],
            [true, 'smallerOrEqual', 0, '0', '1'],
            [false, 'smallerOrEqual', 0, '1', '0'],
            [true, 'smallerOrEqual', 1, '0.4', '1.1'],
            [false, 'smallerOrEqual', 1, '1.1', '0.4'],
            [true, 'smallerOrEqual', 1, '1.1', '1.1'],
            [true, 'smallerOrEqual', 0, '1', '1'],
            [true, 'different', 0, '1', '2'],
            [true, 'different', 1, '1.1', '2.2'],
            [false, 'different', 1, '1.0', '1'],
        ];
    }

    #[DataProvider('roundProvider')]
    public function testRound(string $result, int $decimals, string $number, \RoundingMode $mode): void
    {
        $this->assertSame(new Number($result)->getValue(), self::$numberOperation->round($decimals, new Number($number), $mode)->getValue());
    }

    public static function roundProvider(): array
    {
        return [
            ['0.00', 2, '0.0000', \RoundingMode::HalfAwayFromZero],
            ['0.04', 2, '0.0449', \RoundingMode::HalfAwayFromZero],
            ['0.05', 2, '0.0450', \RoundingMode::HalfAwayFromZero],
            ['-0.04', 2, '-0.0449', \RoundingMode::HalfAwayFromZero],
            ['-0.05', 2, '-0.0450', \RoundingMode::HalfAwayFromZero],
            ['0.00', 2, '0.0000', \RoundingMode::AwayFromZero],
            ['0.05', 2, '0.0449', \RoundingMode::AwayFromZero],
            ['0.05', 2, '0.0450', \RoundingMode::AwayFromZero],
            ['-0.05', 2, '-0.0449', \RoundingMode::AwayFromZero],
            ['-0.05', 2, '-0.0450', \RoundingMode::AwayFromZero],
        ];
    }
}
