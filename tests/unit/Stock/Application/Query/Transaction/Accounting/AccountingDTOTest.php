<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting\AccountingDTO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(AccountingDTO::class)]
#[UsesClass(Movement::class)]
#[UsesClass(Stock::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(MovementCollection::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockProfitVO::class)]
#[UsesClass(CashDividendMoneyVO::class)]
#[UsesClass(CashDividendSummaryVO::class)]
class AccountingDTOTest extends TestCase
{
    private Account&Stub $account;

    public function setUp(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $currency->method('sameId')->willReturn(true);
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($currency);
        $this->account->method('getTimeZone')->willReturn(new \DateTimeZone('UTC'));
    }

    public function testAccountingDTO(): void
    {
        $displayedYear = ((int) new \DateTime('1 year ago')->format('Y'));

        $accountingMovementsCollection = $this->createStub(MovementCollection::class);
        $accountingMovementsCollection->method('offsetExists')->willReturn(true);
        $accountingMovementsCollection->method('offsetGet')->willReturn($this->createStub(Movement::class));

        $summary = $this->createStub(SummaryVO::class);
        $accountingDTO = new AccountingDTO(
            $this->account,
            $accountingMovementsCollection,
            $displayedYear,
            $summary
        );
        // TODO: review this tests
        $this->assertInstanceOf(MovementCollection::class, $accountingDTO->getCollection());
        $this->assertSame($this->account, $accountingDTO->getAccount());
        $this->assertSame((int) new \DateTime('now', $this->account->getTimeZone())->format('Y'), $accountingDTO->getCurrentYear());
        $this->assertSame($displayedYear, $accountingDTO->getDisplayedYear());
        $this->assertSame($summary, $accountingDTO->getSummary());
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementAcquisitionExpense(0));
        $this->assertInstanceOf(MovementPriceVO::class, $accountingDTO->getMovementAcquisitionPrice(0));
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementLiquidationExpense(0));
        $this->assertInstanceOf(MovementPriceVO::class, $accountingDTO->getMovementLiquidationPrice(0));
        $this->assertSame('0', $accountingDTO->getMovementProfitPercentage(0)->getValue());
        $this->assertInstanceOf(MoneyVO::class, $accountingDTO->getMovementProfitPrice(0));
    }

    public function testDividendSummaryAndCombinedNetResults(): void
    {
        $currency = $this->account->getCurrency();
        $summary = $this->createStub(SummaryVO::class);
        $summary->method('getAllTimeProfitPrice')->willReturn(new MoneyVO('5', $currency));
        $summary->method('getDisplayedYearProfitPrice')->willReturn(new MoneyVO('3', $currency));
        $summary->method('getYearFirstLiquidation')->willReturn(2022);
        $dto = new AccountingDTO($this->account, new MovementCollection([]), 2024, $summary);

        self::assertInstanceOf(CashDividendSummaryVO::class, $dto->getCashDividendSummary());
        self::assertSame('5', $dto->getAllTimeCombinedNetResult()->getValue());
        self::assertSame('3', $dto->getDisplayedYearCombinedNetResult()->getValue());
        self::assertSame(2022, $dto->getYearFirstOperation());
    }

    public function testAccountingDTOWithNoAccountingMovements(): void
    {
        $summaryVO = $this->createStub(SummaryVO::class);
        $accountingDTO = new AccountingDTO(
            $this->account,
            new MovementCollection([]),
            (int) new \DateTime('now')->format('Y'),
            $summaryVO
        );
        $this->assertInstanceOf(MovementCollection::class, $accountingDTO->getCollection());
        $this->assertSame(0, $accountingDTO->getCollection()->count());
    }

    public function testNonObjectAccountingMovementsArgumentThrowsExeption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $summaryVO = $this->createStub(SummaryVO::class);
        new AccountingDTO(
            $this->account,
            new MovementCollection([1]),
            (int) new \DateTime('now')->format('Y'),
            $summaryVO
        );
    }

    public function testInvalidAccountingMovementsArgumentThrowsExeption(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $summaryVO = $this->createStub(SummaryVO::class);
        new AccountingDTO(
            $this->account,
            new MovementCollection([new \stdClass()]),
            (int) new \DateTime('now')->format('Y'),
            $summaryVO
        );
    }

    public function testSetCollectionKeyWithInvalidOffsetThrowsException(): void
    {
        $summaryVO = $this->createStub(SummaryVO::class);
        $accountingDTO = new AccountingDTO(
            $this->account,
            new MovementCollection([]),
            (int) new \DateTime('now')->format('Y'),
            $summaryVO
        );
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('collectionInvalidOffsetPosition');
        $accountingDTO->getMovementAcquisitionPrice(1);
    }

    // #[DataProvider('getMovementProfitPercentageProvider')]
    // public function testGetMovementProfitPercentageReturnsExpectedNumber(): void
    // {
    //     $acquisitionPrice = $this->createStub(StockPriceVO::class);
    //     $liquidationPrice = $this->createStub(StockPriceVO::class);
    //     $acquisitionExpense = $this->createStub(MoneyVO::class);
    //     $liquidationExpense = $this->createStub(MoneyVO::class);

    //     $moneyAcquisition = $this->createStub(MoneyVO::class);
    //     $moneyLiquidation = $this->createStub(MoneyVO::class);

    //     $acquisitionPrice->method('toMoney')->willReturn($moneyAcquisition);
    //     $liquidationPrice->method('toMoney')->willReturn($moneyLiquidation);

    //     $moneyAcquisition->method('add')->willReturn($moneyAcquisition);
    //     $moneyAcquisition->method('add')->with($this->anything())->willReturn($moneyAcquisition);
    //     $moneyAcquisition->method('subtract')->with($moneyAcquisition)->willReturn($moneyAcquisition);
    //     $moneyAcquisition->method('getMaxDecimals')->willReturn(2);

    //     $moneyLiquidation->method('subtract')->willReturn($moneyAcquisition);
    //     $moneyLiquidation->method('getMaxDecimals')->willReturn(2);

    //     $movement = $this->createStub(Movement::class);
    //     $movement->method('getAcquisitionPrice')->willReturn($acquisitionPrice);
    //     $movement->method('getLiquidationPrice')->willReturn($liquidationPrice);
    //     $movement->method('getAcquisitionExpenses')->willReturn($acquisitionExpense);
    //     $movement->method('getLiquidationExpenses')->willReturn($liquidationExpense);

    //     $movements = $this->createStub(MovementCollection::class);
    //     $movements->method('offsetGet')->with(0)->willReturn($movement);

    //     $summary = $this->createStub(SummaryVO::class);

    //     $accountingDTO = new AccountingDTO(
    //         $this->account,
    //         $movements,
    //         (int) (new \DateTime('now'))->format('Y'),
    //         $summary
    //     );

    //     $result = $accountingDTO->getMovementProfitPercentage(0);
    //     $this->assertInstanceOf(\Xver\MiCartera\Domain\Number\Domain\Number::class, $result);
    // }

    // public static function getMovementProfitPercentageProvider(): array
    // {
    //     return [
    //         ['0', new Movement(

    //         )],
    //     ];
    // }
}
