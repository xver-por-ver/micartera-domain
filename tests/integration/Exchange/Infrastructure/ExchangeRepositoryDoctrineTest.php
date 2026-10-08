<?php

declare(strict_types=1);

namespace Tests\integration\Exchange\Infrastructure;

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
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeCollection;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
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

/**
 * @internal
 */
#[CoversClass(ExchangeRepository::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyPersistence::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(ExchangeCollection::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Movement::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(Stock::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(EntityRepository::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(MovementRepository::class)]
class ExchangeRepositoryDoctrineTest extends IntegrationTestCase
{
    private ExchangePersistence $exchangePersistence;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $this->exchangePersistence = new ExchangePersistence(self::$registry);
    }

    public function testExchangeIsPersisted(): void
    {
        parent::$loadFixtures = true;
        $exchange = new Exchange($this->exchangePersistence, 'CODE', 'NAME');
        $this->exchangePersistence->persist($exchange);
        parent::detachEntity($exchange);
        $this->assertInstanceOf(Exchange::class, $this->exchangePersistence->getRepository()->findById($exchange->getCode()));
    }

    public function testExchangeIsFoundById(): void
    {
        $exchange = $this->exchangePersistence->getRepository()->findById('MCE');
        $this->assertInstanceOf(Exchange::class, $exchange);
        $this->assertSame('MCE', $exchange->getCode());
    }

    public function testExchangeIsFoundByIdOrThrowsException(): void
    {
        $exchange = $this->exchangePersistence->getRepository()->findByIdOrThrowException('MCE');
        $this->assertInstanceOf(Exchange::class, $exchange);
        $this->assertSame('MCE', $exchange->getCode());
    }

    public function testExchangeIsFoundByIdOrThrowsExceptionWhenNotFoundWillThrowException(): void
    {
        $entity = 'Exchange';
        $id = 'XXX';

        try {
            $this->exchangePersistence->getRepository()->findByIdOrThrowException($id);
        } catch (EntityNotFoundException $th) {
            $this->assertSame('entityNotFound', $th->getTranslatableMessage()->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $id], $th->getTranslatableMessage()->getParameters());
            $this->assertSame('PhpAppCore', $th->getTranslatableMessage()->getDomain());
        }
    }

    public function testAll(): void
    {
        $exchangesCollection = $this->exchangePersistence->getRepository()->all();
        $this->assertInstanceOf(ExchangeCollection::class, $exchangesCollection);
    }
}
