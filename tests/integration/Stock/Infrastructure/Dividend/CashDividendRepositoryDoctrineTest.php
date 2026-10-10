<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Infrastructure\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Uid\Uuid;
use Tests\integration\IntegrationTestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;

/**
 * @internal
 */
#[CoversClass(CashDividendRepository::class)]
#[CoversClass(AcquisitionRepository::class)]
#[CoversClass(LiquidationRepository::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(\Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence::class)]
#[UsesClass(\Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence::class)]
#[UsesClass(\Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository::class)]
#[UsesClass(\Xver\MiCartera\Domain\Exchange\Domain\Exchange::class)]
#[UsesClass(\Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence::class)]
#[UsesClass(\Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository::class)]
#[UsesClass(CashDividendAmountVO::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection::class)]
#[UsesClass(Currency::class)]
#[UsesClass(EntityPersistence::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(CashDividend::class)]
#[UsesClass(CashDividendMoneyVO::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO::class)]
#[UsesClass(Stock::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(CashDividendPersistence::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence::class)]
#[UsesClass(\Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
class CashDividendRepositoryDoctrineTest extends IntegrationTestCase
{
    private CashDividendPersistence $cashDividentPersistence;
    private AcquisitionPersistence $acquisitionPersistence;
    private LiquidationPersistence $liquidationPersistence;
    private Account $account;
    private Stock $stock;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->cashDividentPersistence = new CashDividendPersistence(self::$registry);
        $this->acquisitionPersistence = new AcquisitionPersistence(self::$registry);
        $this->liquidationPersistence = new LiquidationPersistence(self::$registry);
        $repoAccount = new AccountRepository(self::$registry);
        $this->account = $repoAccount->findByIdentifier('test@example.com');
        $repoStock = new StockRepository(self::$registry);
        $this->stock = $repoStock->findById('CABK');
    }

    public function testFindByIdOrThrowException(): void
    {
        parent::$loadFixtures = true;
        $cashDividend = $this->createCashDividend(new \DateTime('yesterday', new \DateTimeZone('UTC')));

        self::assertSame(
            $cashDividend,
            $this->cashDividentPersistence->getRepository()->findByIdOrThrowException($cashDividend->getId())
        );
    }

    public function testFindByIdOrThrowExceptionThrowsForMissingDividend(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->cashDividentPersistence->getRepository()->findByIdOrThrowException(Uuid::v4());
    }

    public function testAssertNoCashDividendOnDateTime(): void
    {
        parent::$loadFixtures = true;
        $date = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $repository = $this->cashDividentPersistence->getRepository();

        self::assertTrue($repository->assertNoCashDividendOnDateTime($this->account, $this->stock, $date));
        $this->createCashDividend($date);
        self::assertFalse($repository->assertNoCashDividendOnDateTime($this->account, $this->stock, $date));
        $differentTime = clone $date;
        $differentTime->modify('+1 second');
        self::assertTrue($repository->assertNoCashDividendOnDateTime($this->account, $this->stock, $differentTime));
    }

    public function testFindByAccountStockAtOrAfterReturnsMatchingDividendsInDateOrder(): void
    {
        parent::$loadFixtures = true;
        $firstDate = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $secondDate = clone $firstDate;
        $secondDate->modify('+1 hour');
        $this->createCashDividend($secondDate);
        $this->createCashDividend($firstDate);

        $dividends = $this->cashDividentPersistence->getRepository()->findByAccountStockAtOrAfter(
            $this->account,
            $this->stock,
            $firstDate
        );

        self::assertCount(2, $dividends);
        self::assertSame($firstDate->format('Y-m-d H:i:s'), $dividends->first()->getDateTimeUtc()->format('Y-m-d H:i:s'));
    }

    public function testFindByAccountStockReturnsAllMatchingDividendsInReverseDateOrder(): void
    {
        parent::$loadFixtures = true;
        $firstDate = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $secondDate = clone $firstDate;
        $secondDate->modify('+1 hour');
        $this->createCashDividend($firstDate);
        $this->createCashDividend($secondDate);

        $dividends = $this->cashDividentPersistence->getRepository()->findByAccountStock($this->account, $this->stock);

        self::assertCount(2, $dividends);
        self::assertSame($secondDate->format('Y-m-d H:i:s'), $dividends->first()->getDateTimeUtc()->format('Y-m-d H:i:s'));
    }

    public function testfindById(): void
    {
        parent::$loadFixtures = true;
        $cashDividend = new CashDividend(
            $this->cashDividentPersistence,
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->stock,
            $this->account,
            new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new CashDividendMoneyVO('145', $this->account->getCurrency()),
            new CashDividendMoneyVO('3', $this->account->getCurrency())
        );
        $cashDividendId = $cashDividend->getId();
        parent::detachEntity($cashDividendId);
        $cashDividend = $this->cashDividentPersistence->getRepository()->findById($cashDividendId);
        $this->assertInstanceOf(CashDividend::class, $cashDividend);
        $this->assertEquals($cashDividendId, $cashDividend->getId());
    }

    private function createCashDividend(\DateTime $date): CashDividend
    {
        return new CashDividend(
            $this->cashDividentPersistence,
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->stock,
            $this->account,
            $date,
            new CashDividendMoneyVO('145', $this->account->getCurrency()),
            new CashDividendMoneyVO('3', $this->account->getCurrency())
        );
    }
}
