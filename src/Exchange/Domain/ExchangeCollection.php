<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<Exchange>
 *
 * @psalm-api
 */
class ExchangeCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return Exchange::class;
    }
}
