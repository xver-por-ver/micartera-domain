<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Query\Portfolio;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockProfitVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template-extends EntityCollectionQueryResponse<Acquisition>
 */
final class PortfolioDTO extends EntityCollectionQueryResponse
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private Acquisition $position;
    private ?int $offset = null;
    private NumberOperation $numberOperation;

    public function __construct(
        private readonly Account $account,
        AcquisitionCollection $outstandingPositionsCollection,
        private readonly SummaryVO $summary,
        int $limit = 0,
        public readonly int $page = 0
    ) {
        parent::__construct($outstandingPositionsCollection, $limit, $page);
        $this->numberOperation = new NumberOperation();
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getSummary(): SummaryVO
    {
        return $this->summary;
    }

    public function getPositionAcquisitionPrice(int $offset): StockProfitVO
    {
        $this->setCollectionKey($offset);

        return $this->calculateTotalPrice($this->position->getPrice());
    }

    public function getPositionMarketPrice(int $offset): StockProfitVO
    {
        $this->setCollectionKey($offset);

        return $this->calculateTotalPrice($this->position->getStock()->getPrice());
    }

    private function calculateTotalPrice(StockPriceVO $stockPrice): StockProfitVO
    {
        $totalPrice = $this->numberOperation->multiply(
            $stockPrice->getMaxDecimals(),
            $stockPrice,
            $this->position->getAmountActionable(),
            \RoundingMode::AwayFromZero
        );

        return new StockProfitVO(
            $totalPrice,
            $this->getAccount()->getCurrency()
        );
    }

    public function getPositionAcquisitionExpenses(int $offset): MoneyVO
    {
        $this->setCollectionKey($offset);

        return $this->position->getExpensesUnaccountedFor();
    }

    public function getPositionProfitPrice(int $offset): StockProfitVO
    {
        $acqExpAsStockPrice = new StockProfitVO(
            $this->getPositionAcquisitionExpenses($offset)->getValue(),
            $this->account->getCurrency()
        );

        $totalInvestment = new MovementPriceVO(
            $this->numberOperation->add(
                $acqExpAsStockPrice->getMaxDecimals(),
                $this->getPositionAcquisitionPrice($offset),
                $acqExpAsStockPrice
            ),
            $this->account->getCurrency()
        );

        return new StockProfitVO(
            $this->numberOperation->subtract(
                $totalInvestment->getMaxDecimals(),
                $this->getPositionMarketPrice($offset),
                $totalInvestment
            ),
            $this->account->getCurrency()
        );
    }

    public function getPositionProfitPercentage(int $offset): Number
    {
        $positionInvestment = $this->calculatePositionTotalInvestment($offset);
        $positionMarketPrice = $this->getPositionMarketPrice($offset);
        $profitPercentage = $this->numberOperation->percentageDifference(
            $positionMarketPrice->getMaxDecimals(),
            2,
            $positionInvestment,
            $positionMarketPrice,
        );

        return new Number($profitPercentage);
    }

    private function calculatePositionTotalInvestment(int $offset): StockProfitVO
    {
        $positionExpensesAsStockProfit = new StockProfitVO(
            $this->getPositionAcquisitionExpenses($offset)->getValue(),
            $this->getAccount()->getCurrency()
        );

        return new StockProfitVO(
            $this->numberOperation->add(
                $this->getPositionAcquisitionPrice($offset)->getMaxDecimals(),
                $this->getPositionAcquisitionPrice($offset),
                $positionExpensesAsStockProfit
            ),
            $this->getAccount()->getCurrency()
        );
    }

    /**
     * @throws DomainViolationException
     */
    private function setCollectionKey(int $offset): void
    {
        if ($this->offset !== $offset) {
            $position = $this->getCollection()->offsetGet($offset);
            if (is_null($position)) {
                throw new DomainViolationException(
                    new TranslatableMessage(
                        'collectionInvalidOffsetPosition',
                        [],
                        'MiCarteraDomain'
                    )
                );
            }
            $this->position = $position;
            $this->offset = $offset;
        }
    }
}
