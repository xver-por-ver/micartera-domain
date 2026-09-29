<?php

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template-extends EntityPersistence<Movement>
 */
final class MovementPersistence extends EntityPersistence implements MovementPersistenceInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
        parent::__construct($this->entityManager(), Movement::class);
    }

    /**
     * @return MovementRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Movement::class);
        if (!$repository instanceof MovementRepositoryInterface) {
            throw new DomainViolationException(new TranslatableMessage('entityConfigurationContainsInvalidRepository'));
        }

        return $repository;
    }

    private function entityManager(): EntityManager
    {
        $manager = $this->managerRegistry->getManager();
        if (!$manager instanceof EntityManager) {
            throw new \LogicException('Movement persistence requires a Doctrine ORM entity manager.');
        }

        return $manager;
    }
}
