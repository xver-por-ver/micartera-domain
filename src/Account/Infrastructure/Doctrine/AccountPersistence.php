<?php

namespace Xver\MiCartera\Domain\Account\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 * 
 * @template-extends EntityPersistence<Account>
 */
final class AccountPersistence extends EntityPersistence implements AccountPersistenceInterface
{
    public function __construct(private EntityManager $entityManager) 
    {
        // parent::__construct($this->entityManager, Account::class);
    }

    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->entityManager->getRepository(Account::class);
        if (!$repository instanceof AccountRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }

    #[\Override]
    public function getRepositoryForCurrency(): EntityRepositoryInterface
    {
        $repository = $this->entityManager->getRepository(Currency::class);
        if (!$repository instanceof CurrencyRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }
}
