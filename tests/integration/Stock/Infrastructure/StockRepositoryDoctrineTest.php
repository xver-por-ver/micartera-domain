<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Infrastructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
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
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockCollection;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(StockRepository::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyPersistence::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(StockCollection::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(EntityRepository::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(MovementRepository::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(Movement::class)]
#[UsesClass(MovementPriceVO::class)]
class StockRepositoryDoctrineTest extends IntegrationTestCase
{
    private StockPersistence $stockPersistence;
    private Currency $currencyEuro;
    private Currency $currencyDollar;
    private Exchange $exchange;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->stockPersistence = new StockPersistence(self::$registry);
        $repoCurrency = new CurrencyRepository(self::$registry);
        $this->currencyEuro = $repoCurrency->findById('EUR');
        $this->currencyDollar = $repoCurrency->findById('USD');
        $repoExchange = new ExchangeRepository(self::$registry);
        $this->exchange = $repoExchange->findById('MCE');
    }

    public function testStockIsAddedUpdatedAndRemoved(): void
    {
        $stock = new Stock($this->stockPersistence, 'ABCD', 'ABCD Name', new StockPriceVO('2.6632', $this->currencyEuro), $this->exchange);
        $this->assertInstanceOf(Stock::class, $stock);
        parent::detachEntity($stock);
        $stock = $this->stockPersistence->getRepository()->findById($stock->getId());
        $this->assertInstanceOf(Stock::class, $stock);
        $newName = 'ABCD Name New';
        $newPrice = new StockPriceVO('2.7400', $stock->getCurrency());
        $stock->persistUpdate($this->stockPersistence, $newName, $newPrice);
        parent::detachEntity($stock);
        $stock = $this->stockPersistence->getRepository()->findById($stock->getId());
        $this->assertSame($newName, $stock->getName());
        $this->assertEquals($newPrice, $stock->getPrice());
        $stock->persistRemove($this->stockPersistence);
        parent::detachEntity($stock);
        $this->assertSame(null, $this->stockPersistence->getRepository()->findById($stock->getId()));
    }

    public function testfindByIdOrThrowException(): void
    {
        $stockCode = 'CABK';
        $stock = $this->stockPersistence->getRepository()->findByIdOrThrowException($stockCode);
        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertSame($stockCode, $stock->getId());
    }

    public function testfindByIdOrThrowExceptionWithNonExistingThrowsException(): void
    {
        try {
            $entity = 'Stock';
            $id = 'XXX';
            $this->stockPersistence->getRepository()->findByIdOrThrowException($id);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $id], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testFindByCurrency(): void
    {
        parent::$loadFixtures = true;
        $expected = [
            new Stock($this->stockPersistence, 'EFGH', 'EFGGH Name', new StockPriceVO('4.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->stockPersistence, 'IJKL', 'IJKL Name', new StockPriceVO('5.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->stockPersistence, 'MNOP', 'MNOP Name', new StockPriceVO('4.2301', $this->currencyDollar), $this->exchange),
        ];
        $amount = count($expected);
        $stocks = $this->stockPersistence->getRepository()->findByCurrency($this->currencyDollar, 10, 0, 'code', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 1, 2 => 2];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->stockPersistence->getRepository()->findByCurrency($this->currencyDollar, 10, 0, 'code', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 2, 1 => 1, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->stockPersistence->getRepository()->findByCurrency($this->currencyDollar, 10, 0, 'price.value', 'ASC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 0, 1 => 2, 2 => 1];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
        $stocks = $this->stockPersistence->getRepository()->findByCurrency($this->currencyDollar, 10, 0, 'price.value', 'DESC');
        $this->assertCount($amount, $stocks);
        $ordering = [0 => 1, 1 => 2, 2 => 0];
        foreach ($stocks as $key => $stock) {
            $this->assertTrue($stock->sameId($expected[$ordering[$key]]));
        }
    }

    public function testCountByCurrency(): void
    {
        parent::$loadFixtures = true;
        $expected = [
            new Stock($this->stockPersistence, 'EFGH', 'EFGGH Name', new StockPriceVO('4.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->stockPersistence, 'IJKL', 'IJKL Name', new StockPriceVO('5.2300', $this->currencyDollar), $this->exchange),
            new Stock($this->stockPersistence, 'MNOP', 'MNOP Name', new StockPriceVO('4.2301', $this->currencyDollar), $this->exchange),
        ];
        $amount = count($expected);
        $amountDb = $this->stockPersistence->getRepository()->countByCurrency($this->currencyDollar);
        $this->assertSame($amount, $amountDb);
    }

    public function testRemovingStockHavingTransactionsThrowsException(): void
    {
        $stock = $this->stockPersistence->getRepository()->findById('CABK');
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('stockHasTransactions');
        $stock->persistRemove($this->stockPersistence);
    }
}
