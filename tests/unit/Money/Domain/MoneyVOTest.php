<?php

declare(strict_types=1);

namespace Tests\unit\Money\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(MoneyVO::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
class MoneyVOTest extends TestCase
{
    private Currency&Stub $currency;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('sameId')->willReturn(true);
    }

    public function testCanInstantiate(): void
    {
        $instance = new MoneyVO('0', $this->currency);
        $this->assertInstanceOf(MoneyVO::class, $instance);
    }

    public function testGetCurrency(): void
    {
        $instance = new MoneyVO('0', $this->currency);
        $this->assertSame($this->currency, $instance->getCurrency());
    }

    #[DataProvider('decimalPlacesProvider')]
    public function testDecimalPlaces(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('numberPrecision');
        new MoneyVO($result, $this->currency);
    }

    public static function decimalPlacesProvider(): array
    {
        return [
            ['-0.999'],
            ['0.999'],
        ];
    }

    #[DataProvider('numberBetweenProvider')]
    public function testNumberBetween(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('enterNumberBetween');
        new MoneyVO($result, $this->currency);
    }

    public static function numberBetweenProvider(): array
    {
        return [
            ['-10000000000000'],
            ['10000000000000'],
        ];
    }

    #[DataProvider('formatProvider')]
    public function testFormat(string $testPrice, string $result, string $resultFormatted): void
    {
        $price = new MoneyVO($testPrice, $this->currency);
        $this->assertSame($result, $price->getValue());
        $this->assertSame($resultFormatted, $price->getValueFormatted());
    }

    public static function formatProvider(): array
    {
        return [
            ['-9999999999999.99', '-9999999999999.99', '-9999999999999.99'],
            ['-9999999999999', '-9999999999999', '-9999999999999.00'],
            ['0', '0', '0.00'],
            ['9999999999999.99', '9999999999999.99', '9999999999999.99'],
            ['9999999999999', '9999999999999', '9999999999999.00'],
        ];
    }

    #[DataProvider('operationMethodsProvider')]
    public function testOperationWithDifferentTypesThrowsException(string $method): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);
        $m = new MoneyVO('0', $currency);
        $e = new class ('0', $currency) extends MoneyVO {};
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('operationRequiresBothOperandsWithSameType');
        $m->{$method}($e);
    }

    #[DataProvider('operationMethodsProvider')]
    public function testOperationWithDifferentCurrenciesThrowsException(string $method): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(false);
        $m = new MoneyVO('0', $currency);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('operationRequiresBothOperandsWithSameCurrency');
        $m->{$method}($m);
    }

    public static function operationMethodsProvider(): array
    {
        return [
            ['add'],
            ['subtract'],
            ['percentageDifference'],
        ];
    }

    #[DataProvider('operationMethodsReturnsProvider')]
    public function testOperationReturnsStatic(string $method): void
    {
        $m = new MoneyVO('0', $this->currency);
        $this->assertInstanceOf(MoneyVO::class, $m->{$method}($m));
        $e = new class ('0', $this->currency) extends MoneyVO {};
        $this->assertInstanceOf(get_class($e), $e->{$method}($e));
    }

    public static function operationMethodsReturnsProvider(): array
    {
        return [
            ['add'],
            ['subtract'],
        ];
    }

    public function testPercentageDifferenceReturnedTyped(): void
    {
        $m = new MoneyVO('0', $this->currency);
        $this->assertInstanceOf(Number::class, $m->percentageDifference($m));
    }
}
