<?php

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;

final class StockDeletePurchaseCommand
{
    public function __construct(private AcquisitionPersistenceInterface $acquisitionPersistence) {}

    public function invoke(
        string $acquisitionUuid
    ): void {
        $this->acquisitionPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($acquisitionUuid)
        )->persistRemove(
            $this->acquisitionPersistence
        );
    }
}
