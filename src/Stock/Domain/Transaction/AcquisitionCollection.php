<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<Acquisition>
 *
 * @psalm-api
 */
class AcquisitionCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return Acquisition::class;
    }
}
