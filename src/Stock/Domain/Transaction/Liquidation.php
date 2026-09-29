<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Liquidation extends TransactionAbstract
{
    /** @var MovementCollection */
    private Collection $movementCollection;

    public function __construct(
        private readonly LiquidationPersistenceInterface $liquidationPersistence,
        private readonly AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly MovementPersistenceInterface $movementPersistence,
        Stock $stock,
        StockPriceVO $liquidationPrice,
        \DateTime $datetimeutc,
        TransactionAmountVO $amount,
        TransactionExpenseVO $expenses,
        Account $account
    ) {
        parent::__construct($stock, $liquidationPrice, $datetimeutc, $amount, $expenses, $account);
        $this->movementCollection = new MovementCollection([]);
        $this->persistCreate();
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Liquidation) {
            throw new \InvalidArgumentException();
        }

        return parent::getId()->equals($otherEntity->getId());
    }

    public function clearMovementCollection(
        AcquisitionPersistenceInterface $acquisitionPersistence,
        LiquidationPersistenceInterface $liquidationPersistence,
        MovementPersistenceInterface $movementPersistence
    ): AcquisitionCollection {
        $updatedAcquisitionsCollection = new AcquisitionCollection([]);
        foreach ($this->movementCollection->toArray() as $movement) {
            $acquisition = $movement->getAcquisition();
            $acquisition->unaccountMovement(
                $acquisitionPersistence,
                $movement
            );
            if (false === $updatedAcquisitionsCollection->contains($acquisition)) {
                $updatedAcquisitionsCollection->add($acquisition);
            }
            $movementPersistence->remove($movement);
            $movementPersistence->flush();
            parent::increaseExpensesUnaccountedFor($movement->getLiquidationExpenses());
        }
        $this->movementCollection->clear();
        $this->amountActionable = new TransactionAmountActionableVO($this->amount->getValue());
        $liquidationPersistence->persist($this);

        return $updatedAcquisitionsCollection;
    }

    public function accountMovement(Movement $movement): self {
        if (false === $this->sameId($movement->getLiquidation())) {
            throw new \InvalidArgumentException();
        }
        parent::decreaseExpensesUnaccountedFor($movement->getLiquidationExpenses()); // TODO: parent::? should be $this->
        $this->decreaseAmountActionable(new TransactionAmountActionableVO($movement->getAmount()->getValue()));
        $this->movementCollection->add($movement);
        $this->liquidationPersistence->persist($this);

        return $this;
    }

    #[\Override]
    protected function persistCreate(): void
    {
        $repoLiquidation = $this->liquidationPersistence->getRepository();
        if (
            false === $repoLiquidation->assertNoTransWithSameAccountStockOnDateTime(
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
                'liquidation.duplicate'
            );
        }
        $this->liquidationPersistence->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $this->acquisitionPersistence,
                $this->liquidationPersistence,
                $this->movementPersistence
            )->onLiquidation($this);
            $this->liquidationPersistence->persist($this);
            $this->liquidationPersistence->flush();
            $this->liquidationPersistence->commit();
        } catch (\Throwable $th) {
            $this->liquidationPersistence->rollBack();

            throw $th;
        }
    }

    public function persistRemove(
        LiquidationPersistenceInterface $liquidationPersistence,
        AcquisitionPersistenceInterface $acquisitionPersistence,
        MovementPersistenceInterface $movementPersistence
    ): void {
        $repoLiquidation = $liquidationPersistence->getRepository();
        $liquidationPersistence->beginTransaction();

        try {
            $this->fiFoCriteriaInstance(
                $acquisitionPersistence,
                $liquidationPersistence,
                $movementPersistence
            )->onLiquidationRemoval($this);
            $liquidationPersistence->remove($this);
            $liquidationPersistence->flush();
            $liquidationPersistence->commit();
        } catch (\Throwable $th) {
            $liquidationPersistence->rollBack();

            throw $th;
        }
    }
}
