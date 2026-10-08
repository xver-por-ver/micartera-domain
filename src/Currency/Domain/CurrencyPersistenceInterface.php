<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<Currency>
 */
interface CurrencyPersistenceInterface extends EntityPersistenceInterface
{
    /**
     * @return CurrencyRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
