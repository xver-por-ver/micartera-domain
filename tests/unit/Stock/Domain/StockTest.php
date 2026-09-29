<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Stock::class)]
#[UsesClass(Account::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockPriceVO::class)]
class StockTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO&Stub $stockPrice;
    private StockRepositoryInterface&Stub $repoStock;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private Exchange&Stub $exchange;
    private StockPersistenceInterface&MockObject $stockPersistence;

    public function setUp(): void
    {
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->stockPersistence = $this->createMock(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->stockPersistence->method('getRepositoryForAcquisition')->willReturn($this->repoAcquisition);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->stockPrice = $this->createStub(StockPriceVO::class);
        $this->stockPrice->method('getCurrency')->willReturn($this->currency);
        $this->stockPrice->method('getValue')->willReturn('4.5614');
        $this->exchange = $this->createStub(Exchange::class);
    }

    public function testStockObjectIsCreated(): void
    {
        $this->stockPersistence->expects($this->once())->method('persist');
        $this->currency->method('sameId')->willReturn(true);
        $code = "TEST";
        $name = "TEST NAME";
        $stock = new Stock($this->stockPersistence, $code, $name, $this->stockPrice, $this->exchange);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertTrue($stock->sameId($stock));
        $this->assertSame($name, $stock->getName());
        $this->assertSame('4.5614', $stock->getPrice()->getValue());
        $this->assertSame($this->currency, $stock->getCurrency());
        $this->assertSame($code, $stock->getId());
        $this->assertSame($this->exchange, $stock->getExchange());
    }

    public function testDuplicateStockCodeThrowsException(): void
    {
        $this->stockPersistence->expects($this->once())->method('persist');
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', $this->stockPrice, $this->exchange);
        $this->repoStock->method('findById')->willReturn($stock);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stockExists');
        new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
    }

    #[DataProvider('invalidCodes')]
    public function testStockCodeFormat(string $code): void
    {
        $this->stockPersistence->expects($this->never())->method('persist');
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stringLength');
        new Stock($this->stockPersistence, $code, 'TEST NAME', $this->stockPrice, $this->exchange);
    }

    public static function invalidCodes(): array
    {
        return [
            [''], ['ABCDE'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function testStockNameFormat(string $name): void
    {
        $this->stockPersistence->expects($this->never())->method('persist');
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stringLength');
        new Stock($this->stockPersistence, 'TEST', $name, $this->stockPrice, $this->exchange);
    }

    public static function invalidNames(): array
    {
        $name = '';
        for ($i = 0; $i < 256; ++$i) {
            $name .= mt_rand(0, 9);
        }

        return [
            [''], [$name],
        ];
    }

    public function testUpdateStockPriceWithInvalidCurrencyThrowsException(): void
    {
        $this->stockPersistence->expects($this->once())->method('persist');
        $this->currency->method('sameId')->willReturn(false);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');

        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
        $stock->persistUpdate($this->stockPersistence, $stock->getName(), $this->stockPrice);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->stockPersistence->expects($this->once())->method('persist');
        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        $stock->sameId($entity);
    }

    public function testSetPrice(): void
    {
        $this->stockPersistence->expects($this->exactly(2))->method('persist');
        $this->currency->method('sameId')->willReturn(true);
        $newStockPrice = $this->createStub(StockPriceVO::class);
        $newStockPrice->method('getValue')->willReturn('6.7824');

        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);       
        $stock->persistUpdate($this->stockPersistence, $stock->getName(), $newStockPrice);
        $this->assertSame('6.7824', $stock->getPrice()->getValue());
    }

    public function testRemoveWhenHavingTransactionsWillThrowException(): void
    {
        $this->stockPersistence->expects($this->never())->method('remove');
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(1);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stockHasTransactions');

        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
        $stock->persistRemove($this->stockPersistence);
    }

    public function testRemove(): void
    {
        $this->stockPersistence->expects($this->once())->method('remove');
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);

        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
        $stock->persistRemove($this->stockPersistence);
    }

    public function testUpdate(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);
        $this->stockPersistence->expects($this->once())->method('persist');
        $this->stockPersistence->expects($this->once())->method('flush');
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);

        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods(['getCurrency'])->getMock();
        $stock->method('getCurrency')->willReturn($currency);
        $stock->expects($this->once())->method('getCurrency');
        $stock->persistUpdate($this->stockPersistence, 'newName', new StockPriceVO('37.21', $this->stockPrice->getCurrency()));
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->stockPersistence->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating exception');

        new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);
    }

    public function testExceptionIsThrownOnUpdateCommitFail(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);
        $this->stockPersistence->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating exception');

        $stock = $this->getMockBuilder(Stock::class)->disableOriginalConstructor()->onlyMethods(['getCurrency'])->getMock();
        $stock->method('getCurrency')->willReturn($currency);
        $stock->expects($this->once())->method('getCurrency');
        $stock->persistUpdate($this->stockPersistence, 'newName', new StockPriceVO('33.4451', $this->stockPrice->getCurrency()));
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        $this->stockPersistence->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating exception'));
        $acquisitionsColletion = $this->createStub(AcquisitionCollection::class);
        $acquisitionsColletion->method('count')->willReturn(0);
        $this->repoAcquisition->method('findByStockId')->willReturn($acquisitionsColletion);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating exception');

        $stock = new Stock($this->stockPersistence, 'TEST', 'TEST NAME', $this->stockPrice, $this->exchange);   
        $stock->persistRemove($this->stockPersistence);
    }
}
