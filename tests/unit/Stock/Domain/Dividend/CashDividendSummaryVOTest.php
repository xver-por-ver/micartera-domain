<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSummaryVO;

#[CoversClass(CashDividendSummaryVO::class)]
#[UsesClass(CashDividendCollection::class)]
#[UsesClass(CashDividendMoneyVO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
class CashDividendSummaryVOTest extends TestCase
{
    public function testSummarizesAllTimeAndDisplayedYearDividendsInAccountTimezone(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $currency->method('sameId')->willReturn(true);
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        $account->method('getTimeZone')->willReturn(new \DateTimeZone('Europe/Madrid'));

        $prior = $this->dividend($currency, '2023-12-31 23:30:00', '10', '1');
        $current = $this->dividend($currency, '2024-06-01 12:00:00', '20', '2');
        $collection = new CashDividendCollection([$prior, $current]);
        $summary = new CashDividendSummaryVO($collection, $account, 2024);

        self::assertSame(2024, $summary->getYearFirstDividend());
        self::assertCount(2, $summary->getDisplayedYearDividends());
        self::assertSame('30', $summary->getAllTimeGross()->getValue());
        self::assertSame('3', $summary->getAllTimeExpenses()->getValue());
        self::assertSame('27', $summary->getAllTimeNet()->getValue());
        self::assertSame('30', $summary->getDisplayedYearGross()->getValue());
        self::assertSame('3', $summary->getDisplayedYearExpenses()->getValue());
        self::assertSame('27', $summary->getDisplayedYearNet()->getValue());
    }

    public function testEmptyDividendsHaveZeroTotalsAndNoFirstYear(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('getDecimals')->willReturn(2);
        $currency->method('sameId')->willReturn(true);
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        $account->method('getTimeZone')->willReturn(new \DateTimeZone('UTC'));

        $summary = new CashDividendSummaryVO(new CashDividendCollection([]), $account, 2024);

        self::assertNull($summary->getYearFirstDividend());
        self::assertCount(0, $summary->getDisplayedYearDividends());
        self::assertSame('0', $summary->getAllTimeGross()->getValue());
        self::assertSame('0', $summary->getDisplayedYearNet()->getValue());
    }

    private function dividend(Currency $currency, string $date, string $gross, string $expenses): CashDividend
    {
        $dividend = $this->createStub(CashDividend::class);
        $dividend->method('getDateTimeUtc')->willReturn(new \DateTime($date, new \DateTimeZone('UTC')));
        $dividend->method('getTotalDividendPayment')->willReturn(new CashDividendMoneyVO($gross, $currency));
        $dividend->method('getExpenses')->willReturn(new CashDividendMoneyVO($expenses, $currency));

        return $dividend;
    }
}
