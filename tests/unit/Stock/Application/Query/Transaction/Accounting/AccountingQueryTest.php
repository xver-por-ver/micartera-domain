<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting\AccountingDTO;
use Xver\MiCartera\Domain\Stock\Application\Query\Transaction\Accounting\AccountingQuery;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;

/**
 * @internal
 */
#[CoversClass(AccountingQuery::class)]
#[UsesClass(AccountingDTO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(NumberOperation::class)]
class AccountingQueryTest extends TestCase
{
    private AccountRepositoryInterface&Stub $repoAccount;
    private MovementRepositoryInterface&Stub $repoMovement;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private MovementPersistenceInterface&Stub $movementPersistence;

    public function setUp(): void
    {
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoMovement = $this->createStub(MovementRepositoryInterface::class);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->movementPersistence = $this->createStub(MovementPersistenceInterface::class);
        $this->movementPersistence->method('getRepository')->willReturn($this->repoMovement);
    }

    #[DataProvider('displayedYear')]
    public function testByAccountYearCommandSucceeds($displayedYear): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getTimeZone')->willReturn(new \DateTime('now', new \DateTimeZone('UTC'))->getTimezone());
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($account);
        $this->repoMovement->method('findByAccountAndYear')->willReturn(
            $this->createStub(MovementCollection::class)
        );
        $this->repoMovement->method('accountingSummaryByAccount')->willReturn($this->createStub(SummaryVO::class));
        $query = new AccountingQuery($this->accountPersistence, $this->movementPersistence);
        $accountingDTO = $query->byAccountYear(
            'test@example.com',
            $displayedYear
        );
        $this->assertInstanceOf(AccountingDTO::class, $accountingDTO);
    }

    public static function displayedYear(): array
    {
        return [
            [null],
            [(int) new \DateTime('now')->format('Y')],
        ];
    }
}
