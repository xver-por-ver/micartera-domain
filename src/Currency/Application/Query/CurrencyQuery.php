<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Application\Query;

use Xver\MiCartera\Domain\Currency\Domain\CurrencyPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

final class CurrencyQuery
{
    public function __construct(private CurrencyPersistenceInterface $currencyPersistence) {}

    public function all(): EntityCollectionQueryResponse
    {
        return new EntityCollectionQueryResponse(
            $this->currencyPersistence->getRepository()->all()
        );
    }
}
