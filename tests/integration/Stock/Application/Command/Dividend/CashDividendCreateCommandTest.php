<?php

declare(strict_types=1);

namespace Tests\integration\Stock\Application\Command\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\integration\IntegrationTestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountPersistence;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Command\Dividend\CashDividendCreateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Dividend\CashDividendDeleteCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Dividend\CashDividendUpdateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\AcquisitionCreateCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\AcquisitionDeleteCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\LiquidationCreateCommand;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountException;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSynchronizer;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

#[CoversClass(CashDividendCreateCommand::class)]
#[CoversClass(CashDividendDeleteCommand::class)]
#[CoversClass(CashDividendUpdateCommand::class)]
#[UsesClass(Account::class)]
#[UsesClass(AccountPersistence::class)]
#[UsesClass(AccountRepository::class)]
#[UsesClass(Currency::class)]
#[UsesClass(CurrencyPersistence::class)]
#[UsesClass(CurrencyRepository::class)]
#[UsesClass(EntityPersistence::class)]
#[UsesClass(EntityRepository::class)]
#[UsesClass(Exchange::class)]
#[UsesClass(ExchangePersistence::class)]
#[UsesClass(ExchangeRepository::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(AcquisitionCreateCommand::class)]
#[UsesClass(AcquisitionDeleteCommand::class)]
#[UsesClass(CashDividend::class)]
#[UsesClass(CashDividendAmountException::class)]
#[UsesClass(CashDividendAmountVO::class)]
#[UsesClass(CashDividendCollection::class)]
#[UsesClass(CashDividendMoneyVO::class)]
#[UsesClass(CashDividendSynchronizer::class)]
#[UsesClass(Stock::class)]
#[UsesClass(Movement::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionAmountVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(CashDividendPersistence::class)]
#[UsesClass(CashDividendRepository::class)]
#[UsesClass(StockPersistence::class)]
#[UsesClass(StockRepository::class)]
#[UsesClass(MovementPersistence::class)]
#[UsesClass(AcquisitionPersistence::class)]
#[UsesClass(AcquisitionRepository::class)]
#[UsesClass(LiquidationPersistence::class)]
#[UsesClass(LiquidationRepository::class)]
#[UsesClass(LiquidationCreateCommand::class)]
class CashDividendCreateCommandTest extends IntegrationTestCase
{
    public function testCreatesCashDividendUsingHoldingAtOperationDateAndDefaultsExpensesToZero(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(0), '0.25', 'test@example.com');

        self::assertInstanceOf(CashDividend::class, $cashDividend);
        self::assertSame('200', $cashDividend->getAmount()->getValue());
        self::assertSame('0.25', $cashDividend->getDividendPerShare()->getValue());
        self::assertSame('50', $cashDividend->getTotalDividendPayment()->getValue());
        self::assertSame('0', $cashDividend->getExpenses()->getValue());

