<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Dividend;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPerShareVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;

/** @psalm-api */
final class CashDividendUpdateCommand
{
    public function __construct(private CashDividendPersistenceInterface $cashDividendPersistence) {}

    public function invoke(string $cashDividendUuid, Number $dividendPerShareValue, Number $expensesValue): CashDividend
    {
        $cashDividend = $this->cashDividendPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($cashDividendUuid)
        );

        return $cashDividend->persistUpdate(
            $this->cashDividendPersistence,
            new CashDividendPerShareVO($dividendPerShareValue, $cashDividend->getAccount()->getCurrency()),
            new CashDividendMoneyVO($expensesValue, $cashDividend->getAccount()->getCurrency())
        );
    }
}
