<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

abstract class TransactionAbstract implements EntityInterface
{
    protected Uuid $id;

    protected TransactionAmountActionableVO $amountActionable;

    protected TransactionExpenseVO $expensesUnaccountedFor;

    public function __construct(
        protected readonly Stock $stock,
        protected readonly StockPriceVO $price,
        protected readonly \DateTime $datetimeutc,
        protected readonly TransactionAmountVO $amount,
        protected readonly TransactionExpenseVO $expenses,
        protected readonly Account $account
    ) {
        $this->validateNoTransactionDateInFuture();
        $this->validateStockAndStockPriceSameCurrency();
        $this->validateStockAndExpensesSameCurrency();
        $this->amountActionable = new TransactionAmountActionableVO($this->amount->getValue());
        $this->expensesUnaccountedFor = $this->expenses;
        $this->generateId();
    }

    private function validateNoTransactionDateInFuture(): void // TODO: return class instance
    {
        if ($this->datetimeutc > new \DateTime('now', new \DateTimeZone('UTC'))) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'futureDateNotAllowed',
                    [],
                    'MiCarteraDomain'
                ),
                'transaction.datetimeutc'
            );
        }
    }

    private function validateStockAndStockPriceSameCurrency(): void // TODO: return class instance
    {
        if (false === $this->getCurrency()->sameId($this->getPrice()->getCurrency())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'otherCurrencyExpected',
                    ['received' => $this->getPrice()->getCurrency()->getIso3(), 'expected' => $this->getCurrency()->getIso3()],
                    'MiCarteraDomain'
                ),
                'transaction.price'
            );
        }
    }

    private function validateStockAndExpensesSameCurrency(): void // TODO: return class instance
    {
        if (false === $this->expenses->getCurrency()->sameId($this->getCurrency())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'otherCurrencyExpected',
                    ['received' => $this->expenses->getCurrency()->getIso3(), 'expected' => $this->getCurrency()->getIso3()],
                    'MiCarteraDomain'
                ),
                'transaction.expenses'
            );
        }
    }

    private function generateId(): void
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    #[\Override]
    abstract public function sameId(EntityInterface $otherEntity): bool;

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function getDateTimeUtc(): \DateTime
    {
        return new \DateTime($this->datetimeutc->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
    }

    public function getAmount(): TransactionAmountVO
    {
        return $this->amount;
    }

    public function getAmountActionable(): TransactionAmountActionableVO
    {
        return $this->amountActionable;
    }

    public function getCurrency(): Currency
    {
        return $this->getStock()->getCurrency();
    }

    public function getPrice(): StockPriceVO
    {
        return new StockPriceVO(
            $this->price->getValue(),
            $this->getCurrency()
        );
    }

    public function getExpenses(): TransactionExpenseVO
    {
        return new TransactionExpenseVO(
            $this->expenses->getValue(),
            $this->getCurrency()
        );
    }

    protected function decreaseExpensesUnaccountedFor(TransactionExpenseVO $delta): void
    {
        $expensesUnaccountedFor = $this->getExpensesUnaccountedFor();
        if ($expensesUnaccountedFor->smaller($delta)) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'InvalidMovementExpensesAmount',
                    [],
                    'MiCarteraDomain'
                ),
                'transaction.expenses'
            );
        }

        $numberOperation = new NumberOperation();

        $this->expensesUnaccountedFor = $expensesUnaccountedFor->subtract($delta);
    }

    protected function increaseExpensesUnaccountedFor(TransactionExpenseVO $delta): void
    {
        $expensesUnaccountedFor = $this->getExpensesUnaccountedFor();

        $numberOperation = new NumberOperation();

        $expensesUnaccountedFor = new TransactionExpenseVO(
            $numberOperation->add(
                $expensesUnaccountedFor->getMaxDecimals(),
                $expensesUnaccountedFor,
                $delta
            ),
            $this->getCurrency()
        );

        if ($expensesUnaccountedFor->greater($this->getExpenses())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'InvalidMovementExpensesAmount',
                    [],
                    'MiCarteraDomain'
                ),
                'transaction.expenses'
            );
        }
        $this->expensesUnaccountedFor = $expensesUnaccountedFor;
    }

    public function getExpensesUnaccountedFor(): TransactionExpenseVO
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        return isset($this->expensesUnaccountedFor)
            ? new TransactionExpenseVO(
                $this->expensesUnaccountedFor->getValue(),
                $this->getStock()->getCurrency()
            )
            : new TransactionExpenseVO('0', $this->getStock()->getCurrency());
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    protected function increaseAmountActionable(TransactionAmountActionableVO $delta): void
    {
        $numberOperation = new NumberOperation();

        $newAmountActionable = new TransactionAmountActionableVO(
            $numberOperation->add(
                $this->getAmountActionable()->getMaxDecimals(),
                $this->getAmountActionable(),
                $delta
            )
        );
        if ($numberOperation->smaller($this->getAmount()->getMaxDecimals(), $this->getAmount(), $delta)) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'MovementAmountNotWithinAllowedLimits',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.amount'
            );
        }

        $this->amountActionable = $newAmountActionable;
    }

    protected function decreaseAmountActionable(TransactionAmountActionableVO $delta): void
    {
        if ($this->getAmountActionable()->smaller($delta)) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'MovementAmountNotWithinAllowedLimits',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.amount'
            );
        }

        $numberOperation = new NumberOperation();

        $this->amountActionable = new TransactionAmountActionableVO(
            $numberOperation->subtract(
                $this->getAmountActionable()->getMaxDecimals(),
                $this->getAmountActionable(),
                $delta
            )
        );
    }

    abstract protected function persistCreate(): void;

    protected function fiFoCriteriaInstance(
        AcquisitionPersistenceInterface $acquisitionPersistence,
        LiquidationPersistenceInterface $liquidationPersistence,
        Accounting\MovementPersistenceInterface $movementPersistence
    ): FiFoCriteria {
        return new FiFoCriteria($acquisitionPersistence, $liquidationPersistence, $movementPersistence);
    }
}
