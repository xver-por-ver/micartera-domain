<?php

namespace Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityPersistenceInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @template T of EntityInterface
 *
 * @template-implements EntityPersistenceInterface<T>
 */
abstract class EntityPersistence implements EntityPersistenceInterface
{
    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(private EntityManager $entityManager, private string $entityClass) {}

    /**
     * @param T $entity
     */
    #[\Override]
    public function persist(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->entityManager->persist($entity);

        return $this;
    }

    /**
     * @param T $entity
     */
    #[\Override]
    public function remove(EntityInterface $entity): self
    {
        $this->validateRepositoryCanOperateEntity($entity);
        $this->entityManager->remove($entity);

        return $this;
    }

    #[\Override]
    public function flush(): self
    {
        $this->entityManager->flush();

        return $this;
    }

    #[\Override]
    public function beginTransaction(): self
    {
        $this->entityManager->beginTransaction();

        return $this;
    }

    #[\Override]
    public function commit(): self
    {
        $this->entityManager->commit();

        return $this;
    }

    #[\Override]
    public function rollBack(): self
    {
        $this->entityManager->rollback();

        return $this;
    }

    private function validateRepositoryCanOperateEntity(EntityInterface $entity): void
    {
        if (!$entity instanceof $this->entityClass) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'cannotOperateEntityUsingRepository',
                    ['entity' => get_class($entity), 'repository' => get_class($this)]
                )
            );
        }
    }
}
