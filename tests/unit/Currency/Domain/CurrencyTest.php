<?php

declare(strict_types=1);

namespace Tests\unit\Currency\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyPersistenceInterface;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Currency::class)]
#[UsesClass(Stock::class)]
class CurrencyTest extends TestCase
{
    private CurrencyPersistenceInterface&Stub $currencyPersistence;
    private CurrencyRepositoryInterface&Stub $repoCurrency;

    public function setUp(): void
    {
        $this->repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $this->currencyPersistence = $this->createStub(CurrencyPersistenceInterface::class);
        $this->currencyPersistence->method('getRepository')->willReturn($this->repoCurrency);
    }

    public function testDuplicateCodeThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('currencyCodeAlreadyExists');
        new Currency($this->currencyPersistence, 'EUR', '€', 2);
    }

    public function testCurrencyValueObjectIsCreated(): void
    {
        $iso3 = 'EUR';
        $symbol = '€';
        $decimals = 2;
        $curreny = new Currency($this->currencyPersistence, $iso3, $symbol, $decimals);
        $this->assertSame($iso3, $curreny->getISO3());
        $this->assertSame($symbol, $curreny->getSymbol());
        $this->assertSame($decimals, $curreny->getDecimals());
        $this->assertTrue($curreny->sameId($curreny));
    }

    #[DataProvider('invalidCodes')]
    public function testInvalidCodeThrowExceptions($testCode): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('invalidIso3');
        new Currency($this->currencyPersistence, $testCode, '€', 2);
    }

    public static function invalidCodes(): array
    {
        return [
            [''],
            ['A'],
            ['AA'],
            ['AAAA'],
        ];
    }

    #[DataProvider('invalidSymbols')]
    public function testInvalidSymbolThrowExceptions($testSymbol): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('invalidCurrencySymbol');
        new Currency($this->currencyPersistence, 'ABC', $testSymbol, 2);
    }

    public static function invalidSymbols(): array
    {
        return [
            [''],
            ['12345678901'],
        ];
    }

    #[DataProvider('invalidPrecisions')]
    public function testInvalidPrecisionThrowExceptions($testPrecision): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('enterNumberBetween');
        new Currency($this->currencyPersistence, 'ABC', '€', $testPrecision);
    }

    public static function invalidPrecisions(): array
    {
        return [
            [5],
            [0],
            [-1],
        ];
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $currency = new Currency($this->currencyPersistence, 'EUR', '€', 2);
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $currency->sameId($entity);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        $this->currencyPersistence->method('persist')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        new Currency($this->currencyPersistence, 'EUR', '€', 2);
    }
}
