<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command;

use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;

final class StockDeleteCommand
{
    public function __construct(private StockPersistenceInterface $stockPersistence) {}

    public function invoke(
        string $code
    ): void {
        $this->stockPersistence->getRepository()->findByIdOrThrowException($code)
            ->persistRemove(
                $this->stockPersistence
            )
        ;
    }
}
