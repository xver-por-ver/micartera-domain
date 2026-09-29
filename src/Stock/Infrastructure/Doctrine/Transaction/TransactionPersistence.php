<?php

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 * 
 * @template-extends EntityPersistence<Acquisition|Liquidation>
 */
final class TransactionPersistence extends EntityPersistence implements TransactionPersistenceInterface
{
    public function __construct(private EntityManager $entityManager) {}

    /**
     * @return AcquisitionRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->entityManager->getRepository(Acquisition::class);
        if (!$repository instanceof AcquisitionRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }

    /**
     * @return LiquidationRepositoryInterface
     */
    #[\Override]
    public function getRepositoryForLiquidation(): EntityRepositoryInterface
    {
        $repository = $this->entityManager->getRepository(Liquidation::class);
        if (!$repository instanceof LiquidationRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }

    /**
     * @return MovementRepositoryInterface
     */
    #[\Override]
    public function getRepositoryForMovement(): EntityRepositoryInterface
    {
        $repository = $this->entityManager->getRepository(Movement::class);
        if (!$repository instanceof MovementRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }
}
