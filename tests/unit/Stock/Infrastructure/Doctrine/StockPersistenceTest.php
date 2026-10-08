<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(StockPersistence::class)]
class StockPersistenceTest extends TestCase
{
    public function testGetRepositoryReturnsStockRepository(): void
    {
        $repo = $this->createStub(StockRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new StockPersistence($registry);
        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(StockRepositoryInterface::class, $result);
    }

    public function testGetRepositoryThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new StockPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }

    public function testGetRepositoryForAcquisitionReturnsCurrencyRepository(): void
    {
        $repo = $this->createStub(AcquisitionRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new StockPersistence($registry);
        $result = $persistence->getRepositoryForAcquisition();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(AcquisitionRepositoryInterface::class, $result);
    }

    public function testGetRepositoryForAcquisitionThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new StockPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepositoryForAcquisition();
    }
}
