<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;

final class AccountingQuery
{
    public function __construct(
        private AccountPersistenceInterface $accountPersistence,
        private MovementPersistenceInterface $movementPersistence
    ) {}

    public function byAccountYear(
        string $accountIdentifier,
        ?int $displayedYear = null,
        int $limit = 0,
        int $page = 0,
    ): AccountingDTO {
        $account = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier);
        $displayedYear = (
            is_null($displayedYear)
            ? (int) new \DateTime('now', $account->getTimeZone())->format('Y')
            : $displayedYear
        );

        return new AccountingDTO(
            $account,
            $this->movementPersistence->getRepository()->findByAccountAndYear(
                $account,
                $displayedYear,
                $limit ? $limit + 1 : null,
                $limit ? $page * $limit : 0
            ),
            $displayedYear,
            $this->movementPersistence->getRepository()->accountingSummaryByAccount($account, $displayedYear),
            $limit,
            $page
        );
    }
}
