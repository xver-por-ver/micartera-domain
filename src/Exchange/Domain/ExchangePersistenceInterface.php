<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Exchange>
 */
interface ExchangePersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return ExchangeRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
