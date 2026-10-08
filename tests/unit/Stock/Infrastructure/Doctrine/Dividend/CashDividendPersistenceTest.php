<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Infrastructure\Doctrine\Dividend;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(CashDividendPersistence::class)]
class CashDividendPersistenceTest extends TestCase
{
    public function testGetRepositoryReturnsCashDividendRepository(): void
    {
        $repo = $this->createStub(CashDividendRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new CashDividendPersistence($registry);
        $result = $persistence->getRepository();
        $this->assertInstanceOf(EntityRepositoryInterface::class, $result);
        $this->assertInstanceOf(CashDividendRepositoryInterface::class, $result);
    }

    public function testGetRepositoryThrowsOnInvalidRepository(): void
    {
        $repo = $this->createStub(EntityRepository::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $persistence = new CashDividendPersistence($registry);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('entityConfigurationContainsInvalidRepository');
        $persistence->getRepository();
    }
}
