<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Acquisition|Liquidation|Movement>
 */
interface TransactionPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return AcquisitionRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;

    /**
     * @return LiquidationRepositoryInterface
     */
    public function getRepositoryForLiquidation(): EntityRepositoryInterface;

    /**
     * @return MovementRepositoryInterface
     */
    public function getRepositoryForMovement(): EntityRepositoryInterface;
}
