<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Command\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\AcquisitionCreateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\LiquidationCreateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\AcquisitionDeleteCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\LiquidationDeleteCommand;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @internal
 */
#[CoversClass(AcquisitionCreateCommand::class)]
#[CoversClass(LiquidationCreateCommand::class)]
#[CoversClass(AcquisitionDeleteCommand::class)]
#[CoversClass(LiquidationDeleteCommand::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
class StockOperateCommandTest extends TestCase
{
    private Currency&Stub $currency;
    private Account&Stub $account;
    private Stock&Stub $stock;
    private StockRepositoryInterface&Stub $repoStock;
    private AccountRepositoryInterface&Stub $repoAccount;
    private MovementRepositoryInterface&Stub $repoMovement;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private LiquidationRepositoryInterface&Stub $repoLiquidation;
    private AcquisitionPersistenceInterface&Stub $acquisitionPersistence;
    private LiquidationPersistenceInterface&Stub $liquidationPersistence;
    private MovementPersistenceInterface&Stub $movementPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private StockPersistenceInterface&Stub $stockPersistence;

    public function setUp(): void
    {
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoMovement = $this->createStub(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createStub(LiquidationRepositoryInterface::class);
        $this->acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $this->acquisitionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $this->liquidationPersistence->method('getRepository')->willReturn($this->repoLiquidation);
        $this->movementPersistence = $this->createStub(MovementPersistenceInterface::class);
        $this->movementPersistence->method('getRepository')->willReturn($this->repoMovement);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($this->currency);
        $this->account->method('getTimeZone')->willReturn(new \DateTimeZone('Europe/Madrid'));
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
    }

    public function testPurchaseCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $this->repoAcquisition->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $command = new AcquisitionCreateCommand($this->acquisitionPersistence, $this->liquidationPersistence, $this->movementPersistence, $this->accountPersistence, $this->stockPersistence);
        $command->invoke(
            'TEST',
            new \DateTime('now', new \DateTimeZone('UTC')),
            '100',
            '6.5443',
            '5.34',
            'test@example.com'
        );
    }

    public function testRemovePurchaseCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $uuid = Uuid::v4();
        $transaction = $this->createStub(Acquisition::class);
        $this->repoAcquisition->method('findByIdOrThrowException')->willReturn($transaction);
        $command = new AcquisitionDeleteCommand($this->acquisitionPersistence);
        $command->invoke($uuid->toRfc4122());
    }

    public function testSellCommandSucceeds(): void
    {
        $this->repoLiquidation->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $command = $this->createStub(LiquidationCreateCommand::class);        
        $liquidation = $command->invoke(
            'TEST',
            new \DateTime('now', new \DateTimeZone('UTC')),
            '100',
            '6.5443',
            '5.34',
            'test@example.com'
        );
        $this->assertInstanceOf(Liquidation::class, $liquidation);
    }

    public function testRemoveSellCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $uuid = Uuid::v4();
        $transaction = $this->createStub(Liquidation::class);
        $this->repoLiquidation->method('findByIdOrThrowException')->willReturn($transaction);
        $command = new LiquidationDeleteCommand($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
        $command->invoke($uuid->toRfc4122());
    }
}
