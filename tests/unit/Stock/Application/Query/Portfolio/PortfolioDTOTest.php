<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query\Portfolio;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Query\Portfolio\PortfolioDTO;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(PortfolioDTO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(StockProfitVO::class)]
#[UsesClass(MovementPriceVO::class)]
#[UsesClass(NumberOperation::class)]
class PortfolioDTOTest extends TestCase
{
    public function testPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);

        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);

        $price = $this->createStub(StockPriceVO::class);
        $price->method('getValue')->willReturn('0');

        $stock = $this->createStub(Stock::class);
        $stock->method('getPrice')->willReturn($price);

        $expensesUnaccountedFor = $this->createStub(TransactionExpenseVO::class);
        $expensesUnaccountedFor->method('getValue')->willReturn('0');

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getPrice')->willReturn($price);
        $acquisition->method('getStock')->willReturn($stock);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn($expensesUnaccountedFor);

        $outstandingPositionsCollection = $this->createStub(AcquisitionCollection::class);
        $outstandingPositionsCollection->method('offsetGet')->willReturn(
            $acquisition
        );

        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->assertNotNull($portfolio->getPositionAcquisitionExpenses(0));
        $this->assertNotNull($portfolio->getPositionAcquisitionPrice(0));
        $this->assertNotNull($portfolio->getPositionMarketPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPrice(0));
        $this->assertNotNull($portfolio->getPositionProfitPercentage(0));
    }

    public function testEmptyPortfolio(): void
    {
        $currency = $this->createStub(Currency::class);

        /** @var Account&Stub */
        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);
        $outstandingPositionsCollection = new AcquisitionCollection([]);

        /** @var Stub&SummaryVO */
        $summary = $this->createStub(SummaryVO::class);
        $portfolio = new PortfolioDTO(
            $account,
            $outstandingPositionsCollection,
            $summary
        );
        $this->assertSame($account, $portfolio->getAccount());
        $this->assertSame($outstandingPositionsCollection, $portfolio->getCollection());
        $this->assertSame($summary, $portfolio->getSummary());
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('collectionInvalidOffsetPosition');
        $this->assertNull($portfolio->getPositionAcquisitionExpenses(0));
    }

    #[DataProvider('percentageProvider')]
    public function testGetPositionProfitPercentageReturnsExpectedNumber(
        string $purchasePrice,
        string $currentPrice,
        string $transAmount,
        string $expenses,
        string $percResult
    ): void {
        $currency = $this->createStub(Currency::class);

        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);

        $price = $this->createStub(StockPriceVO::class);
        $price->method('getValue')->willReturn($purchasePrice);
        $price->method('getMaxDecimals')->willReturn(4);

        $marketPrice = $this->createStub(StockPriceVO::class);
        $marketPrice->method('getValue')->willReturn($currentPrice);
        $marketPrice->method('getMaxDecimals')->willReturn(4);

        $stock = $this->createStub(Stock::class);
        $stock->method('getPrice')->willReturn($marketPrice);

        $amount = $this->createStub(TransactionAmountActionableVO::class);
        $amount->method('getValue')->willReturn($transAmount);

        $expensesUnaccountedFor = $this->createStub(TransactionExpenseVO::class);
        $expensesUnaccountedFor->method('getValue')->willReturn($expenses);

        $acquisition = $this->createStub(Acquisition::class);
        $acquisition->method('getPrice')->willReturn($price);
        $acquisition->method('getStock')->willReturn($stock);
        $acquisition->method('getAmountActionable')->willReturn($amount);
        $acquisition->method('getExpensesUnaccountedFor')->willReturn($expensesUnaccountedFor);

        $collection = $this->createStub(AcquisitionCollection::class);
        $collection->method('offsetGet')->willReturn($acquisition);

        $summary = $this->createStub(SummaryVO::class);

        $portfolio = new PortfolioDTO(
            $account,
            $collection,
            $summary
        );

        $result = $portfolio->getPositionProfitPercentage(0);
        $this->assertInstanceOf(Number::class, $result);
        $this->assertSame($percResult, $result->getValue());
    }

    public static function percentageProvider(): array
    {
        // string $purchasePrice, string $currentPrice, string $transAmount, string $expenses, string $percResult
        return [
            ['100', '100', '1', '0', '0'],
            ['100', '50', '1', '0', '-50'],
            ['50', '100', '1', '0', '100'],
            ['100', '200', '1', '10', '81.82'],
            ['200', '100', '1', '10', '-52.38'],
            ['61.6634', '75.35', '95', '21.72', '21.74'],
            ['0.428', '0.321', '14000', '10', '-25.12'],
            ['3.4561', '4.3321', '10', '5.32', '8.63'],
            ['2.511', '2.22', '2330', '21.70', '-11.92'],
        ];
    }
}
