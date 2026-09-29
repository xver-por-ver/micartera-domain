<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Infrastructure\Transaction\Criteria;

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
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
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
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(FiFoCriteria::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(Movement::class)]
#[UsesClass(MovementCollection::class)]
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
#[UsesClass(Liquidation::class)]
#[UsesClass(LiquidationCollection::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(MovementRepository::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(EntityRepository::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(MovementPriceVO::class)]
class FifoCriteriaTest extends IntegrationTestCase
{
    private Account $account;
    private Stock $stock;
    private Stock $stock2;
    private TransactionExpenseVO $expenses;
    private AcquisitionPersistence $acquisitionPersistence;
    private LiquidationPersistence $liquidationPersistence;
    private MovementPersistence $movementPersistence;

    protected function resetEntityManager(): void
    {
        parent::resetEntityManager();
        $repoAccount = new AccountRepository(self::$registry);
        $repoStock = new StockRepository(self::$registry);
        $this->account = $repoAccount->findByIdentifierOrThrowException('test@example.com');
        $this->stock = $repoStock->findByIdOrThrowException('CABK');
        $this->stock2 = $repoStock->findByIdOrThrowException('SAN');
        $this->expenses = new TransactionExpenseVO('4.56', $this->account->getCurrency());
        $this->acquisitionPersistence = new AcquisitionPersistence(self::$registry);
        $this->liquidationPersistence = new LiquidationPersistence(self::$registry);
        $this->movementPersistence = new MovementPersistence(self::$registry);
    }

