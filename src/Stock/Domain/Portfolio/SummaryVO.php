<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Portfolio;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @psalm-api
 */
class SummaryVO
{
    private NumberOperation $numberOperation;
    private MovementPriceVO $totalInvestment;
    private StockProfitVO $totalStockProfitForecast;

    public function __construct(
        private readonly MovementPriceVO $totalAcquisitionsPrice,
        private readonly TransactionExpenseVO $totalAcquisitionsExpenses,
        private readonly MovementPriceVO $totalMarketPrice,
        private readonly Currency $currency
    ) {
        $this->numberOperation = new NumberOperation();
        $this->calculateTotalProfitForecastPrice();
    }

    public function getTotalAcquisitionsPrice(): MovementPriceVO
    {
        return $this->totalAcquisitionsPrice;
    }

    public function getTotalMarketsPrice(): MovementPriceVO
    {
        return $this->totalMarketPrice;
    }

    public function getTotalAcquisitionsExpenses(): TransactionExpenseVO
    {
        return $this->totalAcquisitionsExpenses;
    }

    public function getTotalProfitForecastPrice(): StockProfitVO
    {
        return $this->totalStockProfitForecast;
    }

    public function getTotalProfitForecastPercentage(): Number
    {
        $profitPercentage = $this->numberOperation->percentageDifference(
            $this->getTotalMarketsPrice()->getMaxDecimals(),
            2,
            $this->totalInvestment,
            $this->getTotalMarketsPrice()
        );

        return new Number($profitPercentage);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    private function calculateTotalProfitForecastPrice(): void
    {
        $acqExpAsMovementPrice = new MovementPriceVO($this->getTotalAcquisitionsExpenses()->getValue(), $this->getCurrency());

        $this->totalInvestment = $this->getTotalAcquisitionsPrice()->add($acqExpAsMovementPrice);

        $this->totalStockProfitForecast = new StockProfitVO(
            $this->numberOperation->subtract(
                $this->totalInvestment->getMaxDecimals(),
                $this->getTotalMarketsPrice(),
                $this->totalInvestment
            ),
            $this->getCurrency()
        );
    }
}
