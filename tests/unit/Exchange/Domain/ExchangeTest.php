<?php

declare(strict_types=1);

namespace Tests\unit\Exchange\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Exchange::class)]
class ExchangeTest extends TestCase
{
    private ExchangePersistenceInterface&Stub $exchangePersitence;
    private ExchangeRepositoryInterface&Stub $repoExchange;

    public function setUp(): void
    {
        $this->repoExchange = $this->createStub(ExchangeRepositoryInterface::class);
        $this->exchangePersitence = $this->createStub(ExchangePersistenceInterface::class);
        $this->exchangePersitence->method('getRepository')->willReturn($this->repoExchange);
    }

    public function testDuplicateCodeThrowsException(): void
    {
        $this->repoExchange->method('findById')->willReturn($this->createStub(Exchange::class));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('ExchangeExists');
        new Exchange($this->exchangePersitence, 'CODE', 'NAME');
    }

    public function testExchangeValueObjectIsCreated(): void
    {
        $code = 'CODE';
        $name = 'NAME';
        $exchange = new Exchange($this->exchangePersitence, $code, $name);
        $this->assertSame($code, $exchange->getCode());
        $this->assertSame($name, $exchange->getName());
        $this->assertTrue($exchange->sameId($exchange));
    }

    #[DataProvider('invalidCodes')]
    public function testInvalidCodeThrowExceptions($testCode): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stringLength');
        new Exchange($this->exchangePersitence, $testCode, 'NAME');
    }

    public static function invalidCodes(): array
    {
        return [
            [''],
            ['AAAAAAAAAAAAA'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function testExchangeNameFormat($name): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('stringLength');
        new Exchange($this->exchangePersitence, 'CODE', $name);
    }

    public static function invalidNames(): array
    {
        $name = '';
        for ($i = 0; $i < 256; ++$i) {
            $name .= mt_rand(0, 9);
        }

        return [
            [''], [$name],
        ];
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $exchange = new Exchange($this->exchangePersitence, 'CODE', 'NAME');
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $exchange->sameId($entity);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        $this->exchangePersitence->method('persist')->willThrowException(new \Exception('simulating uncached exception'));
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        new Exchange($this->exchangePersitence, 'CODE', 'NAME');
    }
}
