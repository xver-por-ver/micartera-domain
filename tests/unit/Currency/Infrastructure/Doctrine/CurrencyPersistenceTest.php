<?php

declare(strict_types=1);

namespace Tests\unit\Currency\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyPersistence;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(CurrencyPersistence::class)]
class CurrencyPersistenceTest extends TestCase
{
    public function testGetRepositoryReturnsCurrencyRepository(): void
    {
        $repo = $this->createStub(CurrencyRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new CurrencyPersistence($registry);
        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(CurrencyRepositoryInterface::class, $result);
    }

    public function testGetRepositoryThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new CurrencyPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessage('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }
}
