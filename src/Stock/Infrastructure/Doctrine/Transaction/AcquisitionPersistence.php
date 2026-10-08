<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

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
    public function __construct(private ManagerRegistry $managerRegistry)
    {
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
}
