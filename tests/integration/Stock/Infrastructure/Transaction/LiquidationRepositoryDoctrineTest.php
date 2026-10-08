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
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
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
#[CoversClass(LiquidationRepository::class)]
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
#[UsesClass(Stock::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(LiquidationCollection::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(MovementRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(MovementPriceVO::class)]
class LiquidationRepositoryDoctrineTest extends IntegrationTestCase
{
    private AcquisitionPersistence $acquisitionPersistence;
    private LiquidationPersistence $liquidationPersistence;
    private MovementPersistence $movementPersistence;
    private Account $account;
    private Stock $stock;
    private Stock $stock2;
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
        $this->stock = $repoStock->findById('CABK');
        $this->stock2 = $repoStock->findById('SAN');
        $this->expenses = new TransactionExpenseVO('11.43', $this->account->getCurrency());
    }

    public function testIsCreatedAndRemoved(): void
    {
        $transaction = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new TransactionAmountVO('99'),
            $this->expenses,
            $this->account
        );
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->liquidationPersistence->getRepository()->findByIdOrThrowException($transactionId);
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $transaction->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
        parent::detachEntity($transaction);
        $this->assertSame(null, $this->liquidationPersistence->getRepository()->findById($transactionId));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new TransactionAmountVO('10'),
            $this->expenses,
            $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $transaction = $this->liquidationPersistence->getRepository()->findById($transactionId);
        $this->assertInstanceOf(Liquidation::class, $transaction);
        $this->assertEquals($transactionId, $transaction->getId());
    }

    public function testFindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $transaction = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('30 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('14'),
            $this->expenses,
            $this->account
        );
        $transactionId = $transaction->getId();
        parent::detachEntity($transaction);
        $this->assertInstanceOf(Liquidation::class, $this->liquidationPersistence->getRepository()->findByIdOrThrowException($transactionId));
    }

    public function testFindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        try {
            $entity = 'Liquidation';
            $uuid = Uuid::v4();
            $this->liquidationPersistence->getRepository()->findByIdOrThrowException($uuid);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $uuid->toString()], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testFindByStockId(): void
    {
        parent::$loadFixtures = true;
        $transactionsCollection = $this->liquidationPersistence->getRepository()->findByStockId($this->stock2, 20, 0);
        $this->assertInstanceOf(LiquidationCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
        new Acquisition(
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
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('25 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            new \DateTime('24 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('400'),
            $this->expenses,
            $this->account
        );
        $transactionsCollection = $this->liquidationPersistence->getRepository()->findByStockId($this->stock2, 20, 0);
        $this->assertSame(2, $transactionsCollection->count());
        foreach ($transactionsCollection->toArray() as $transaction) {
            $this->assertSame($this->stock2->getId(), $transaction->getStock()->getId());
        }
        $transactionsCollection = $this->liquidationPersistence->getRepository()->findByStockId($this->stock2, 1, 0);
        $this->assertSame(1, $transactionsCollection->count());
    }

    public function testFindByStockIdWithNonExistentStockReturnsEmptyArray(): void
    {
        /** @var Stock&Stub */
        $stock = $this->createStub(Stock::class);
        $stock->method('getId')->willReturn('NONEXISTENT');
        $transactionsCollection = $this->liquidationPersistence->getRepository()->findByStockId($stock, 2, 0);
        $this->assertInstanceOf(LiquidationCollection::class, $transactionsCollection);
        $this->assertSame(0, $transactionsCollection->count());
    }

    public function testCreateIsRolledBack(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new TransactionAmountVO('999'),
            $this->expenses,
            $this->account
        );
    }
}
