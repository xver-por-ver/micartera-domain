<?php

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 * 
 * @template-extends EntityPersistence<Stock>
 */
final class StockPersistence extends EntityPersistence implements StockPersistenceInterface
{
    public function __construct(private ManagerRegistry $managerRegistry) {
        parent::__construct($this->managerRegistry, Stock::class);
    }

    #[\Override]
    public function getRepository(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Stock::class);
        if (!$repository instanceof StockRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }

    #[\Override]
    public function getRepositoryForAcquisition(): EntityRepositoryInterface
    {
        $repository = $this->managerRegistry->getRepository(Acquisition::class);
        if (!$repository instanceof AcquisitionRepositoryInterface) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'entityConfigurationContainsInvalidRepository'
                )
            );
        }

        return $repository;
    }
}
