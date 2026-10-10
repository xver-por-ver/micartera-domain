<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;

/** @psalm-api */
final class CashDividendSummaryVO
{
    private CashDividendMoneyVO $allTimeGross;
    private CashDividendMoneyVO $allTimeExpenses;
    private CashDividendMoneyVO $displayedYearGross;
    private CashDividendMoneyVO $displayedYearExpenses;
    private ?int $yearFirstDividend = null;
    private CashDividendCollection $displayedYearDividends;

    public function __construct(
        CashDividendCollection $dividends,
        Account $account,
        private readonly int $displayedYear
    ) {
        $currency = $account->getCurrency();
        $this->allTimeGross = new CashDividendMoneyVO('0', $currency);
        $this->allTimeExpenses = new CashDividendMoneyVO('0', $currency);
        $this->displayedYearGross = new CashDividendMoneyVO('0', $currency);
        $this->displayedYearExpenses = new CashDividendMoneyVO('0', $currency);
        /** @var list<CashDividend> $displayedYearDividends */
        $displayedYearDividends = [];

        $numberOperation = new NumberOperation();
        $decimals = $currency->getDecimals();
        foreach ($dividends as $dividend) {
            $this->allTimeGross = new CashDividendMoneyVO(
                $numberOperation->add($decimals, new Number($this->allTimeGross->getValue()), new Number($dividend->getTotalDividendPayment()->getValue())),
                $currency
            );
            $this->allTimeExpenses = new CashDividendMoneyVO(
                $numberOperation->add($decimals, new Number($this->allTimeExpenses->getValue()), new Number($dividend->getExpenses()->getValue())),
                $currency
            );
            $year = (int) $dividend->getDateTimeUtc()->setTimezone($account->getTimeZone())->format('Y');
            $this->yearFirstDividend = null === $this->yearFirstDividend ? $year : min($this->yearFirstDividend, $year);

            if ($this->displayedYear === $year) {
                $this->displayedYearGross = new CashDividendMoneyVO(
                    $numberOperation->add($decimals, new Number($this->displayedYearGross->getValue()), new Number($dividend->getTotalDividendPayment()->getValue())),
                    $currency
                );
                $this->displayedYearExpenses = new CashDividendMoneyVO(
                    $numberOperation->add($decimals, new Number($this->displayedYearExpenses->getValue()), new Number($dividend->getExpenses()->getValue())),
                    $currency
                );
                $displayedYearDividends[] = $dividend;
            }
        }
        $this->displayedYearDividends = new CashDividendCollection($displayedYearDividends);
    }

    public function getDisplayedYearDividends(): CashDividendCollection
    {
        return $this->displayedYearDividends;
    }

    public function getYearFirstDividend(): ?int
    {
        return $this->yearFirstDividend;
    }

    public function getAllTimeGross(): CashDividendMoneyVO
    {
        return $this->allTimeGross;
    }

    public function getAllTimeExpenses(): CashDividendMoneyVO
    {
        return $this->allTimeExpenses;
    }

    public function getAllTimeNet(): CashDividendMoneyVO
    {
        return $this->allTimeGross->subtract($this->allTimeExpenses);
    }

    public function getDisplayedYearGross(): CashDividendMoneyVO
    {
        return $this->displayedYearGross;
    }

    public function getDisplayedYearExpenses(): CashDividendMoneyVO
    {
        return $this->displayedYearExpenses;
    }

    public function getDisplayedYearNet(): CashDividendMoneyVO
    {
        return $this->displayedYearGross->subtract($this->displayedYearExpenses);
    }
}
