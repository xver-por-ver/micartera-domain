<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendSynchronizer;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;

final class AcquisitionDeleteCommand
{
    public function __construct(
        private AcquisitionPersistenceInterface $acquisitionPersistence,
        private ?CashDividendSynchronizer $cashDividendSynchronizer = null
    ) {}

    public function invoke(
        string $acquisitionUuid
    ): void {
        $this->acquisitionPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($acquisitionUuid)
        )->persistRemove(
            $this->acquisitionPersistence,
            $this->cashDividendSynchronizer
        );
    }
}
