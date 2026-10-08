<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<Stock>
 *
 * @psalm-api
 */
class StockCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return Stock::class;
    }
}
