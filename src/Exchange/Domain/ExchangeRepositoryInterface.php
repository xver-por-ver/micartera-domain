<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Domain;

use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Exchange>
 */
interface ExchangeRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(string $code): ?Exchange;

    public function findByIdOrThrowException(string $code): Exchange;

    public function all(): ExchangeCollection;
}
