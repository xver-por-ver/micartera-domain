<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Application\Query;

use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

final class ExchangeQuery
{
    public function __construct(private ExchangePersistenceInterface $exchangePersistence) {}

    public function all(): EntityCollectionQueryResponse
    {
        return new EntityCollectionQueryResponse(
            $this->exchangePersistence->getRepository()->all()
        );
    }
}
