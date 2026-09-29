<?php

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Movement>
 */
interface MovementPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return MovementRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
