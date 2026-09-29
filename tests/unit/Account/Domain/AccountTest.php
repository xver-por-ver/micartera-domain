<?php

declare(strict_types=1);

namespace Tests\unit\Account\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(Account::class)]
#[UsesClass(Currency::class)]
#[UsesClass(Stock::class)]
#[UsesClass(StockPriceVO::class)]
class AccountTest extends TestCase
{
    private Currency&Stub $currency;
    private static \DateTimeZone $timezone;
    private AccountRepositoryInterface&Stub $repoAccount;
    private CurrencyRepositoryInterface&Stub $repoCurrency;
    private AccountPersistenceInterface&Stub $accountPeristence;

    public static function setUpBeforeClass(): void
    {
        self::$timezone = new \DateTimeZone('Europe/Madrid');
    }

    public function setUp(): void
    {
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoCurrency = $this->createStub(CurrencyRepositoryInterface::class);
        $this->accountPeristence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPeristence->method('getRepository')->willReturn($this->repoAccount);
        $this->accountPeristence->method('getRepositoryForCurrency')->willReturn($this->repoCurrency);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
    }

    public function testAccountObjectIsCreated(): void
    {
        $email = 'test@example.com';
        $password = 'password';
        $this->repoCurrency->method('findById')->willReturn($this->currency);
        $account = new Account($this->accountPeristence, $email, $password, $this->currency, self::$timezone, ['ROLE_ADMIN']);
        $this->assertInstanceOf(Uuid::class, $account->getId());
        $this->assertTrue($account->sameId($account));
        $this->assertCount(2, $account->getRoles());
        $this->assertContains('ROLE_USER', $account->getRoles());
        $this->assertContains('ROLE_ADMIN', $account->getRoles());
        $this->assertSame($email, $account->getEmail());
        $this->assertSame($password, $account->getPassword());
        $this->assertSame(self::$timezone->getName(), $account->getTimeZone()->getName());
        $this->assertSame($this->currency, $account->getCurrency());
        $this->assertSame($email, $account->getIdentifier());
        $this->assertSame($password, $account->getPassword());
    }

    public function testCreateWithNonExistentCurrencyThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn(null);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('relatedEntityNotPersisted');
        new Account($this->accountPeristence, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }

    public function testCreateWithDuplicateEmailThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn($this->createStub(Currency::class));
        $this->repoAccount->method('findByIdentifier')->willReturn($this->createStub(Account::class));
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('accountEmailExists');
        new Account($this->accountPeristence, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }

    public function testExceptionIsThrownOnCommitFail(): void
    {
        $this->accountPeristence->method('persist')->willThrowException(new \Exception('simulating uncached exception'));
        $this->repoCurrency->method('findById')->willReturn($this->currency);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIs('simulating uncached exception');
        new Account($this->accountPeristence, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_ADMIN']);
    }

    public function testSameIdWithInvalidEntityThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn($this->currency);
        $this->expectException(\InvalidArgumentException::class);
        $account = new Account($this->accountPeristence, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_USER']);
        $entity = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return true;
            }
        };
        $account->sameId($entity);
    }

    public function testCreateAccountWithInvalidRoleThrowsException(): void
    {
        $this->repoCurrency->method('findById')->willReturn($this->currency);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('invalidUserRole');
        new Account($this->accountPeristence, 'test@example.com', 'password', $this->currency, self::$timezone, ['ROLE_NOEXISTS']);
    }
}
