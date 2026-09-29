<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Movement implements EntityInterface
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private TransactionAmountVO $amount;
    private MovementPriceVO $acquisitionPrice;
    private MovementPriceVO $liquidationPrice;
    private TransactionExpenseVO $acquisitionExpenses;
    private TransactionExpenseVO $liquidationExpenses;

    public function __construct(
        MovementPersistenceInterface $movementPersistence,
        AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly Acquisition $acquisition,
        private readonly Liquidation $liquidation
    ) {
        $this
            ->validateAcquisitionLiquidationSameStock()
            ->validateLiquidationDateAfterAcquisitionDate()
            ->setAmount()
        ;
        $this->setAcquisitionPrice();
        $this->setLiquidationPrice();
        $this->setAcquisitionExpenses();
        $this->setLiquidationExpenses();
        $this->acquisition->accountMovement($acquisitionPersistence, $this);
        $this->liquidation->accountMovement($this);
        $movementPersistence->persist($this);
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Movement) {
            throw new \InvalidArgumentException();
        }

        return
            $this->acquisition->sameId($otherEntity->getAcquisition())
            && $this->liquidation->sameId($otherEntity->getLiquidation());
    }

    public function getAcquisition(): Acquisition
    {
        return $this->acquisition;
    }

    public function getLiquidation(): Liquidation
    {
        return $this->liquidation;
    }

    public function getAmount(): TransactionAmountVO
    {
        return $this->amount;
    }

    private function setAmount(): self
    {
        if ($this->acquisition->getAmountActionable()->same(new TransactionAmountActionableVO('0'))) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'accountingMovementAcquisitionHasNoAmountOutstanding',
                    [],
                    'MiCarteraDomain'
                ),
                'movement.amount'
            );
        }
        if ($this->liquidation->getAmountActionable()->same(new TransactionAmountActionableVO('0'))) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'accountingMovementLiquidationHasNoAmountRemaining',
                    [],
                    'MiCarteraDomain'
                ),
                'movement.amount'
            );
        }
        $this->amount
            = $this->acquisition->getAmountActionable()->smaller($this->liquidation->getAmountActionable())
            ? new TransactionAmountVO($this->acquisition->getAmountActionable()->getValue())
            : new TransactionAmountVO($this->liquidation->getAmountActionable()->getValue());

        return $this;
    }

    private function setAcquisitionPrice(): self
    {
        $this->acquisitionPrice = $this->calculateTotalPrice($this->acquisition->getPrice());

        return $this;
    }

    private function setLiquidationPrice(): self
    {
        $this->liquidationPrice = $this->calculateTotalPrice($this->liquidation->getPrice());

        return $this;
    }

    private function calculateTotalPrice(StockPriceVO $stockPrice): MovementPriceVO
    {
        $numberOperation = new NumberOperation();
        $totalPrice = $numberOperation->multiply(
            $stockPrice->getCurrency()->getDecimals(),
            $stockPrice,
            $this->getAmount(),
            \RoundingMode::AwayFromZero
        );

        return new MovementPriceVO($totalPrice, $stockPrice->getCurrency());
    }

    private function setAcquisitionExpenses(): void
    {
        $this->acquisitionExpenses = (
            $this->getAmount()->same($this->getAcquisition()->getAmountActionable())
            ? $this->getAcquisition()->getExpensesUnaccountedFor()
            : $this->calculateApplicableExpenses($this->getAcquisition()->getExpensesUnaccountedFor(), $this->getAcquisition()->getAmountActionable())
        );
    }

    private function setLiquidationExpenses(): void
    {
        $this->liquidationExpenses
            = $this->getAmount()->same($this->getLiquidation()->getAmountActionable())
            ? $this->getLiquidation()->getExpensesUnaccountedFor()
            : $this->calculateApplicableExpenses($this->getLiquidation()->getExpensesUnaccountedFor(), $this->getLiquidation()->getAmountActionable());
    }

    private function calculateApplicableExpenses(TransactionExpenseVO $unaccountedExpenses, TransactionAmountActionableVO $actionableAmount): TransactionExpenseVO
    {
        $numberOperation = new NumberOperation();
        $ratio = $numberOperation->divide(
            Number::DECIMALS_MAX,
            $this->getAmount(),
            $actionableAmount,
            \RoundingMode::TowardsZero
        );
        $applicableExpenses = $numberOperation->multiply(
            $unaccountedExpenses->getCurrency()->getDecimals(),
            $unaccountedExpenses,
            new Number($ratio),
            \RoundingMode::TowardsZero
        );

        return new TransactionExpenseVO($applicableExpenses, $unaccountedExpenses->getCurrency());
    }

    public function getAcquisitionPrice(): MovementPriceVO
    {
        return new MovementPriceVO(
            $this->acquisitionPrice->getValue(),
            $this->getAcquisition()->getCurrency()
        );
    }

    public function getLiquidationPrice(): MovementPriceVO
    {
        return new MovementPriceVO(
            $this->liquidationPrice->getValue(),
            $this->getAcquisition()->getCurrency()
        );
    }

    public function getAcquisitionExpenses(): TransactionExpenseVO
    {
        return new TransactionExpenseVO(
            $this->acquisitionExpenses->getValue(),
            $this->getAcquisition()->getCurrency()
        );
    }

    public function getLiquidationExpenses(): TransactionExpenseVO
    {
        return new TransactionExpenseVO(
            $this->liquidationExpenses->getValue(),
            $this->getAcquisition()->getCurrency()
        );
    }

    private function validateAcquisitionLiquidationSameStock(): self
    {
        if (false === $this->acquisition->getStock()->sameId($this->liquidation->getStock())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transactionAssertStock',
                    [],
                    'MiCarteraDomain'
                ),
                'stock.code'
            );
        }

        return $this;
    }

    private function validateLiquidationDateAfterAcquisitionDate(): self
    {
        if ($this->acquisition->getDateTimeUtc() >= $this->liquidation->getDateTimeUtc()) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'accountingMovementAssertDateTime',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.datetimeutc'
            );
        }

        return $this;
    }
}
