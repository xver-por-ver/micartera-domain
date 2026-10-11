<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Command\StockCreateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\StockDeleteCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\StockUpdateCommand;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockCollection;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;

/**
 * @internal
 */
#[CoversClass(StockCreateCommand::class)]
#[CoversClass(StockUpdateCommand::class)]
#[CoversClass(StockDeleteCommand::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(StockCollection::class)]
class StockCommandTest extends TestCase
{
    /** @var Currency&Stub */
    private Currency $currency;
    private Stock&Stub $stock;
    private StockRepositoryInterface&Stub $repoStock;
    private AccountRepositoryInterface&Stub $repoAccount;
    private ExchangeRepositoryInterface&Stub $repoExchange;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private StockPersistenceInterface&Stub $stockPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private ExchangePersistenceInterface&Stub $exchangePersistence;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoExchange = $this->createStub(ExchangeRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->stockPersistence->method('getRepositoryForAcquisition')->willReturn($this->repoAcquisition);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->exchangePersistence = $this->createStub(ExchangePersistenceInterface::class);
        $this->exchangePersistence->method('getRepository')->willReturn($this->repoExchange);
    }

    public function testCreateCommandSucceeds(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($this->currency);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($account);
        $command = new StockCreateCommand($this->stockPersistence, $this->accountPersistence, $this->exchangePersistence);
        $stock = $command->invoke('TEST', 'Test', new \Xver\MiCartera\Domain\Number\Domain\Number('5.44'), 'test@example.com', 'BME');
        $this->assertInstanceOf(Stock::class, $stock);
    }

    public function testUpdateCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $command = new StockUpdateCommand($this->stockPersistence);
        $command->invoke('TEST', 'Test', new \Xver\MiCartera\Domain\Number\Domain\Number('5.44'));
    }

    public function testRemoveCommandSucceeds(): void
    {
        $this->expectNotToPerformAssertions();
        $this->repoStock->method('findByIdOrThrowException')->willReturn($this->stock);
        $command = new StockDeleteCommand($this->stockPersistence);
        $command->invoke('TEST');
    }
}
