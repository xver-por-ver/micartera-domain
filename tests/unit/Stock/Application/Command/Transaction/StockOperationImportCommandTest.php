<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Command\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockCreatePurchaseCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockCreateSellCommand;
use Xver\MiCartera\Domain\Stock\Application\Command\Transaction\StockOperationImportCommand;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Criteria\FiFoCriteria;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAbstract;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(StockOperationImportCommand::class)]
#[UsesClass(StockCreatePurchaseCommand::class)]
#[UsesClass(StockCreateSellCommand::class)]
#[UsesClass(Acquisition::class)]
#[UsesClass(AcquisitionCollection::class)]
#[UsesClass(FiFoCriteria::class)]
#[UsesClass(Liquidation::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(StockPriceVO::class)]
#[UsesClass(TransactionAbstract::class)]
#[UsesClass(TransactionAmountActionableVO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(TransactionAmountVO::class)]
class StockOperationImportCommandTest extends TestCase
{
    private Currency&Stub $currency;
    private Account&Stub $account;
    private Stock&Stub $stock;
    private StockRepositoryInterface&Stub $repoStock;
    private AccountRepositoryInterface&Stub $repoAccount;
    private MovementRepositoryInterface&Stub $repoMovement;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private LiquidationRepositoryInterface&Stub $repoLiquidation;
    private AcquisitionPersistenceInterface&Stub $acquisitionPersistence;
    private LiquidationPersistenceInterface&Stub $liquidationPersistence;
    private MovementPersistenceInterface&Stub $movementPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private StockPersistenceInterface&Stub $stockPersistence;

    public function setUp(): void
    {
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoMovement = $this->createStub(MovementRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->repoLiquidation = $this->createStub(LiquidationRepositoryInterface::class);
        $this->acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $this->acquisitionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
        $this->liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $this->liquidationPersistence->method('getRepository')->willReturn($this->repoLiquidation);
        $this->movementPersistence = $this->createStub(MovementPersistenceInterface::class);
        $this->movementPersistence->method('getRepository')->willReturn($this->repoMovement);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('sameId')->willReturn(true);
        $this->currency->method('getDecimals')->willReturn(2);
        $this->account = $this->createStub(Account::class);
        $this->account->method('getCurrency')->willReturn($this->currency);
        $this->account->method('getTimeZone')->willReturn(new \DateTimeZone('Europe/Madrid'));
        $this->stock = $this->createStub(Stock::class);
        $this->stock->method('getCurrency')->willReturn($this->currency);
    }

    #[DataProvider('invalidImportData')]
    public function testCreateBatchFromCSVCommandWithInvalidDataThrowException($line, $field, $message, $params, $domain): void
    {
        $this->repoAcquisition->method('assertNoTransWithSameAccountStockOnDateTime')->willReturn(true);
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $command = new StockOperationImportCommand($this->acquisitionPersistence, $this->liquidationPersistence, $this->movementPersistence, $this->accountPersistence, $this->stockPersistence);

        try {
            $command->invoke(1, $line, 'test@example.com');
        } catch (DomainViolationException $th) {
            $this->assertSame('importCsvDomainError', $th->getTranslatableMessage()->getMessage());
            $parentTranslatableParams = $th->getTranslatableMessage()->getParameters();
            $this->assertSame(1, $parentTranslatableParams['row']);
            $this->assertSame($field, $parentTranslatableParams['field']);

            /** @var TranslatableMessage */
            $childTranslatable = $parentTranslatableParams['error'];
            $this->assertInstanceOf(TranslatableMessage::class, $childTranslatable);
            $this->assertSame($message, $childTranslatable->getMessage());
            $this->assertSame($params, $childTranslatable->getParameters());
            $this->assertSame($domain, $childTranslatable->getDomain());
        }
    }

    public static function invalidImportData(): array
    {
        $date = new \DateTime('yesterday', new \DateTimeZone('UTC'));

        return [
            [[$date->format('Y-m-d H:i:s'), 'invalid', 'TEST', '5.66', 100, '3.67'], 'transaction.type', 'invalidTransactionType', ['type' => 'invalid'], 'MiCarteraDomain'], // Invalid transaction type
            [[$date->format('Y-m-d'), 'acquisition', 'TEST', '5.66', 100, '3.67'], 'transaction.datetimeutc', 'failedCreatingDateObjectFromString', ['format' => 'Y-m-d H:i:s'], 'MiCarteraDomain'], // Invalid date
            [[$date->format('Y-m-d H:i:s'), 'acquisition', 'TEST', '5,66', 100, '3.67'], 'stock.value', 'numberFormat', [], 'MiCarteraDomain'], // Invalid price format
            [[$date->format('Y-m-d H:i:s'), 'acquisition', 'TEST', '5.66', 100, '3,67'], 'expense.value', 'numberFormat', [], 'MiCarteraDomain'], // Invalid expenses format
            [[$date->format('Y-m-d'), 'liquidation', 'TEST', '5.66', 100, '3.67'], 'transaction.datetimeutc', 'failedCreatingDateObjectFromString', ['format' => 'Y-m-d H:i:s'], 'MiCarteraDomain'], // Invalid date
            [[$date->format('Y-m-d H:i:s'), 'liquidation', 'TEST', '5,66', 100, '3.67'], 'stock.value', 'numberFormat', [], 'MiCarteraDomain'], // Invalid price format
            [[$date->format('Y-m-d H:i:s'), 'liquidation', 'TEST', '5.66', 100, '3,67'], 'expense.value', 'numberFormat', [], 'MiCarteraDomain'], // Invalid expenses format
        ];
    }

    public function testCreateBatchFromCSVCommandWithNonExistentStockThrowException(): void
    {
        $entity = 'Stock';
        $identifier = '44';
        $this->repoStock->method('findByIdOrThrowException')->willThrowException(new EntityNotFoundException($entity, $identifier));
        $this->repoAccount->method('findByIdentifierOrThrowException')->willReturn($this->account);
        $date = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $command = new StockOperationImportCommand($this->acquisitionPersistence, $this->liquidationPersistence, $this->movementPersistence, $this->accountPersistence, $this->stockPersistence);

        try {
            $command->invoke(
                1,
                [$date->format('Y-m-d H:i:s'), 'acquisition', 'NONEXISTENTSTOCK', '5.66', 100, '3.67'],
                'test@example.com'
            );
        } catch (DomainViolationException $th) {
            $this->assertSame('importCsvDomainError', $th->getTranslatableMessage()->getMessage());
            $parentTranslatableParams = $th->getTranslatableMessage()->getParameters();
            $this->assertSame(1, $parentTranslatableParams['row']);
            $this->assertSame('stock', $parentTranslatableParams['field']);

            /** @var TranslatableMessage */
            $childTranslatable = $parentTranslatableParams['error'];
            $this->assertInstanceOf(TranslatableMessage::class, $childTranslatable);
            $this->assertSame('entityNotFound', $childTranslatable->getMessage());
            $this->assertSame(['entity' => $entity, 'identifier' => $identifier], $childTranslatable->getParameters());
            $this->assertSame('PhpAppCore', $childTranslatable->getDomain());
        }
    }
}
