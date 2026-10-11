<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(StockProfitVO::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(MoneyVO::class)]
class StockProfitVOTest extends TestCase
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
        $instance = new StockProfitVO(new Number('0'), $this->currency);
        $this->assertInstanceOf(StockProfitVO::class, $instance);
    }

    public function testGetCurrency(): void
    {
        $instance = new StockProfitVO(new Number('0'), $this->currency);
        $this->assertSame($this->currency, $instance->getCurrency());
    }

    #[DataProvider('decimalPlacesProvider')]
    public function testDecimalPlaces(string $value): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('numberPrecision');
        new StockProfitVO(new Number($value), $this->currency);
    }

    public static function decimalPlacesProvider(): array
    {
        return [
            ['0.00001'],
            ['-0.00001'],
        ];
    }

    #[DataProvider('numberBetweenProvider')]
    public function testNumberBetween(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('enterNumberBetween');
        new StockProfitVO(new Number($result), $this->currency);
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
        $price = new StockProfitVO(new Number($testPrice), $this->currency);
        $this->assertSame($result, $price->getValue());
        $this->assertSame($resultFormatted, $price->getValueFormatted());
    }

    public static function formatProvider(): array
    {
        return [
            ['0', '0', '0.0000'],
            ['9999999999999.9999', '9999999999999.9999', '9999999999999.9999'],
            ['-9999999999999.9999', '-9999999999999.9999', '-9999999999999.9999'],
            ['999999', '999999', '999999.0000'],
            ['-999999', '-999999', '-999999.0000'],
        ];
    }
}
