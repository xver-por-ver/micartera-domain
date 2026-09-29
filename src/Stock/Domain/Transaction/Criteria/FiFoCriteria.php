<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class FiFoCriteria
{
    private AcquisitionCollection $acquisitionsCollection;

    public function __construct(
        private readonly AcquisitionPersistenceInterface $acquisitionPersistence,
        private readonly LiquidationPersistenceInterface $liquidationPersistence,
        private readonly MovementPersistenceInterface $movementPersistence
    ) {
        $this->acquisitionsCollection = new AcquisitionCollection([]);
    }

    public function onAcquisition(
        Acquisition $acquisition
    ): void {
        $this->acquisitionsCollection = new AcquisitionCollection([]);

        $this->acquisitionsCollection->add($acquisition);

        $liquidationsCollection = $this->liquidationPersistence->getRepository()->findByAccountStockAndDateAtOrAfter(
            $acquisition->getAccount(),
            $acquisition->getStock(),
            $acquisition->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidation(
        Liquidation $liquidation
    ): void {
        $this->acquisitionsCollection = new AcquisitionCollection([]);

        $liquidationsCollection = $this->liquidationPersistence->getRepository()->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $lastLiquidation = $liquidationsCollection->last();
        $this->includePersistedAcquisitionsWithAmountOutstanding(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            false !== $lastLiquidation
            ? $lastLiquidation->getDateTimeUtc()
            : $liquidation->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $this->accountMovements($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    public function onLiquidationRemoval(
        Liquidation $liquidation
    ): void {
        $this->acquisitionsCollection = new AcquisitionCollection([]);

        $liquidationsCollection = $this->liquidationPersistence->getRepository()->findByAccountStockAndDateAtOrAfter(
            $liquidation->getAccount(),
            $liquidation->getStock(),
            $liquidation->getDateTimeUtc()
        );

        $this->traverseLiquidationsClearingMovements($liquidationsCollection);

        $this->sortAcquisitionsByOldestFirst();

        $liquidationsCollection->removeElement($liquidation);

        $this->traverseLiquidationsAccountingMovements($liquidationsCollection);
    }

    private function traverseLiquidationsClearingMovements(
        LiquidationCollection $liquidationsCollection
    ): void {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->mergeAcquisitions(
                $liquidation->clearMovementCollection(
                    $this->acquisitionPersistence,
                    $this->movementPersistence
                )
            );
        }
    }

    private function includePersistedAcquisitionsWithAmountOutstanding(
        Account $account,
        Stock $stock,
        \DateTime $dateLastLiquidation
    ): void {
        $acquisitionsWithAmountOutstandingCollection = $this->acquisitionPersistence->getRepository()->findByAccountStockWithActionableAmountAndDateAtOrBefore(
            $account,
            $stock,
            $dateLastLiquidation
        );
        $this->mergeAcquisitions($acquisitionsWithAmountOutstandingCollection);
    }

    private function mergeAcquisitions(AcquisitionCollection $acquisitionsCollection): void
    {
        foreach ($acquisitionsCollection->toArray() as $acquisition) {
            if (false === $this->acquisitionsCollection->contains($acquisition)) {
                $this->acquisitionsCollection->add($acquisition);
            }
        }
    }

    private function sortAcquisitionsByOldestFirst(): void
    {
        $acquisitionsArray = $this->acquisitionsCollection->toArray();
        usort($acquisitionsArray, fn (Acquisition $a, Acquisition $b) => $a->getDateTimeUtc() <=> $b->getDateTimeUtc());
        $this->acquisitionsCollection = new AcquisitionCollection($acquisitionsArray);
    }

    private function traverseLiquidationsAccountingMovements(LiquidationCollection $liquidationsCollection): void
    {
        foreach ($liquidationsCollection->toArray() as $liquidation) {
            $this->accountMovements($liquidation);
        }
    }

    private function accountMovements(Liquidation $liquidation): void
    {
        foreach ($this->acquisitionsCollection->toArray() as $acquisition) {
            if ($acquisition->getAmountActionable()->greater(new TransactionAmountActionableVO('0'))) {
                try {
                    new Movement($this->movementPersistence, $acquisition, $liquidation);
                } catch (DomainViolationException $dv) {
                    throw new DomainViolationException(
                        new TranslatableMessage(
                            'transNotPassFifoSpec',
                            [],
                            'MiCarteraDomain'
                        ),
                        'movement',
                        0,
                        $dv
                    );
                }
                if ($liquidation->getAmountActionable()->same(new TransactionAmountActionableVO('0'))) {
                    break;
                }
            }
        }
        if (
            $liquidation->getAmountActionable()->different(new TransactionAmountActionableVO('0'))
            || $liquidation->getExpensesUnaccountedFor()->different(new TransactionAmountActionableVO('0'))
        ) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'transNotPassFifoSpec',
                    [],
                    'MiCarteraDomain'
                ),
                'movement'
            );
        }
    }
}
