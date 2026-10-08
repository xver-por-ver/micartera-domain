<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityCollection;

/**
 * @template-extends EntityCollection<CashDividend>
 *
 * @psalm-api
 */
class CashDividendCollection extends EntityCollection
{
    #[\Override]
    public function type(): string
    {
        return CashDividend::class;
    }
}
