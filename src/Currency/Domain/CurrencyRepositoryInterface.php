<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Currency>
 */
interface CurrencyRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(string $iso3): ?Currency;

    public function findByIdOrThrowException(string $iso3): Currency;

    public function all(): CurrencyCollection;
}
