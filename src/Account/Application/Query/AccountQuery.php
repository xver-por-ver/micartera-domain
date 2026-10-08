<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Account\Application\Query;

use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\SymfonyAuthBundle\Account\Application\Query\AccountQueryInterface;

final class AccountQuery implements AccountQueryInterface
{
    public function __construct(private AccountPersistenceInterface $accountPersistence) {}

    #[\Override]
    final public function findByIdentifierOrThrowException(
        string $identifier
    ): Account {
        $repoAccount = $this->accountPersistence->getRepository();

        return $repoAccount->findByIdentifierOrThrowException($identifier);
    }
}
