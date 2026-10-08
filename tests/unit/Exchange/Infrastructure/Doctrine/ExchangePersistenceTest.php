<?php

declare(strict_types=1);

namespace Tests\unit\Exchange\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangePersistence;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(ExchangePersistence::class)]
class ExchangePersistenceTest extends TestCase
{
    public function testGetRepositoryReturnsExchangeRepository(): void
    {
        $repo = $this->createStub(ExchangeRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new ExchangePersistence($registry);
        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(ExchangeRepositoryInterface::class, $result);
    }

    public function testGetRepositoryThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new ExchangePersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }
}
