<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Liquidation::class)]
#[CoversClass(TransactionAbstract::class)]
#[UsesClass(Currency::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Movement::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
class LiquidationTest extends TestCase
{
    private Currency&Stub $currency;
    private StockPriceVO $price;
    private Stock&Stub $stock;
    private static \DateTime $dateTimeUtc;
    private static TransactionAmountVO $amount;
    private TransactionExpenseVO $expenses;
    private Account&Stub $account;
    private MovementRepositoryInterface&Stub $repoMovement;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private LiquidationRepositoryInterface&Stub $repoLiquidation;
    private AcquisitionPersistenceInterface&Stub $acquisitionPersistence;
    private LiquidationPersistenceInterface&MockObject $liquidationPersistence;
    private MovementPersistenceInterface&Stub $movementPersistence;

    public static function setUpBeforeClass(): void
    {
        self::$dateTimeUtc = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        self::$amount = new TransactionAmountVO('100');
    }

    public function setUp(): void
    {
        $this->repoMovement = $this->createStub(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createStub(LiquidationRepositoryInterface::class);
        $this->repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $this->acquisitionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->liquidationPersistence = $this->createMock(LiquidationPersistenceInterface::class);
        $this->liquidationPersistence->method('getRepository')->willReturn($this->repoLiquidation);
        $this->movementPersistence = $this->createStub(MovementPersistenceInterface::class);
        $this->movementPersistence->method('getRepository')->willReturn($this->repoMovement);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('getIso3')->willReturn('EUR');
        $this->account = $this->createStub(Account::class);
        $this->price = new StockPriceVO('4.5600', $this->currency);
        $this->expenses = new TransactionExpenseVO('23.34', $this->currency);
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->stock->method('getPrice')->willReturn($this->price);
        $this->stock->method('sameId')->willReturn(true);
    }

    public function testCreate(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('beginTransaction');
        $this->liquidationPersistence->expects($this->once())->method('persist');
        $this->liquidationPersistence->expects($this->once())->method('flush');
        $this->liquidationPersistence->expects($this->once())->method('commit');

        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->never())->method('fiFoCriteriaInstance');
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $this->assertSame($this->stock, $transaction->getStock());
        $this->assertEquals(self::$dateTimeUtc->format('Y-m-d H:i:s'), $transaction->getDateTimeUtc()->format('Y-m-d H:i:s'));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmount()->getValue());
        $this->assertEquals($this->price, $transaction->getPrice());
        $this->assertEquals($this->expenses, $transaction->getExpenses());
        $this->assertSame($this->account, $transaction->getAccount());
        $this->assertInstanceOf(Uuid::class, $transaction->getId());
        $this->assertSame($this->currency, $transaction->getCurrency());
        $this->assertTrue($transaction->sameId($transaction));
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountActionable()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testCreateWithSameAccountStockAndDatetimeWillThrowException(): void
    {
        $this->liquidationPersistence->expects($this->never())->method('persist');
        $repoLiquidation = $this->createStub(LiquidationRepositoryInterface::class);
        $repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(false);
        $liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $liquidationPersistence->method('getRepository')->willReturn($repoLiquidation);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('transExistsOnDateTime');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDateInFutureThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->never())->method('persist');
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('futureDateNotAllowed');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), new \DateTime('tomorrow', new \DateTimeZone('UTC')), self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    #[DataProvider('invalidAmount')]
    public function testInvalidAmountFormatThrowsException(string $transAmount): void
    {
        $this->liquidationPersistence->expects($this->never())->method('persist');
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('enterNumberBetween');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, new TransactionAmountVO($transAmount), $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public static function invalidAmount(): array
    {
        return [
            ['1000000000'],
            ['-1'],
            ['0'],
        ];
    }

    public function testMovementWithWrongLiquidationThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->exactly(2))->method('persist');
        $transaction1 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction1->expects($this->never())->method('fiFoCriteriaInstance');
        $transaction2 = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction2->expects($this->never())->method('fiFoCriteriaInstance');
        $movement = $this->createStub(Movement::class);
        $movement->method('getLiquidation')->willReturn($transaction2);
        $this->expectException(\InvalidArgumentException::class);
        $transaction1->accountMovement($movement);
    }

    public function testSameIdWithIncorrectEntityArgumentThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('persist');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->never())->method('fiFoCriteriaInstance');
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        $transaction->sameId($entity);
    }

    public function testWrongExpensesCurrencyThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->never())->method('persist');
        $expenses = $this->createStub(TransactionExpenseVO::class);
        $expenses->method('getCurrency')->willReturn($this->createStub(Currency::class));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testAccountMovementAndClearMovements(): void
    {
        $this->liquidationPersistence->expects($this->exactly(3))->method('persist');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(self::$amount);
        $movement->expects($this->exactly(2))->method('getLiquidationExpenses')->willReturn($this->expenses);
        $this->assertSame($transaction, $transaction->accountMovement($movement));
        $this->assertSame('0', $transaction->getAmountActionable()->getValue());
        $this->assertEquals(new TransactionExpenseVO('0.00', $this->currency), $transaction->getExpensesUnaccountedFor());
        $acquisitionsCollection = $transaction->clearMovementCollection($this->acquisitionPersistence, $this->liquidationPersistence, $this->movementPersistence);
        $this->assertInstanceOf(AcquisitionCollection::class, $acquisitionsCollection);
        $this->assertSame(self::$amount->getValue(), $transaction->getAmountActionable()->getValue());
        $this->assertEquals($this->expenses, $transaction->getExpensesUnaccountedFor());
    }

    public function testMovementWithWrongExpensesAmountThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('persist');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn($this->expenses->add(new TransactionExpenseVO('1', $this->currency)));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('InvalidMovementExpensesAmount');
        $transaction->accountMovement($movement);
    }

    public function testMovementAmountGreaterThanAmountRemainingThrowsException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('persist');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance', 'sameId'])->getMock();
        $transaction->expects($this->once())->method('sameId')->willReturn(true);
        $movement = $this->createMock(Movement::class);
        $movement->expects($this->once())->method('getAmount')->willReturn(
            new TransactionAmountVO(bcadd(self::$amount->getValue(), '1'))
        );
        $movement->expects($this->once())->method('getLiquidationExpenses')->willReturn(new TransactionExpenseVO('4.56', $this->currency));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('MovementAmountNotWithinAllowedLimits');
        $transaction->accountMovement($movement);
    }

    public function testCreateIsRolledBackOnTransactionException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('beginTransaction');
        $this->liquidationPersistence->expects($this->once())->method('commit')->willThrowException(new \Exception('simulating uncached exception'));
        $this->liquidationPersistence->expects($this->once())->method('rollBack');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testPersistRemove(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->once())->method('fiFoCriteriaInstance');
        $this->liquidationPersistence->expects($this->once())->method('beginTransaction');
        $this->liquidationPersistence->expects($this->once())->method('remove');
        $this->liquidationPersistence->expects($this->once())->method('flush');
        $this->liquidationPersistence->expects($this->once())->method('commit');
        $transaction->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
    }

    public function testRemoveIsRolledBackOnTransactionException(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->once())->method('fiFoCriteriaInstance');
        $this->liquidationPersistence->expects($this->once())->method('beginTransaction');
        $this->liquidationPersistence->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating uncached exception'));
        $this->liquidationPersistence->expects($this->once())->method('rollBack');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        $transaction->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
    }

    public function testExceptionIsThrownOnCreateCommitFail(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('commit')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testExceptionIsThrownOnRemoveCommitFail(): void
    {
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->once())->method('fiFoCriteriaInstance');
        $this->liquidationPersistence->expects($this->once())->method('remove')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        $transaction->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
    }

    public function testDomainExceptionWhileInCreateTransactionThrowsDomainException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('persist')->willThrowException(new \Exception('simulating exception is thrown'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating exception is thrown');
        $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
    }

    public function testDomainExceptionWhileInRemoveTransactionThrowsDomainException(): void
    {
        $this->liquidationPersistence->expects($this->once())->method('persist');
        $transaction = $this->getMockBuilder(Liquidation::class)->enableOriginalConstructor()->setConstructorArgs(
            [$this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence, $this->stock, $this->stock->getPrice(), self::$dateTimeUtc, self::$amount, $this->expenses, $this->account]
        )->onlyMethods(['fiFoCriteriaInstance'])->getMock();
        $transaction->expects($this->once())->method('fiFoCriteriaInstance')->willThrowException(new \Exception('simulating exception is thrown'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating exception is thrown');
        $transaction->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
    }
}
