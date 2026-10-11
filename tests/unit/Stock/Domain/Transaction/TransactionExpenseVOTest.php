<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(TransactionExpenseVO::class)]
#[UsesClass(NumberOperation::class)]
class TransactionExpenseVOTest extends TestCase
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
        $instance = new TransactionExpenseVO(new Number('0'), $this->currency);
        $this->assertInstanceOf(TransactionExpenseVO::class, $instance);
    }

    public function testGetCurrency(): void
    {
        $instance = new TransactionExpenseVO(new Number('0'), $this->currency);
        $this->assertSame($this->currency, $instance->getCurrency());
    }

    #[DataProvider('decimalPlacesProvider')]
    public function testDecimalPlaces(string $result): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('numberPrecision');
        new TransactionExpenseVO(new Number($result), $this->currency);
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
        $this->expectExceptionMessage('enterNumberBetween');
        new TransactionExpenseVO(new Number($result), $this->currency);
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
        $price = new TransactionExpenseVO(new Number($testPrice), $this->currency);
        $this->assertSame($result, $price->getValue());
        $this->assertSame($resultFormatted, $price->getValueFormatted());
    }

    public static function formatProvider(): array
    {
        return [
            ['0', '0', '0.00'],
            ['9999999999999.99', '9999999999999.99', '9999999999999.99'],
            ['9999999999999', '9999999999999', '9999999999999.00'],
        ];
    }
}
