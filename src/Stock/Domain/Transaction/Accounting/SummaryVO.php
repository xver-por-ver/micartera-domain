<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @psalm-api
 */
class SummaryVO
{
    private readonly int $yearOfFirstLiquidation;
    private NumberOperation $numberOperation;
    private MovementPriceVO $allTimeInvestment;
    private MovementPriceVO $displayedYearInvestment;

    public function __construct(
        private readonly Account $account,
        public readonly ?\DateTime $dateTimeFirstLiquidationUtc,
        private readonly SummaryDTO $summaryAllTimeDTO,
        private readonly SummaryDTO $summaryDisplayedYearDTO
    ) {
        false === is_null($dateTimeFirstLiquidationUtc)
            ? $this->yearOfFirstLiquidation = (int) $dateTimeFirstLiquidationUtc->setTimezone($this->account->getTimeZone())
                ->format('Y')
            : $this->yearOfFirstLiquidation = (int) new \DateTime('now', $this->account->getTimeZone())
                ->format('Y')
        ;
        $this->numberOperation = new NumberOperation();
        $this->calculateInvestments();
    }

    public function getYearFirstLiquidation(): int
    {
        return $this->yearOfFirstLiquidation;
    }

    public function getAllTimeAcquisitionsPrice(): MovementPriceVO
    {
        return $this->summaryAllTimeDTO->acquisitionsPrice;
    }

    public function getAllTimeAcquisitionsExpenses(): TransactionExpenseVO
    {
        return $this->summaryAllTimeDTO->acquisitionsExpenses;
    }

    public function getAllTimeLiquidationsPrice(): MovementPriceVO
    {
        return $this->summaryAllTimeDTO->liquidationsPrice;
    }

    public function getAllTimeLiquidationsExpenses(): TransactionExpenseVO
    {
        return $this->summaryAllTimeDTO->liquidationsExpenses;
    }

    public function getAllTimeProfitPrice(): MoneyVO
    {
        $profit = new StockProfitVO(
            $this->numberOperation->subtract(
                $this->allTimeInvestment->getMaxDecimals(),
                $this->getAllTimeLiquidationsPrice(),
                $this->allTimeInvestment
            ),
            $this->account->getCurrency()
        );

        return new MoneyVO(
            $this->numberOperation->round(
                $this->account->getCurrency()->getDecimals(),
                $profit,
                \RoundingMode::TowardsZero
            ),
            $this->account->getCurrency()
        );
    }

    public function getAllTimeProfitPercentage(): Number
    {
        return $this->allTimeInvestment->percentageDifference(
            $this->getAllTimeLiquidationsPrice(),
            2
        );
    }

    public function getDisplayedYearAcquisitionsPrice(): MovementPriceVO
    {
        return $this->summaryDisplayedYearDTO->acquisitionsPrice;
    }

    public function getDisplayedYearAcquisitionsExpenses(): TransactionExpenseVO
    {
        return $this->summaryDisplayedYearDTO->acquisitionsExpenses;
    }

    public function getDisplayedYearLiquidationsPrice(): MovementPriceVO
    {
        return $this->summaryDisplayedYearDTO->liquidationsPrice;
    }

    public function getDisplayedYearLiquidationsExpenses(): TransactionExpenseVO
    {
        return $this->summaryDisplayedYearDTO->liquidationsExpenses;
    }

    public function getDisplayedYearProfitPrice(): MoneyVO
    {
        $profit = new StockProfitVO(
            $this->numberOperation->subtract(
                $this->displayedYearInvestment->getMaxDecimals(),
                $this->getDisplayedYearLiquidationsPrice(),
                $this->displayedYearInvestment
            ),
            $this->account->getCurrency()
        );

        return new MoneyVO(
            $this->numberOperation->round(
                $this->account->getCurrency()->getDecimals(),
                $profit,
                \RoundingMode::TowardsZero
            ),
            $this->account->getCurrency()
        );
    }

    public function getDisplayedYearProfitPercentage(): Number
    {
        return $this->displayedYearInvestment->percentageDifference(
            $this->getDisplayedYearLiquidationsPrice(),
            2
        );
    }

    private function calculateInvestments(): void
    {
        $allTimeExpensesAsMovementPrice = new MovementPriceVO(
            $this->getAllTimeAcquisitionsExpenses()->add($this->getAllTimeLiquidationsExpenses())->getValue(),
            $this->account->getCurrency()
        );

        $this->allTimeInvestment = $this->getAllTimeAcquisitionsPrice()->add($allTimeExpensesAsMovementPrice);

        $displayeyYearExpensesAsMovementPrice = new MovementPriceVO(
            $this->getDisplayedYearAcquisitionsExpenses()->add($this->getDisplayedYearLiquidationsExpenses())->getValue(),
            $this->account->getCurrency()
        );

        $this->displayedYearInvestment = $this->getDisplayedYearAcquisitionsPrice()->add($displayeyYearExpensesAsMovementPrice);
    }
}
