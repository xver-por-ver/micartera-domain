<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Account\Application\Command;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

final class AccountCreateCommand
{
    public function __construct(private AccountPersistenceInterface $accountPersistence) {}

    /**
     * @psalm-param non-empty-string $email
     * @psalm-param list<string> $roles
     * @psalm-param non-empty-string $currencyIso3
     */
    public function invoke(
        string $email,
        string $password,
        string $currencyIso3,
        \DateTimeZone $timezone,
        array $roles,
        bool $agreeTerms
    ): Account {
        if (true !== $agreeTerms) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'mustAgreeTerms',
                    [],
                    'SymfonyAuthBundle'
                ),
                'account.agreeTerms'
            );
        }

        /** @var CurrencyRepositoryInterface */
        $repoCurrency = $this->accountPersistence->getRepositoryForCurrency();

        return new Account(
            $this->accountPersistence,
            $email,
            $password,
            $repoCurrency->findByIdOrThrowException($currencyIso3),
            $timezone,
            $roles
        );
    }
}
