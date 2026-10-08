<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityPersistenceInterface<CashDividend>
 */
interface CashDividendPersistenceInterface extends EntityPersistenceInterface
{
    /** @return CashDividendRepositoryInterface */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;
}
