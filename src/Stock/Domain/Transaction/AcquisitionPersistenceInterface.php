<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Acquisition>
 */
interface AcquisitionPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return AcquisitionRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
