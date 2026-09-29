<?php

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template-extends EntityPersistence<Liquidation>
 */
final class LiquidationPersistence extends EntityPersistence implements LiquidationPersistenceInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
        parent::__construct($this->entityManager(), Liquidation::class);
    }

    /**
     * @return LiquidationRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Liquidation::class);
        if (!$repository instanceof LiquidationRepositoryInterface) {
            throw new DomainViolationException(new TranslatableMessage('entityConfigurationContainsInvalidRepository'));
        }

        return $repository;
    }

    private function entityManager(): EntityManager
    {
        $manager = $this->managerRegistry->getManager();
        if (!$manager instanceof EntityManager) {
            throw new \LogicException('Liquidation persistence requires a Doctrine ORM entity manager.');
        }

        return $manager;
    }
}
