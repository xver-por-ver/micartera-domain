<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Movement::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(MovementPriceVO::class)]
class MovementTest extends TestCase
{
    private Stock&Stub $stock;
    private MovementRepositoryInterface&Stub $repoMovement;
    private MovementPersistenceInterface&Stub $movementPersistence;
    private AcquisitionPersistenceInterface&Stub $acquisitionPersistence;
    private LiquidationPersistenceInterface&Stub $liquidationPersistence;

    public function setUp(): void
    {
        $this->repoMovement = $this->createStub(MovementRepositoryInterface::class);
        $this->movementPersistence = $this->createStub(MovementPersistenceInterface::class);
        $this->movementPersistence->method('getRepository')->willReturn($this->repoMovement);
        $this->acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $this->liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('sameId')->willReturn(true);
    }

    #[DataProvider('createValues')]
    public function testIsCreated(
        string $acquisitionAmountActionable,
        string $acquisitionPrice,
        string $acquisitionExpenses,
        string $liquidationAmountActionable,
        string $liquidationPrice,
        string $liquidationExpenses,
        string $movementAmount,
        string $movementAcquisitionPrice,
        string $movementAcquisitionExpenses,
        string $movementLiquidationPrice,
        string $movementLiquidationExpenses,
    ): void {
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('sameId')->willReturn(true);
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO($acquisitionAmountActionable));
        $acquisition->method('getPrice')->willReturn(new StockPriceVO($acquisitionPrice, $currency));
        $acquisition->method('getCurrency')->willReturn($currency);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn(new TransactionExpenseVO($acquisitionExpenses, $currency));

        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $liquidation->method('sameId')->willReturn(true);
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO($liquidationAmountActionable));
        $liquidation->method('getPrice')->willReturn(new StockPriceVO($liquidationPrice, $currency));
        $liquidation->method('getCurrency')->willReturn($currency);
        $liquidation->method('getExpensesUnaccountedFor')->willReturn(new TransactionExpenseVO($liquidationExpenses, $currency));

        $accountingMovement = new Movement($this->movementPersistence, $this->acquisitionPersistence, $this->liquidationPersistence, $acquisition, $liquidation);
        $this->assertSame($acquisition, $accountingMovement->getAcquisition());
        $this->assertSame($liquidation, $accountingMovement->getLiquidation());
        $this->assertTrue($accountingMovement->sameId($accountingMovement));
        $this->assertSame(new TransactionAmountVO($movementAmount)->getValue(), $accountingMovement->getAmount()->getValue());
        $this->assertSame($movementAcquisitionPrice, $accountingMovement->getAcquisitionPrice()->getValueFormatted());
        $this->assertSame($movementLiquidationPrice, $accountingMovement->getLiquidationPrice()->getValueFormatted());
        $this->assertSame($movementAcquisitionExpenses, $accountingMovement->getAcquisitionExpenses()->getValue());
        $this->assertSame($movementLiquidationExpenses, $accountingMovement->getLiquidationExpenses()->getValue());
    }

    public static function createValues(): array
    {
        return [
            ['10', '57.8000', '23.54', '10', '60.8000', '15.66', '10', '578.0000', '23.54', '608.0000', '15.66'],
            ['100', '578.0000', '23.54', '100', '608.0000', '15.66', '100', '57800.0000', '23.54', '60800.0000', '15.66'],
            ['200', '1.1234', '10.55', '100', '1.5678', '5.45', '100', '112.3400', '5.27', '156.7800', '5.45'],
            ['95', '61.6634', '10.55', '95', '61.6634', '10.55', '95', '5858.0300', '10.55', '5858.0300', '10.55'],
            ['10', '5.7800', '4.93', '100', '8.5300', '3.51', '10', '57.8000', '4.93', '85.3000', '0.35'],
        ];
    }

    public function testMovementWithTransactionsHavingDifferentStockThrowsException(): void
    {
        $stock = $this->createStub(Stock::class);
        $stock->method('sameId')->willReturn(false);

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($stock);

        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($stock);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('transactionAssertStock');
        new Movement($this->movementPersistence, $this->acquisitionPersistence, $this->liquidationPersistence, $acquisition, $liquidation);
    }

    public function testLiquidationDateNotAfterAcquistionThrowsException(): void
    {
        $price = $this->createStub(StockPriceVO::class);

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('3'));
        $acquisition->method('getPrice')->willReturn($price);

        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('3'));
        $liquidation->method('getPrice')->willReturn($price);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('accountingMovementAssertDateTime');
        new Movement($this->movementPersistence, $this->acquisitionPersistence, $this->liquidationPersistence, $acquisition, $liquidation);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $accountingMovement = $this->getMockBuilder(Movement::class)->disableOriginalConstructor()->onlyMethods(['getAcquisition'])->getMock();
        $accountingMovement->expects($this->never())->method('getAcquisition');
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $accountingMovement->sameId($entity);
    }

    public function testAcquisitionWithoutAmountOutstandingThrowsException(): void
    {
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('0'));

        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('accountingMovementAcquisitionHasNoAmountOutstanding');
        new Movement($this->movementPersistence, $this->acquisitionPersistence, $this->liquidationPersistence, $acquisition, $liquidation);
    }

    public function testLiquidationWithoutAmountRemainingThrowsException(): void
    {
        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getStock')->willReturn($this->stock);
        $acquisition->method('getDateTimeUtc')->willReturn(new \DateTime('30 minutes ago'));
        $acquisition->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('10'));

        $liquidation = $this->createStub(Liquidation::class);
        $liquidation->method('getStock')->willReturn($this->stock);
        $liquidation->method('getDateTimeUtc')->willReturn(new \DateTime('20 minutes ago'));
        $liquidation->method('getAmountActionable')->willReturn(new TransactionAmountActionableVO('0'));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('accountingMovementLiquidationHasNoAmountRemaining');
        new Movement($this->movementPersistence, $this->acquisitionPersistence, $this->liquidationPersistence, $acquisition, $liquidation);
    }
}
