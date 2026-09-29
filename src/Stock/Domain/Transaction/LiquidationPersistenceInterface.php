<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Liquidation>
 */
interface LiquidationPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return LiquidationRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
