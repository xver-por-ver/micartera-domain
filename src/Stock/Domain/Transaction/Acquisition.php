<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSynchronizer;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Acquisition extends TransactionAbstract
{
    public function __construct(
        private readonly AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly LiquidationPersistenceInterface $liquidationPersistence,
        private readonly MovementPersistenceInterface $movementPersistence,
        Stock $stock,
        StockPriceVO $acquisitionPrice,
        \DateTime $datetimeutc,
        TransactionAmountVO $amount,
        TransactionExpenseVO $expenses,
        Account $account,
        private readonly ?CashDividendSynchronizer $cashDividendSynchronizer = null
    ) {
        parent::__construct($stock, $acquisitionPrice, $datetimeutc, $amount, $expenses, $account);
        $this->persistCreate();
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Acquisition) {
            throw new \InvalidArgumentException();
        }

        return parent::getId()->equals(
            $otherEntity->getId()
        );
    }

    public function accountMovement(
        AcquisitionPersistenceInterface $acquisitionPersistence,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new \InvalidArgumentException();
        }
        $this->decreaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->decreaseExpensesUnaccountedFor($movement->getAcquisitionExpenses());
        $acquisitionPersistence->persist($this);

        return $this;
    }

    public function unaccountMovement(
        AcquisitionPersistenceInterface $acquisitionPersistence,
        Movement $movement
    ): self {
        if (false === $this->sameId($movement->getAcquisition())) {
            throw new \InvalidArgumentException();
        }
        $this->increaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->increaseExpensesUnaccountedFor($movement->getAcquisitionExpenses());
        $acquisitionPersistence->persist($this);

        return $this;
    }

    #[\Override]
    protected function persistCreate(): void
    {
        $repoAcquisition = $this->acquisitionPersistence->getRepository();
        if (
            false === $repoAcquisition->assertNoTransWithSameAccountStockOnDateTime(
                $this->getAccount(),
                $this->getStock(),
                $this->getDateTimeUtc()
            )
        ) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transExistsOnDateTime',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.duplicate'
            );
        }
        $this->acquisitionPersistence->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $this->acquisitionPersistence,
                $this->liquidationPersistence,
                $this->movementPersistence
            )->onAcquisition($this);
            $this->acquisitionPersistence->persist($this);
            $this->acquisitionPersistence->flush();
            $this->cashDividendSynchronizer?->synchronize($this->getAccount(), $this->getStock(), $this->getDateTimeUtc());
            $this->acquisitionPersistence->commit();
        } catch (\Throwable $th) {
            $this->acquisitionPersistence->rollBack();

            throw $th;
        }
    }

    public function persistRemove(
        AcquisitionPersistenceInterface $acquisitionPersistence,
        ?CashDividendSynchronizer $cashDividendSynchronizer = null
    ): void {
        if ($this->getAmount()->different($this->getAmountActionable())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transBuyCannotBeRemovedWithoutFullAmountOutstanding',
                    [],
                    'MiCarteraDomain'
                ),
                'acquisition.amountOutstanding'
            );
        }
        $acquisitionPersistence->beginTransaction();

        try {
            $acquisitionPersistence->remove($this);
            $acquisitionPersistence->flush();
            $cashDividendSynchronizer?->synchronize($this->getAccount(), $this->getStock(), $this->getDateTimeUtc());
            $acquisitionPersistence->commit();
        } catch (\Throwable $th) {
            $acquisitionPersistence->rollBack();

            throw $th;
        }
    }
}
