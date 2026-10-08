<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command\Dividend;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;

/** @psalm-api */
final class CashDividendUpdateCommand
{
    public function __construct(private CashDividendPersistenceInterface $cashDividendPersistence) {}

    /**
     * @psalm-param numeric-string $dividendPerShareValue
     * @psalm-param numeric-string $expensesValue
     */
    public function invoke(string $cashDividendUuid, string $dividendPerShareValue, string $expensesValue): CashDividend
    {
        $cashDividend = $this->cashDividendPersistence->getRepository()->findByIdOrThrowException(
            new Uuid($cashDividendUuid)
        );

        return $cashDividend->persistUpdate(
            $this->cashDividendPersistence,
            new CashDividendMoneyVO($dividendPerShareValue, $cashDividend->getAccount()->getCurrency()),
            new CashDividendMoneyVO($expensesValue, $cashDividend->getAccount()->getCurrency())
        );
    }
}
