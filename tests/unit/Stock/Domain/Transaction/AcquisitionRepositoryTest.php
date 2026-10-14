<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;

/**
 * @internal
 */
#[CoversClass(AcquisitionRepository::class)]
class AcquisitionRepositoryTest extends TestCase
{
    public function testFindByStockIdIsCallingFindByStock(): void
    {
        $stock = $this->createStub(Stock::class);
        $expected = $this->createStub(AcquisitionCollection::class);
        $repository = $this->getMockBuilder(AcquisitionRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByStock'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findByStock')
            ->with($stock)
            ->willReturn($expected);

        $this->assertSame($expected, $repository->findByStockId($stock));
    }
}