    public function testNoAcquistionBeforeDateThrowsException(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('last year', new \DateTimeZone('UTC')),
            new TransactionAmountVO('100'),
            $this->expenses,
            $this->account
        );
    }

    public function testNotEnoughAcquistionAmountOutstandingThrowsException(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('30 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('201'),
            $this->expenses,
            $this->account
        );
    }

    public function testNotEnoughAcquistionAmountOutstandingAfterRearrangementThrowsException(): void
    {
        parent::$loadFixtures = true;
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('30 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('1'),
            $this->expenses,
            $this->account
        );
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock,
            $this->stock->getPrice(),
            new \DateTime('40 minutes ago', new \DateTimeZone('UTC')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
    }

    public function testFifo(): void
    {
        parent::$loadFixtures = true;

        /** @var Acquisition[] */
        $acquisitions = [];

        /** @var Liquidation[] */
        $liquidations = [];
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        // Test create acquisition generating no accounting movements
        $acquisitions[0] = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT60M')),
            new TransactionAmountVO('1000'),
            $this->expenses,
            $this->account
        );
        $expectedMovements = [];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountVO('1000'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test create liquidation requiring no accounting movements rearrangements
        $liquidations[0] = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT30M')),
            new TransactionAmountVO('500'),
            $this->expenses,
            $this->account
        );
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('500')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountVO('500'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test create acquisition requiring accounting movement rearrangement
        $acquisitions[1] = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT90M')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('200')],
            1 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('300')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('700'),
            1 => new TransactionAmountActionableVO('0'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test create liquidation requiring accounting movement rearrangement
        $liquidations[1] = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT86M')),
            new TransactionAmountVO('100'),
            $this->expenses,
            $this->account
        );
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[1], 'amount' => new TransactionAmountVO('100')],
            1 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('100')],
            2 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('400')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('600'),
            1 => new TransactionAmountActionableVO('0'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test create other liquidation requiring accounting movement rearrangement
        $liquidations[2] = new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT31M')),
            new TransactionAmountVO('500'),
            $this->expenses,
            $this->account
        );
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[1], 'amount' => new TransactionAmountVO('100')],
            1 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('100')],
            2 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('400')],
            3 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('500')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('100'),
            1 => new TransactionAmountActionableVO('0'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test remove liquidation requiring accounting movements rearrangement
        $liquidations[1]->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('200')],
            1 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('300')],
            2 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[0], 'amount' => new TransactionAmountVO('500')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('200'),
            1 => new TransactionAmountActionableVO('0'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test remove other liquidation not requiring accounting movements rearrangement
        $liquidations[0]->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
        $expectedMovements = [
            0 => ['acquisition' => $acquisitions[1], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('200')],
            1 => ['acquisition' => $acquisitions[0], 'liquidation' => $liquidations[2], 'amount' => new TransactionAmountVO('300')],
        ];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('700'),
            1 => new TransactionAmountActionableVO('0'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test add acquisition not requiring rearrangement
        $acquisitions[2] = new Acquisition(
            $this->acquisitionPersistence,
            $this->liquidationPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT20M')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));
        $expectedAmountOutstanding = [
            0 => new TransactionAmountActionableVO('700'),
            1 => new TransactionAmountActionableVO('0'),
            2 => new TransactionAmountActionableVO('200'),
        ];
        $this->checkAcquisitionsAmountOutstanding($acquisitions, $expectedAmountOutstanding);

        // Test adding liquidation with insufficient amount outstanding throws exception
        $exceptionsThrown = 0;
        $exceptionsMessagesCorrect = 0;

        try {
            new Liquidation(
                $this->liquidationPersistence,
                $this->acquisitionPersistence,
                $this->movementPersistence,
                $this->stock2,
                $this->stock2->getPrice(),
                (clone $acquisitions[1]->getDateTimeUtc())->sub(new \DateInterval('PT30S')),
                new TransactionAmountVO('1000'),
                $this->expenses,
                $this->account
            );
        } catch (DomainViolationException $th) {
            ++$exceptionsThrown;
            if ('transNotPassFifoSpec' === $th->getMessage()) {
                ++$exceptionsMessagesCorrect;
            }
            $this->resetEntityManager();
        }

        try {
            new Liquidation(
                $this->liquidationPersistence,
                $this->acquisitionPersistence,
                $this->movementPersistence,
                $this->stock2,
                $this->stock2->getPrice(),
                (clone $liquidations[2]->getDateTimeUtc())->sub(new \DateInterval('PT30S')),
                new TransactionAmountVO('1200'),
                $this->expenses,
                $this->account
            );
        } catch (DomainViolationException $th) {
            ++$exceptionsThrown;
            if ('transNotPassFifoSpec' === $th->getMessage()) {
                ++$exceptionsMessagesCorrect;
            }
            $this->resetEntityManager();
        }
        $this->assertSame(2, $exceptionsThrown);
        $this->assertSame(2, $exceptionsMessagesCorrect);

        // Test remove liquidation not requiring rearrangement
        $this->liquidationPersistence->getRepository()->findById($liquidations[2]->getId())->persistRemove($this->liquidationPersistence, $this->acquisitionPersistence, $this->movementPersistence);
        $expectedMovements = [];
        $this->checkMovements($expectedMovements, $this->retrieveMovements($this->movementPersistence->getRepository()));

        // Test adding liquidation requiring accounting movements rearrangement
        // causes existing liquidation not find acquistions with enough
        // amount outstanding
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT50M')),
            new TransactionAmountVO('1000'),
            $this->expenses,
            $this->account
        );
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT25M')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('transNotPassFifoSpec');
        new Liquidation(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence,
            $this->stock2,
            $this->stock2->getPrice(),
            (clone $date)->sub(new \DateInterval('PT55M')),
            new TransactionAmountVO('200'),
            $this->expenses,
            $this->account
        );
    }

    private function checkMovements(
        array $expectedMovements,
        MovementCollection $persistedMovements
    ): void {
        $this->assertSame(count($expectedMovements), $persistedMovements->count());
        foreach ($persistedMovements->toArray() as $key => $persistedAccountingMovement) {
            $this->assertTrue($persistedAccountingMovement->getAcquisition()->sameId($expectedMovements[$key]['acquisition']));
            $this->assertTrue($persistedAccountingMovement->getLiquidation()->sameId($expectedMovements[$key]['liquidation']));
            $this->assertSame($persistedAccountingMovement->getAmount()->getValue(), $expectedMovements[$key]['amount']->getValue());
        }
    }

    private function retrieveMovements(MovementRepositoryInterface $repoAccountingMovement): MovementCollection
    {
        return $repoAccountingMovement->findByAccountStockAcquisitionDateAfter($this->account, $this->stock2, new \DateTime('30 days ago', new \DateTimeZone('UTC')));
    }

    private function checkAcquisitionsAmountOutstanding(array $acquisitions, array $expectedAcquisitionsAmountOutstanding): void
    {
        foreach ($acquisitions as $key => $acquisition) {
            $this->assertSame($acquisition->getAmountActionable()->getValue(), $expectedAcquisitionsAmountOutstanding[$key]->getValue());
        }
    }
}
