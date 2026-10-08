<?php

declare(strict_types=1);

namespace Tests\unit\Account\Application\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Application\Query\AccountQuery;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;

/**
 * @internal
 */
#[CoversClass(AccountQuery::class)]
class AccountQueryTest extends TestCase
{
    private AccountPersistenceInterface&Stub $accountPersistence;
    private AccountRepositoryInterface&Stub $accountRepo;
    private AccountQuery $accountQuery;

    protected function setUp(): void
    {
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountRepo = $this->createStub(AccountRepositoryInterface::class);

        $this->accountPersistence->method('getRepository')->willReturn($this->accountRepo);

        $this->accountQuery = new AccountQuery($this->accountPersistence);
    }

    public function testFindByIdentifierOrThrowExceptionReturnsAccount(): void
    {
        $expectedAccount = $this->createStub(Account::class);

        $this->accountRepo
            ->method('findByIdentifierOrThrowException')
            ->willReturn($expectedAccount)
        ;

        $result = $this->accountQuery->findByIdentifierOrThrowException('test-identifier');

        $this->assertSame($expectedAccount, $result);
    }

    public function testFindByIdentifierOrThrowExceptionThrowsException(): void
    {
        $this->expectException(\Exception::class);

        $this->accountRepo
            ->method('findByIdentifierOrThrowException')
            ->willThrowException(new \Exception('Account not found'))
        ;

        $this->accountQuery->findByIdentifierOrThrowException('non-existent-identifier');
    }
}