        $returnedDate = $cashDividend->getDateTimeUtc();
        $returnedDate->modify('+1 day');
        self::assertSame(
            $this->dateAtSecond(0)->format('Y-m-d H:i:s'),
            $cashDividend->getDateTimeUtc()->format('Y-m-d H:i:s')
        );
    }

    public function testUpdatesDividendPerShareAndExpensesAndRecalculatesGrossPayment(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(0), '0.25', 'test@example.com');

        $updated = new CashDividendUpdateCommand(new CashDividendPersistence(self::$registry))
            ->invoke($cashDividend->getId()->toString(), '0.40', '1.20');

        self::assertSame('0.4', $updated->getDividendPerShare()->getValue());
        self::assertSame('1.2', $updated->getExpenses()->getValue());
        self::assertSame('80', $updated->getTotalDividendPayment()->getValue());
    }

    public function testDeletesCashDividendById(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(0), '0.25', 'test@example.com');

        new CashDividendDeleteCommand(new CashDividendPersistence(self::$registry))
            ->invoke($cashDividend->getId()->toString());

        self::assertNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));
    }

    public function testRecordsExpensesSeparatelyFromGrossDividendPayment(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(0), '0.25', 'test@example.com', '1.50');

        self::assertSame('50', $cashDividend->getTotalDividendPayment()->getValue());
        self::assertSame('1.5', $cashDividend->getExpenses()->getValue());
    }

    public function testRejectsDuplicateCashDividendForSameAccountStockAndDateTime(): void
    {
        self::$loadFixtures = true;
        $date = $this->dateAtSecond(0);
        $this->createCommand()->invoke('CABK', $date, '0.25', 'test@example.com');

        $this->expectException(DomainViolationException::class);
        $this->createCommand()->invoke('CABK', $date, '0.30', 'test@example.com');
    }

    public function testRejectsMoneyValuesWithMorePrecisionThanTheAccountCurrency(): void
    {
        self::$loadFixtures = true;
        $this->expectException(DomainViolationException::class);

        $this->createCommand()->invoke('CABK', $this->dateAtSecond(0), '0.001', 'test@example.com');
    }

    public function testRejectsCashDividendWhenNoHoldingOnOperationDateDespiteLaterHolding(): void
    {
        self::$loadFixtures = true;
        $this->expectException(CashDividendAmountException::class);

        $date = new \DateTime('first day of january last year', new \DateTimeZone('UTC'));
        $this->createCommand()->invoke('CABK', $date, '0.25', 'test@example.com');
    }

    public function testAcquisitionChangesRecalculateAffectedDividendSharesAndGrossPayment(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(5), '0.25', 'test@example.com');
        self::assertNotNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));
        self::assertCount(1, new CashDividendPersistence(self::$registry)->getRepository()->findByAccountStockAtOrAfter($cashDividend->getAccount(), $cashDividend->getStock(), $this->dateAtSecond(2)));

        $this->createAcquisitionCommand()->invoke(
            'CABK',
            $this->dateAtSecond(2),
            '5',
            '2.5',
            '0',
            'test@example.com'
        );

        self::assertSame('204', $cashDividend->getAmount()->getValue());
        self::assertSame('51', $cashDividend->getTotalDividendPayment()->getValue());
        self::assertSame('0', $cashDividend->getExpenses()->getValue());
    }

    public function testLiquidationThatRemovesEntirePositionDeletesAffectedDividend(): void
    {
        self::$loadFixtures = true;
        $cashDividend = $this->createCommand()->invoke('CABK', $this->dateAtSecond(5), '0.25', 'test@example.com');

        $this->createLiquidationCommand()->invoke(
            'CABK',
            $this->dateAtSecond(2),
            '199',
            '2.5',
            '0',
            'test@example.com'
        );

        self::assertNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));
    }

    public function testDeletingAcquisitionRemovesCashDividendBeforeLaterReplacement(): void
    {
        self::$loadFixtures = true;
        $date = $this->dateAtSecond(0);
        $acquisition = $this->createAcquisitionCommand()->invoke(
            'SAN',
            $date,
            '10',
            '3',
            '0',
            'test@example.com'
        );
        $cashDividend = $this->createCommand()->invoke('SAN', $this->dateAtSecond(3600), '0.5', 'test@example.com');

        new AcquisitionDeleteCommand($this->acquisitionPersistence(), $this->cashDividendSynchronizer())
            ->invoke($acquisition->getId()->toString());
        self::assertNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));

        $replacement = $this->createAcquisitionCommand()->invoke(
            'SAN',
            $date,
            '12',
            '3',
            '0',
            'test@example.com'
        );
        self::assertSame('12', $replacement->getAmount()->getValue());
        self::assertNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));
    }

    public function testDeletingAcquisitionThatLeavesNoHoldingDeletesAffectedDividend(): void
    {
        self::$loadFixtures = true;
        $acquisition = $this->createAcquisitionCommand()->invoke(
            'SAN',
            $this->dateAtSecond(0),
            '10',
            '3',
            '0',
            'test@example.com'
        );
        $cashDividend = $this->createCommand()->invoke('SAN', $this->dateAtSecond(3600), '0.5', 'test@example.com');

        new AcquisitionDeleteCommand($this->acquisitionPersistence(), $this->cashDividendSynchronizer())
            ->invoke($acquisition->getId()->toString());

        self::assertNull(new CashDividendPersistence(self::$registry)->getRepository()->findById($cashDividend->getId()));
    }

    private function createCommand(): CashDividendCreateCommand
    {
        return new CashDividendCreateCommand(
            new CashDividendPersistence(self::$registry),
            $this->acquisitionPersistence(),
            new LiquidationPersistence(self::$registry),
            new AccountPersistence(self::$registry),
            new StockPersistence(self::$registry)
        );
    }

    private function createAcquisitionCommand(): AcquisitionCreateCommand
    {
        return new AcquisitionCreateCommand(
            $this->acquisitionPersistence(),
            new LiquidationPersistence(self::$registry),
            new MovementPersistence(self::$registry),
            new AccountPersistence(self::$registry),
            new StockPersistence(self::$registry),
            $this->cashDividendSynchronizer()
        );
    }

    private function createLiquidationCommand(): LiquidationCreateCommand
    {
        return new LiquidationCreateCommand(
            new LiquidationPersistence(self::$registry),
            $this->acquisitionPersistence(),
            new MovementPersistence(self::$registry),
            new AccountPersistence(self::$registry),
            new StockPersistence(self::$registry),
            $this->cashDividendSynchronizer()
        );
    }

    private function acquisitionPersistence(): AcquisitionPersistence
    {
        return new AcquisitionPersistence(self::$registry);
    }

    private function cashDividendSynchronizer(): CashDividendSynchronizer
    {
        return new CashDividendSynchronizer(
            new CashDividendPersistence(self::$registry),
            $this->acquisitionPersistence(),
            new LiquidationPersistence(self::$registry)
        );
    }

    private function dateAtSecond(int $second): \DateTime
    {
        $date = new \DateTime('first day of january', new \DateTimeZone('UTC'));
        $date->modify(sprintf('+%d seconds', $second));

        return $date;
    }
}
