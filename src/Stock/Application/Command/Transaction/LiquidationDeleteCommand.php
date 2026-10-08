<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;

final class LiquidationDeleteCommand
{
    public function __construct(
        private LiquidationPersistenceInterface $liquidationPersistence,
        private AcquisitionPersistenceInterface $acquisitionPersistence,
        private MovementPersistenceInterface $movementPersistence
    ) {}

    public function invoke(
        string $id
    ): void {
        $this->liquidationPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($id)
        )->persistRemove(
            $this->liquidationPersistence,
            $this->acquisitionPersistence,
            $this->movementPersistence
        );
    }
}
