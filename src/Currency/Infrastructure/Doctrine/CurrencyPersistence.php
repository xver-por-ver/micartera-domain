<?php

namespace Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyPersistenceInterface;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 * 
 * @template-extends EntityPersistence<Currency>
 */
final class CurrencyPersistence extends EntityPersistence implements CurrencyPersistenceInterface
{
    public function __construct(private EntityManager $entityManager) {}

    #[\Override]
    public function getRepository(): EntityRepositoryInterface
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
