<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Infrastructure\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Uid\Uuid;
use Tests\integration\IntegrationTestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(AcquisitionRepository::class)]
#[CoversClass(EntityRepository::class)]
#[CoversClass(TransactionAbstract::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(Movement::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyPersistence::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(SummaryVO::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(MovementRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(LiquidationCollection::class)]
#[UsesClass(StockProfitVO::class)]
class AcquisitionRepositoryDoctrineTest extends IntegrationTestCase
{
    private AcquisitionPersistence $acquisitionPersistence;
    private LiquidationPersistence $liquidationPersistence;
    private MovementPersistence $movementPersistence;
    private Account $account;
    private Account $account2;
    private Stock $stock;
    private Stock $stock2;
    private Stock $stock3;
    private TransactionExpenseVO $expenses;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->acquisitionPersistence = new AcquisitionPersistence(self::$registry);
        $this->liquidationPersistence = new LiquidationPersistence(self::$registry);
        $this->movementPersistence = new MovementPersistence(self::$registry);
        $repoAccount = new AccountRepository(self::$registry);
        $repoStock = new StockRepository(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $this->account2 = $repoAccount->findByIdentifier('test_other@example.com');
        $this->stock = $repoStock->findById('CABK');
        $this->stock2 = $repoStock->findById('SAN');
        $this->stock3 = $repoStock->findById('ROVI');
        $this->expenses = new TransactionExpenseVO('11.43', $this->account->getCurrency());
    }

    public function testIsCreatedAndRemoved(): void
    {
        $amount = new TransactionAmountVO('399');
        $transaction = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            $amount,
            $this->expenses,
            $this->account
        );
        $this->assertInstanceOf(Acquisition::class, $transaction);
        $this->assertEquals($this->stock->getPrice(), $transaction->getPrice());
        $this->assertSame($amount->getValue(), $transaction->getAmount()->getValue());
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->acquisitionPersistence->getRepository()->findByIdOrThrowException($transactionId);
        $this->assertInstanceOf(Acquisition::class, $transaction);
        $this->assertEquals($transactionId, $transaction->getId());
        $transaction->persistRemove($this->acquisitionPersistence);
        parent::detachEntity($transaction);
        $this->assertSame(null, $this->acquisitionPersistence->getRepository()->findById($transactionId));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new TransactionAmountVO('654'),
            $this->expenses,
            $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Acquisition::class, $this->acquisitionPersistence->getRepository()->findById($transactionId));
        $this->assertSame($transactionId, $transaction->getId());
    }

    public function testfindByIdWithNonExistingReturnsNull(): void
    {
        $this->assertNull($this->acquisitionPersistence->getRepository()->findById(Uuid::v4()));
    }

    public function testfindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('30 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('654'),
            $this->expenses,
            $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Acquisition::class, $this->acquisitionPersistence->getRepository()->findByIdOrThrowException($transactionId));
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        try {
            $entity = 'Acquisition';
            $uuid = Uuid::v4();
            $this->acquisitionPersistence->getRepository()->findByIdOrThrowException($uuid);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $uuid->toString()], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testFindByStockId(): void
    {
        parent::$loadFixtures = true;
        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByStockId($this->stock2, 20, 0);
        $this->assertInstanceOf(AcquisitionCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
        new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('2 hours ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('654'),
            $this->expenses,
            $this->account
        );
        new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('1 hour ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('654'),
            $this->expenses,
            $this->account
        );
        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByStockId($this->stock2, 20, 0);
        $this->assertSame(2, $transactionsCollection->count());
        foreach ($transactionsCollection->toArray() as $transaction) {
            $this->assertSame($this->stock2->getId(), $transaction->getStock()->getId());
        }
        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByStockId($this->stock2, 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testFindByStockIdWithNonExistentStockReturnsEmptyCollection(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('getId')->willReturn('NONEXISTENT');
        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByStockId($stock, 2, 0);
        $this->assertInstanceOf(AcquisitionCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
    }

    public function testMultipleWithSameAccountStockAndDateTimeThrowsException(): void
    {
        parent::$loadFixtures = true;
        $date = new \DateTime('5 hours ago', new \DateTimeZone('UTC'));
        new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            $date,
            new TransactionAmountVO('544'),
            $this->expenses,
            $this->account
        );
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transExistsOnDateTime');
        new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            $date,
            new TransactionAmountVO('544'),
            $this->expenses,
            $this->account
        );
    }

    public function testRemovalWhenNotFullAmountOutstandingThrowsException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock3,
            $this->stock3->getPrice(),
            new \DateTime('90 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('1500'),
            $this->expenses,
            $this->account
        );
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock3,
            $this->stock3->getPrice(),
            new \DateTime('89 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('420'),
            $this->expenses,
            $this->account
        );
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transBuyCannotBeRemovedWithoutFullAmountOutstanding');
        $transaction->persistRemove($this->acquisitionPersistence);
    }

    public function testFindByAccountWithActionableAmount(): void
    {
        parent::$loadFixtures = true;
        $transaction1 = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('2021-09-21 09:44:12', new \DateTimeZone('UTC')),
            new TransactionAmountVO('440'),
            $this->expenses,
            $this->account2
        );
        $transaction2 = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('2021-09-23 10:51:21s', new \DateTimeZone('UTC')),
            new TransactionAmountVO('600'),
            $this->expenses,
            $this->account2
        );
        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByAccountWithActionableAmount($this->account2, 'ASC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(1));
        $this->assertEquals($transaction1->getId(), $transactionsCollection->offsetGet(0)->getId());
        $this->assertEquals($transaction2->getId(), $transactionsCollection->offsetGet(1)->getId());

        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByAccountWithActionableAmount($this->account2, 'DESC', 'datetimeutc', 0, 0);
        $this->assertSame(2, $transactionsCollection->count());
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(0));
        $this->assertInstanceOf(Acquisition::class, $transactionsCollection->offsetGet(1));
        $this->assertEquals($transaction2->getId(), $transactionsCollection->offsetGet(0)->getId());
        $this->assertEquals($transaction1->getId(), $transactionsCollection->offsetGet(1)->getId());

        $transactionsCollection = $this->acquisitionPersistence->getRepository()->findByAccountWithActionableAmount($this->account2, 'ASC', 'datetimeutc', 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testPortfolioSummary(): void
    {
        $summary = $this->acquisitionPersistence->getRepository()->portfolioSummary($this->account);
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }

    public function testStockPortfolioSummary(): void
    {
        $summary = $this->acquisitionPersistence->getRepository()->portfolioSummary($this->account, $this->stock);
        $this->assertInstanceOf(SummaryVO::class, $summary);
    }
}
