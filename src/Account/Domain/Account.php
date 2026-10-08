<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Account\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;
use Xver\SymfonyAuthBundle\Account\Domain\Account as BaseAccount;

/**
 * @psalm-api
 */
class Account extends BaseAccount
{
    /** @psalm-var non-empty-string */
    private readonly string $timezone;

    /**
     * @psalm-param non-empty-string $email
     * @psalm-param list<string> $roles
     */
    public function __construct(
        private readonly AccountPersistenceInterface $accountPersistence,
        string $email,
        protected string $password,
        private readonly Currency $currency,
        \DateTimeZone $timezone,
        array $roles = ['ROLE_USER']
    ) {
        $this->timezone = $timezone->getName();
        parent::__construct($this->accountPersistence, $email, $password, $roles);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone($this->timezone);
    }

    #[\Override]
    protected function persistCreate(): void
    {
        $repoCurrency = $this->accountPersistence->getRepositoryForCurrency();
        if (null === $repoCurrency->findById($this->getCurrency()->getIso3())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'relatedEntityNotPersisted',
                    ['entity' => 'Currency', 'entity2' => 'Account'],
                    'MiCarteraDomain'
                ),
                'acocunt.currency'
            );
        }
        $repoAccount = $this->accountPersistence->getRepository();
        $this->validateIdentifierUniqueness($repoAccount);
        $this->accountPersistence->persist($this);
        $this->accountPersistence->flush();
    }
}
