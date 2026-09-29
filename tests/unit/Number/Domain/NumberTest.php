<?php

declare(strict_types=1);

namespace Tests\unit\Number\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Number::class)]
#[UsesClass(NumberOperation::class)]
class NumberTest extends TestCase
{
    #[DataProvider('formatsProvider')]
    public function testAssertValueFormat(string $value, bool $exception): void
    {
        if ($exception) {
            $this->expectException(DomainViolationException::class);
            $this->expectExceptionMessageIs('numberFormat');
        } else {
            $this->expectNotToPerformAssertions();
        }
        new Number($value);
    }

    public static function formatsProvider(): array
    {
        return [
            ['-1', false],
            ['0', false],
            ['1', false],
            ['-1.001', false],
            ['0.001', false],
            ['1.001', false],
            ['0001.00100', false],
            ['+1', true],
            ['a', true],
            ['1.', true],
            ['.1', true],
            ['1e2', true],
            ['-1e2', true],
            ['1a', true],
            ['', true],
        ];
    }

    #[DataProvider('trimZerosProvider')]
    public function testTrimZeros(string $result, string $value): void
    {
        $this->assertSame($result, new Number($value)->getValue());
    }

    public static function trimZerosProvider(): array
    {
        return [
            ['0', '00.00'],
            ['1', '01.00'],
            ['1.1', '01.10'],
            ['1.1', '00000000000001.10000000000000'],
            ['0', '-00.00'],
            ['-0.01', '-00.01'],
            ['-1', '-01.00'],
            ['-1.1', '-01.10'],
            ['-1.1', '-00000000000001.10000000000000'],
        ];
    }

    #[DataProvider('assertDecimalPlacesProvider')]
    public function testAssertDecimalPlaces(string $value): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('numberPrecision');
        new Number($value);
    }

    public static function assertDecimalPlacesProvider(): array
    {
        return [
            ['0.12345678912345'],
            ['0.12345678912349'],
        ];
    }

    #[DataProvider('valueWithinRangeProvider')]
    public function testAssertValueWithinRange(string $value): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('enterNumberBetween');
        new Number($value);
    }

    public static function valueWithinRangeProvider(): array
    {
        return [
            ['10000000000000'],
            ['-10000000000000'],
        ];
    }

    public function testZeroAndMaxLimits(): void
    {
        $zero = new Number('0.00');
        $this->assertSame('0', $zero->getValue());
        $max = new Number(Number::VALUE_MAX);
        $this->assertSame(Number::VALUE_MAX, $max->getValue());
    }

    #[DataProvider('getValueProvider')]
    public function testGetValue(string $result, string $value): void
    {
        $this->assertSame($result, new Number($value)->getValue());
    }

    public static function getValueProvider(): array
    {
        return [
            ['5', '5.00'],
            ['-5', '-5.00'],
            ['3.4', '3.40'],
            ['-3.4', '-3.40'],
            ['4', '04'],
            ['-4', '-04'],
            ['4.4', '04.40'],
            ['-4.4', '-04.40'],
            ['0.4', '0.40'],
            ['-0.4', '-0.40'],
        ];
    }

    #[DataProvider('greaterProvider')]
    public function testGreater(string $greater, string $smaller): void
    {
        $this->assertTrue(
            new Number($greater)->greater(new Number($smaller))
        );
    }

    public static function greaterProvider(): array
    {
        return [
            ['0', '-1'],
            ['1', '0'],
            ['-1', '-2'],
            ['0.0000000000002', '0.0000000000001'],
            ['-0.0000000000001', '-0.0000000000002'],
        ];
    }

    #[DataProvider('sameProvider')]
    public function testSame(string $number1, string $number2): void
    {
        $this->assertTrue(new Number($number1)->same(new Number($number2)));
    }

    public static function sameProvider(): array
    {
        return [
            ['0', '0'],
            ['0', '0.000000000'],
            ['1', '1.000000000'],
            ['-1', '-1.000000000'],
        ];
    }

    #[DataProvider('smallerProvider')]
    public function testSmaller(string $smaller, string $greater): void
    {
        $this->assertTrue(
            new Number($smaller)->smaller(new Number($greater))
        );
    }

    public static function smallerProvider(): array
    {
        return [
            ['-1', '0'],
            ['0', '1'],
            ['-2', '-1'],
            ['0.0000000000001', '0.0000000000002'],
            ['-0.0000000000002', '-0.0000000000001'],
        ];
    }

    #[DataProvider('smallerOrEqualProvider')]
    public function testSmallerOrEqual(string $smaller, string $greater): void
    {
        $this->assertTrue(
            new Number($smaller)->smallerOrEqual(new Number($greater))
        );
    }

    public static function smallerOrEqualProvider(): array
    {
        return [
            ['-1', '0'],
            ['0', '1'],
            ['-2', '-1'],
            ['0.0000000000001', '0.0000000000002'],
            ['-0.0000000000002', '-0.0000000000001'],
            ['0', '0'],
            ['0.0000000000001', '0.0000000000001'],
            ['-0.0000000000001', '-0.0000000000001'],
        ];
    }

    #[DataProvider('differentProvider')]
    public function testDifferent(string $value1, string $value2): void
    {
        $this->assertTrue(
            new Number($value1)->different(new Number($value2))
        );
    }

    public static function differentProvider(): array
    {
        return [
            ['-1', '0'],
            ['0', '1'],
            ['-2', '-1'],
            ['0.0000000000001', '0.0000000000002'],
            ['-0.0000000000002', '-0.0000000000001'],
        ];
    }
}
