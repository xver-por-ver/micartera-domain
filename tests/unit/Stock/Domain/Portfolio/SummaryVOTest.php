<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Portfolio;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @internal
 */
#[CoversClass(SummaryVO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockProfitVO::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
class SummaryVOTest extends TestCase
{
    private Currency&Stub $currency;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->currency->method('sameId')->willReturn(true);
    }

    public function testGettersReturnExpectedValues(): void
    {
        $acqPrice = new MovementPriceVO(new Number('100.00'), $this->currency);
        $acqExp = new TransactionExpenseVO(new Number('10.00'), $this->currency);
        $marketPrice = new MovementPriceVO(new Number('150.00'), $this->currency);
        $vo = new SummaryVO($acqPrice, $acqExp, $marketPrice, $this->currency);

        $this->assertSame($acqPrice, $vo->getTotalAcquisitionsPrice());
        $this->assertSame($acqExp, $vo->getTotalAcquisitionsExpenses());
        $this->assertSame($marketPrice, $vo->getTotalMarketsPrice());
        $this->assertSame($this->currency, $vo->getCurrency());
    }

    public function testTotalProfitForecastPriceAndPercentage(): void
    {
        $acqPrice = new MovementPriceVO(new Number('100.00'), $this->currency);
        $acqExp = new TransactionExpenseVO(new Number('10.00'), $this->currency);
        $marketPrice = new MovementPriceVO(new Number('150.00'), $this->currency);
        $vo = new SummaryVO($acqPrice, $acqExp, $marketPrice, $this->currency);

        $profit = $vo->getTotalProfitForecastPrice();
        $this->assertInstanceOf(StockProfitVO::class, $profit);
        $this->assertEquals('40', $profit->getValue()); // 150 - (100+10)
        $this->assertEquals('40.0000', $profit->getValueFormatted()); // 150 - (100+10)

        $percentage = $vo->getTotalProfitForecastPercentage();
        $this->assertInstanceOf(Number::class, $percentage);
    }

    public function testZeroProfit(): void
    {
        $acqPrice = new MovementPriceVO(new Number('100.00'), $this->currency);
        $acqExp = new TransactionExpenseVO(new Number('0.00'), $this->currency);
        $marketPrice = new MovementPriceVO(new Number('100.00'), $this->currency);
        $vo = new SummaryVO($acqPrice, $acqExp, $marketPrice, $this->currency);
        $profit = $vo->getTotalProfitForecastPrice();
        $this->assertEquals('0', $profit->getValue());
        $this->assertEquals('0.0000', $profit->getValueFormatted());
    }
}
