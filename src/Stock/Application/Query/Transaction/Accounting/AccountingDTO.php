<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template-extends EntityCollectionQueryResponse<Movement>
 */
final class AccountingDTO extends EntityCollectionQueryResponse
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Movement $movement;
    private ?int $offset = null;

    public function __construct(
        private readonly Account $account,
        MovementCollection $accountingMovementsCollection,
        private readonly int $displayYear,
        private readonly SummaryVO $summary,
        int $limit = 0,
        public readonly int $page = 0,
        ?CashDividendSummaryVO $cashDividendSummary = null
    ) {
        parent::__construct($accountingMovementsCollection, $limit, $page);
        $this->cashDividendSummary = $cashDividendSummary ?? new CashDividendSummaryVO(new CashDividendCollection([]), $account, $displayYear);
    }

    private readonly CashDividendSummaryVO $cashDividendSummary;

    public function getCashDividendSummary(): CashDividendSummaryVO
    {
        return $this->cashDividendSummary;
    }

    public function getAllTimeCombinedNetResult(): MoneyVO
    {
        return $this->combinedNetResult(
            $this->summary->getAllTimeProfitPrice(),
            $this->cashDividendSummary->getAllTimeNet()
        );
    }

    public function getDisplayedYearCombinedNetResult(): MoneyVO
    {
        return $this->combinedNetResult(
            $this->summary->getDisplayedYearProfitPrice(),
            $this->cashDividendSummary->getDisplayedYearNet()
        );
    }

    private function combinedNetResult(MoneyVO $realizedProfit, MoneyVO $netDividends): MoneyVO
    {
        $currency = $this->account->getCurrency();
        $amount = new NumberOperation()->add(
            $currency->getDecimals(),
            new Number($realizedProfit->getValue()),
            new Number($netDividends->getValue())
        );

        return new MoneyVO($amount, $currency);
    }

    public function getYearFirstOperation(): int
    {
        $firstDividendYear = $this->cashDividendSummary->getYearFirstDividend();

        return null === $firstDividendYear
            ? $this->summary->getYearFirstLiquidation()
            : min($this->summary->getYearFirstLiquidation(), $firstDividendYear);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSummary(): SummaryVO
    {
        return $this->summary;
    }

    public function getCurrentYear(): int
    {
        return (int) new \DateTime('now', $this->account->getTimeZone())->format('Y');
    }

    public function getDisplayedYear(): int
    {
        return $this->displayYear;
    }

    public function getMovementAcquisitionPrice(int $offset): MovementPriceVO
    {
        $this->setCollectionKey($offset);

        return $this->movement->getAcquisitionPrice();
    }

    public function getMovementLiquidationPrice(int $offset): MovementPriceVO
    {
        $this->setCollectionKey($offset);

        return $this->movement->getLiquidationPrice();
    }

    public function getMovementAcquisitionExpense(int $offset): TransactionExpenseVO
    {
        $this->setCollectionKey($offset);

        return $this->movement->getAcquisitionExpenses();
    }

    public function getMovementLiquidationExpense(int $offset): TransactionExpenseVO
    {
        $this->setCollectionKey($offset);

        return $this->movement->getLiquidationExpenses();
    }

    public function getMovementProfitPrice(int $offset): MoneyVO
    {
        $numberOperation = new NumberOperation();

        $totalMovementInvestment = $this->calculateTotalMovementInvestment($offset, $numberOperation);

        $movementProfit = new StockProfitVO(
            $numberOperation->subtract(
                $totalMovementInvestment->getMaxDecimals(),
                $this->getMovementLiquidationPrice($offset),
                $totalMovementInvestment
            ),
            $this->getAccount()->getCurrency()
        );

        return new MoneyVO(
            $numberOperation->round(
                $this->getAccount()->getCurrency()->getDecimals(),
                $movementProfit,
                \RoundingMode::TowardsZero
            ),
            $this->getAccount()->getCurrency()
        );
    }

    public function getMovementProfitPercentage(int $offset): Number
    {
        $numberOperation = new NumberOperation();

        $totalMovementInvestment = $this->calculateTotalMovementInvestment($offset, $numberOperation);

        $profitPercentage = new Number(
            $numberOperation->percentageDifference(
                $totalMovementInvestment->getMaxDecimals(),
                2,
                $totalMovementInvestment,
                $this->getMovementLiquidationPrice($offset)
            )
        );

        return new Number(
            $numberOperation->round(
                2,
                $profitPercentage,
                \RoundingMode::HalfAwayFromZero
            )
        );
    }

    private function calculateTotalMovementInvestment(int $offset, NumberOperation $numberOperation): StockProfitVO
    {
        $movementExpensesAsStockProfit = new StockProfitVO(
            $numberOperation->add(
                $this->getAccount()->getCurrency()->getDecimals(),
                $this->getMovementAcquisitionExpense($offset),
                $this->getMovementLiquidationExpense($offset)
            ),
            $this->getAccount()->getCurrency()
        );

        return new StockProfitVO(
            $numberOperation->add(
                $movementExpensesAsStockProfit->getMaxDecimals(),
                $this->getMovementAcquisitionPrice($offset),
                $movementExpensesAsStockProfit
            ),
            $this->getAccount()->getCurrency()
        );
    }

    private function setCollectionKey(int $offset): void
    {
        if ($this->offset !== $offset) {
            $movement = $this->getCollection()->offsetGet($offset);
            if (is_null($movement)) {
                throw new DomainViolationException(
                    new TranslatableMessage(
                        'collectionInvalidOffsetPosition',
                        [],
                        'MiCarteraDomain'
                    )
                );
            }
            $this->movement = $movement;
            $this->offset = $offset;
        }
    }
}
