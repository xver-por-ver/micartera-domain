<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryDTO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @internal
 */
#[CoversClass(SummaryVO::class)]
#[UsesClass(SummaryDTO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(StockProfitVO::class)]
class SummaryVOTest extends TestCase
{
    private SummaryDTO $dto;
    private Account&Stub $account;
    private \DateTimeZone $tz;
    private Currency $currency;

    public function setUp(): void
    {
        $this->currency = $this->createConfiguredStub(Currency::class, [
            'getDecimals' => 2,
            'sameId' => true,
        ]);
        $movementPriceVO = new MovementPriceVO('0', $this->currency);
        $transactionExpenseVO = new TransactionExpenseVO('0', $this->currency);
        $this->dto = new SummaryDTO($movementPriceVO, $transactionExpenseVO, $movementPriceVO, $transactionExpenseVO);

        $this->tz = new \DateTimeZone('Europe/Madrid');
        $this->account = $this->createStub(Account::class);
        $this->account->method('getTimeZone')->willReturn($this->tz);
        $this->account->method('getCurrency')->willReturn($this->currency);
    }

    public function testYearOfFirstLiquidationWithDate(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $summary = new SummaryVO($this->account, $date, $this->dto, $this->dto);

        $this->assertSame((int) $date->format('Y'), $summary->getYearFirstLiquidation());
    }

    public function testYearOfFirstLiquidationWithNullDate(): void
    {
        $summary = new SummaryVO($this->account, null, $this->dto, $this->dto);

        $this->assertSame((int) new \DateTime('now', $this->tz)->format('Y'), $summary->getYearFirstLiquidation());
    }

    public function testAllTimeAndDisplayedYearMethods(): void
    {
        $allTimeAcquisitionPrice = new MovementPriceVO('111.1111', $this->currency);
        $allTimeAcquisitionExpenses = new TransactionExpenseVO('11.11', $this->currency);
        $allTimeLiquidationPrice = new MovementPriceVO('222.2277', $this->currency);
        $allTimeLiquidationExpenses = new TransactionExpenseVO('22.22', $this->currency);
        $allTimeProfitPrice = new MoneyVO('77.78', $this->currency);
        $allTimeProfitPercentage = new Number('53.85');

        $displayedYearAcquisitionPrice = new MovementPriceVO('11.1111', $this->currency);
        $displayedYearAcquisitionExpenses = new TransactionExpenseVO('1.11', $this->currency);
        $displayedYearLiquidationPrice = new MovementPriceVO('22.2277', $this->currency);
        $displayedYearLiquidationExpenses = new TransactionExpenseVO('2.22', $this->currency);
        $displayedYearProfitPrice = new MoneyVO('7.78', $this->currency);
        $displayedYearProfitPercentage = new Number('53.92');

        $summaryAllTimeDTO = new SummaryDTO(
            $allTimeAcquisitionPrice,
            $allTimeAcquisitionExpenses,
            $allTimeLiquidationPrice,
            $allTimeLiquidationExpenses
        );

        $summaryDisplayedYearDTO = new SummaryDTO(
            $displayedYearAcquisitionPrice,
            $displayedYearAcquisitionExpenses,
            $displayedYearLiquidationPrice,
            $displayedYearLiquidationExpenses
        );
        $summary = new SummaryVO($this->account, null, $summaryAllTimeDTO, $summaryDisplayedYearDTO);

        $this->assertEquals($allTimeAcquisitionPrice, $summary->getAllTimeAcquisitionsPrice());
        $this->assertEquals($allTimeAcquisitionExpenses, $summary->getAllTimeAcquisitionsExpenses());
        $this->assertEquals($allTimeLiquidationPrice, $summary->getAllTimeLiquidationsPrice());
        $this->assertEquals($allTimeLiquidationExpenses, $summary->getAllTimeLiquidationsExpenses());
        $this->assertEquals($allTimeProfitPrice, $summary->getAllTimeProfitPrice());
        $this->assertEquals($allTimeProfitPercentage, $summary->getAllTimeProfitPercentage());

        $this->assertEquals($displayedYearAcquisitionPrice, $summary->getDisplayedYearAcquisitionsPrice());
        $this->assertEquals($displayedYearAcquisitionExpenses, $summary->getDisplayedYearAcquisitionsExpenses());
        $this->assertEquals($displayedYearLiquidationPrice, $summary->getDisplayedYearLiquidationsPrice());
        $this->assertEquals($displayedYearLiquidationExpenses, $summary->getDisplayedYearLiquidationsExpenses());
        $this->assertEquals($displayedYearProfitPrice, $summary->getDisplayedYearProfitPrice());
        $this->assertEquals($displayedYearProfitPercentage, $summary->getDisplayedYearProfitPercentage());
    }
}
