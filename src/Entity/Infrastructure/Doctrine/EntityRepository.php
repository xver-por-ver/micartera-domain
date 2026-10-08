<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template T of EntityInterface
 *
 * @template-extends ServiceEntityRepository<T>
 *
 * @template-implements EntityRepositoryInterface<T>
 */
abstract class EntityRepository extends ServiceEntityRepository implements EntityRepositoryInterface
{
    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(ManagerRegistry $managerRegistry, private string $entityClass)
    {
        parent::__construct($managerRegistry, $this->entityClass);
    }
}
