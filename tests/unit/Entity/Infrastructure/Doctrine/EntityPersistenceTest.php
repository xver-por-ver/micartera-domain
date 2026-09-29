<?php

declare(strict_types=1);

namespace Tests\unit\Entity\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityPersistence;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(EntityPersistence::class)]
class EntityPersistenceTest extends TestCase
{
    public function testPersistValidEntityDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entity = new DummyEntity();
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('persist')->with($entity);

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->persist($entity));
    }

    public function testRemoveValidEntityDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entity = new DummyEntity();
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('remove')->with($entity);

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->remove($entity));
    }

    public function testFlushDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('flush');

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->flush());
    }

    public function testBeginTransactionDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('beginTransaction');

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->beginTransaction());
    }

    public function testCommitDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('commit');

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->commit());
    }

    public function testRollBackDelegatesToEntityManagerAndReturnsSelf(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('rollback');

        $persistence = $this->persistence($entityManager);

        $this->assertSame($persistence, $persistence->rollBack());
    }

    public function testPersistInvalidEntityThrowsDomainViolationException(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->never())->method('persist');

        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('cannotOperateEntityUsingRepository');

        $this->persistence($entityManager)->persist(new OtherDummyEntity());
    }

    public function testRemoveInvalidEntityThrowsDomainViolationException(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->never())->method('remove');

        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('cannotOperateEntityUsingRepository');

        $this->persistence($entityManager)->remove(new OtherDummyEntity());
    }

    public function testNonOrmEntityManagerThrowsLogicException(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->createStub(ObjectManager::class));
        $persistence = new class($registry) extends EntityPersistence {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, DummyEntity::class);
            }

            #[\Override]
            public function getRepository(): EntityRepositoryInterface
            {
                return new class implements EntityRepositoryInterface {};
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('requires a Doctrine ORM entity manager');

        $persistence->flush();
    }

    private function persistence(EntityManager $entityManager): EntityPersistence
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($entityManager);

        return new class($registry) extends EntityPersistence {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, DummyEntity::class);
            }

            #[\Override]
            public function getRepository(): EntityRepositoryInterface
            {
                return new class implements EntityRepositoryInterface {};
            }
        };
    }
}

final class DummyEntity implements EntityInterface
{
    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        return $otherEntity instanceof self;
    }
}

final class OtherDummyEntity implements EntityInterface
{
    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        return $otherEntity instanceof self;
    }
}
