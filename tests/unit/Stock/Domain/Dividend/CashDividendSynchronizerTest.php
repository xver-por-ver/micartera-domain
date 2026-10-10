<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountException;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSynchronizer;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;

#[CoversClass(CashDividendSynchronizer::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(CashDividendCollection::class)]
#[UsesClass(CashDividendAmountVO::class)]
#[UsesClass(CashDividendAmountException::class)]
class CashDividendSynchronizerTest extends TestCase
{
    public function testDoesNothingWhenThereAreNoAffectedDividends(): void
    {
        $cashDividendPersistence = $this->createMock(CashDividendPersistenceInterface::class);
        $cashDividendPersistence->expects(self::never())->method('persist');
        $cashDividendPersistence->expects(self::never())->method('remove');
        $cashDividendPersistence->expects(self::never())->method('flush');

        $synchronizer = $this->createSynchronizer(new CashDividendCollection([]), $cashDividendPersistence);
        $synchronizer->synchronize($this->createStub(Account::class), $this->createStub(Stock::class), new \DateTime('now', new \DateTimeZone('UTC')));
    }

    public function testRecalculatesEachDividendAtItsPaymentDateAndFlushesChanges(): void
    {
        $account = $this->createStub(Account::class);
        $stock = $this->createStub(Stock::class);
        $paymentDate = new \DateTime('2025-01-15 10:00:00', new \DateTimeZone('UTC'));
        $dividend = $this->createMock(CashDividend::class);
        $dividend->expects(self::exactly(2))->method('getDateTimeUtc')->willReturn($paymentDate);
        $dividend->expects(self::once())->method('setAmountAndTotalDividendPayment')
            ->with(self::callback(static fn(CashDividendAmountVO $amount): bool => '90' === $amount->getValue()));

        $cashDividendPersistence = $this->createMock(CashDividendPersistenceInterface::class);
        $cashDividendPersistence->expects(self::once())->method('persist')->with($dividend);
        $cashDividendPersistence->expects(self::never())->method('remove');
        $cashDividendPersistence->expects(self::once())->method('flush');

        $synchronizer = $this->createSynchronizer(
            new CashDividendCollection([$dividend]),
            $cashDividendPersistence,
            new Number('100'),
            new Number('10')
        );
        $synchronizer->synchronize($account, $stock, new \DateTime('2025-01-01', new \DateTimeZone('UTC')));
    }

    public function testDeletesDividendWhenRecalculationLeavesNoHolding(): void
    {
        $account = $this->createStub(Account::class);
        $stock = $this->createStub(Stock::class);
        $dividend = $this->createMock(CashDividend::class);
        $dividend->expects(self::exactly(2))->method('getDateTimeUtc')
            ->willReturn(new \DateTime('2025-01-15', new \DateTimeZone('UTC')));

        $cashDividendPersistence = $this->createMock(CashDividendPersistenceInterface::class);
        $cashDividendPersistence->expects(self::never())->method('persist');
        $cashDividendPersistence->expects(self::once())->method('remove')->with($dividend);
        $cashDividendPersistence->expects(self::once())->method('flush');

        $synchronizer = $this->createSynchronizer(new CashDividendCollection([$dividend]), $cashDividendPersistence);
        $synchronizer->synchronize($account, $stock, new \DateTime('2025-01-01', new \DateTimeZone('UTC')));
    }

    private function createSynchronizer(
        CashDividendCollection $dividends,
        CashDividendPersistenceInterface $cashDividendPersistence,
        ?Number $acquired = null,
        ?Number $liquidated = null
    ): CashDividendSynchronizer {
        $cashDividendRepository = $this->createStub(CashDividendRepositoryInterface::class);
        $cashDividendRepository->method('findByAccountStockAtOrAfter')->willReturn($dividends);
        $cashDividendPersistence->method('getRepository')->willReturn($cashDividendRepository);

        $acquisitionRepository = $this->createStub(AcquisitionRepositoryInterface::class);
        $acquisitionRepository->method('totalAmountForAccountStockAtOrBefore')->willReturn($acquired ?? new Number('0'));
        $acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $acquisitionPersistence->method('getRepository')->willReturn($acquisitionRepository);

        $liquidationRepository = $this->createStub(LiquidationRepositoryInterface::class);
        $liquidationRepository->method('totalAmountForAccountStockAtOrBefore')->willReturn($liquidated ?? new Number('0'));
        $liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $liquidationPersistence->method('getRepository')->willReturn($liquidationRepository);

        return new CashDividendSynchronizer($cashDividendPersistence, $acquisitionPersistence, $liquidationPersistence);
    }
}
