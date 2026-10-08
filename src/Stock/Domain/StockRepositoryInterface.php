<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain;

use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Stock>
 */
interface StockRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(string $code): ?Stock;

    public function findByCurrency(
        Currency $currency,
        ?int $limit = null,
        int $offset = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): StockCollection;

    public function countByCurrency(
        Currency $currency
    ): int;

    public function findByIdOrThrowException(string $id): Stock;
}
