<?php

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template-extends EntityPersistence<Acquisition>
 */
final class AcquisitionPersistence extends EntityPersistence implements AcquisitionPersistenceInterface
{
    public function __construct(private ManagerRegistry $managerRegistry) {
        parent::__construct($this->managerRegistry, Acquisition::class);
    }

    /**
     * @return AcquisitionRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Acquisition::class);
        if (!$repository instanceof AcquisitionRepositoryInterface) {
            throw new DomainViolationException(new TranslatableMessage('entityConfigurationContainsInvalidRepository'));
        }

        return $repository;
    }

    private function entityManager(): EntityManager
    {
        $manager = $this->managerRegistry->getManager();
        if (!$manager instanceof EntityManager) {
            throw new \LogicException('Acquisition persistence requires a Doctrine ORM entity manager.');
        }

        return $manager;
    }
}
