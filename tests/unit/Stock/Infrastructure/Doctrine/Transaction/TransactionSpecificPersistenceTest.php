<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(AcquisitionPersistence::class)]
#[CoversClass(LiquidationPersistence::class)]
#[CoversClass(MovementPersistence::class)]
class TransactionSpecificPersistenceTest extends TestCase
{
    public function testAcquisitionGetRepositoryReturnsRepository(): void
    {
        $repo = $this->createStub(AcquisitionRepository::class);
        $persistence = new AcquisitionPersistence($this->registryReturning($repo));

        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(AcquisitionRepositoryInterface::class, $result);
    }

    public function testAcquisitionGetRepositoryThrowsOnInvalidRepository(): void
    {
        $persistence = new AcquisitionPersistence($this->registryReturning($this->createStub(EntityRepository::class)));

        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }

    public function testLiquidationGetRepositoryReturnsRepository(): void
    {
        $repo = $this->createStub(LiquidationRepository::class);
        $persistence = new LiquidationPersistence($this->registryReturning($repo));

        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(LiquidationRepositoryInterface::class, $result);
    }

    public function testLiquidationGetRepositoryThrowsOnInvalidRepository(): void
    {
        $persistence = new LiquidationPersistence($this->registryReturning($this->createStub(EntityRepository::class)));

        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }

    public function testMovementGetRepositoryReturnsRepository(): void
    {
        $repo = $this->createStub(MovementRepository::class);
        $persistence = new MovementPersistence($this->registryReturning($repo));

        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(MovementRepositoryInterface::class, $result);
    }

    public function testMovementGetRepositoryThrowsOnInvalidRepository(): void
    {
        $persistence = new MovementPersistence($this->registryReturning($this->createStub(EntityRepository::class)));

        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }

    private function registryReturning(EntityRepositoryInterface $repository): ManagerRegistry
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($this->createStub(EntityManager::class));
        $registry->method('getRepository')->willReturn($repository);

        return $registry;
    }
}
