<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/** @psalm-api */
class CashDividend implements EntityInterface
{
    private Uuid $id;

    private CashDividendAmountVO $amount;

    private CashDividendMoneyVO $totalDividendPayment;

    public function __construct(
        private readonly CashDividendPersistenceInterface $cashDividendPersistence,
        private readonly AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly LiquidationPersistenceInterface $liquidationPersistence,
        private readonly Stock $stock,
        private readonly Account $account,
        private readonly \DateTime $datetimeutc,
        private CashDividendMoneyVO $dividendPerShare,
        private CashDividendMoneyVO $expenses
    ) {
        if ($datetimeutc > new \DateTime('now', new \DateTimeZone('UTC'))) {
            throw new DomainViolationException(new TranslatableMessage('futureDateNotAllowed', [], 'MiCarteraDomain'), 'cashDividend.datetimeutc');
        }
        if (false === $this->stock->getCurrency()->sameId($this->account->getCurrency())) {
            throw new DomainViolationException(new TranslatableMessage('otherCurrencyExpected', [], 'MiCarteraDomain'), 'cashDividend.currency');
        }
        foreach ([$dividendPerShare, $expenses] as $money) {
            if (false === $money->getCurrency()->sameId($this->account->getCurrency())) {
                throw new DomainViolationException(new TranslatableMessage('otherCurrencyExpected', [], 'MiCarteraDomain'), 'cashDividend.currency');
            }
        }

        $numberOperation = new NumberOperation();
        if ($numberOperation->smaller($this->expenses->getMaxDecimals(), $this->expenses, new Number('0'))) {
            throw new DomainViolationException(new TranslatableMessage('cashDividendExpensesCannotBeNegative', [], 'MiCarteraDomain'), 'cashDividend.expenses');
        }
        if ($numberOperation->smallerOrEqual($this->dividendPerShare->getMaxDecimals(), $this->dividendPerShare, new Number('0'))) {
            throw new DomainViolationException(new TranslatableMessage('cashDividendPerShareMustBePositive', [], 'MiCarteraDomain'), 'cashDividend.dividendPerShare');
        }

        $this->amount = new CashDividendAmountVO(
            $this->acquisitionPersistence->getRepository()->totalAmountForAccountStockAtOrBefore(
                $this->account,
                $this->stock,
                $this->datetimeutc
            ),
            $this->liquidationPersistence->getRepository()->totalAmountForAccountStockAtOrBefore(
                $this->account,
                $this->stock,
                $this->datetimeutc
            )
        );
        $this->totalDividendPayment = $this->calculateTotalDividendPayment();
        $this->id = Uuid::v4();

        $this->persistCreate();
    }

    public function setAmountAndTotalDividendPayment(CashDividendAmountVO $amount): void
    {
        $this->amount = $amount;
        $this->totalDividendPayment = $this->calculateTotalDividendPayment();
    }

    private function calculateTotalDividendPayment(): CashDividendMoneyVO
    {
        return new CashDividendMoneyVO(
            new NumberOperation()->multiply(
                $this->account->getCurrency()->getDecimals(),
                $this->amount,
                $this->dividendPerShare
            ),
            $this->account->getCurrency()
        );
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof self) {
            throw new \InvalidArgumentException();
        }

        return $this->id->equals($otherEntity->id);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getDateTimeUtc(): \DateTime
    {
        return clone $this->datetimeutc;
    }

    public function getAmount(): CashDividendAmountVO
    {
        return $this->amount;
    }

    public function getDividendPerShare(): CashDividendMoneyVO
    {
        return $this->dividendPerShare;
    }

    public function getTotalDividendPayment(): CashDividendMoneyVO
    {
        return $this->totalDividendPayment;
    }

    public function getExpenses(): CashDividendMoneyVO
    {
        return $this->expenses;
    }

    private function persistCreate(): void
    {
        if (false === $this->cashDividendPersistence->getRepository()->assertNoCashDividendOnDateTime(
            $this->account,
            $this->stock,
            $this->datetimeutc
        )) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'cashDividendExistsOnDateTime',
                    [],
                    'MiCarteraDomain'
                ),
                'cashDividend.duplicate'
            );
        }

        $this->cashDividendPersistence->persist($this);
        $this->cashDividendPersistence->flush();
    }

    public function persistUpdate(
        CashDividendPersistenceInterface $cashDividendPersistence,
        CashDividendMoneyVO $dividendPerShare,
        CashDividendMoneyVO $expenses
    ): self {
        if (false === $dividendPerShare->getCurrency()->sameId($this->account->getCurrency())
            || false === $expenses->getCurrency()->sameId($this->account->getCurrency())) {
            throw new DomainViolationException(
                new TranslatableMessage('otherCurrencyExpected', [], 'MiCarteraDomain'),
                'cashDividend.currency'
            );
        }

        $numberOperation = new NumberOperation();
        if ($numberOperation->smaller($expenses->getMaxDecimals(), $expenses, new Number('0'))) {
            throw new DomainViolationException(
                new TranslatableMessage('cashDividendExpensesCannotBeNegative', [], 'MiCarteraDomain'),
                'cashDividend.expenses'
            );
        }
        if ($numberOperation->smallerOrEqual($dividendPerShare->getMaxDecimals(), $dividendPerShare, new Number('0'))) {
            throw new DomainViolationException(
                new TranslatableMessage('cashDividendPerShareMustBePositive', [], 'MiCarteraDomain'),
                'cashDividend.dividendPerShare'
            );
        }

        $this->dividendPerShare = $dividendPerShare;
        $this->expenses = $expenses;
        $this->totalDividendPayment = $this->calculateTotalDividendPayment();
        $cashDividendPersistence->persist($this);
        $cashDividendPersistence->flush();

        return $this;
    }

    public function persistRemove(
        CashDividendPersistenceInterface $cashDividendPersistence
    ): void {
        $cashDividendPersistence->remove($this);
        $cashDividendPersistence->flush();
    }
}
