<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Dividend;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;

/** @psalm-api */
final class CashDividendDeleteCommand
{
    public function __construct(private CashDividendPersistenceInterface $cashDividendPersistence) {}

    public function invoke(string $cashDividendUuid): void
    {
        $this->cashDividendPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($cashDividendUuid)
        )->persistRemove($this->cashDividendPersistence);
    }
}
