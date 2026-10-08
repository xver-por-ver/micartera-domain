<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<Currency>
 *
 * @psalm-api
 */
class CurrencyCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return Currency::class;
    }
}
