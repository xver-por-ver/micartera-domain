<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(MovementPriceVO::class)]
class MovementPriceVOTest extends TestCase
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
        $instance = new MovementPriceVO(new Number('0'), $this->currency);
        $this->assertInstanceOf(MovementPriceVO::class, $instance);
    }

    public function testGetCurrency(): void
    {
        $instance = new MovementPriceVO(new Number('0'), $this->currency);
        $this->assertSame($this->currency, $instance->getCurrency());
    }

    #[DataProvider('decimalPlacesProvider')]
    public function testDecimalPlaces(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('numberPrecision');
        new MovementPriceVO(new Number($result), $this->currency);
    }

    public static function decimalPlacesProvider(): array
    {
        return [
            ['0.00001'],
            ['0.99999'],
        ];
    }

    #[DataProvider('numberBetweenProvider')]
    public function testNumberBetween(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('enterNumberBetween');
        new MovementPriceVO(new Number($result), $this->currency);
    }

    public static function numberBetweenProvider(): array
    {
        return [
            ['-0.0001'],
            ['10000000000000'],
        ];
    }

    #[DataProvider('formatProvider')]
    public function testFormat(string $testPrice, string $result, string $resultFormatted): void
    {
        $price = new MovementPriceVO(new Number($testPrice), $this->currency);
        $this->assertSame($result, $price->getValue());
        $this->assertSame($resultFormatted, $price->getValueFormatted());
    }

    public static function formatProvider(): array
    {
        return [
            ['0', '0', '0.0000'],
            ['9999999999999.9999', '9999999999999.9999', '9999999999999.9999'],
            ['9999999999999', '9999999999999', '9999999999999.0000'],
        ];
    }
}
