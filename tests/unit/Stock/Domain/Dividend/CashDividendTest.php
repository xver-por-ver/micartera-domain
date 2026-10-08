<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountException;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendAmountVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

#[CoversClass(CashDividend::class)]
#[CoversClass(CashDividendAmountVO::class)]
#[CoversClass(CashDividendAmountException::class)]
#[CoversClass(CashDividendMoneyVO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
#[UsesClass(TransactionAmountVO::class)]
class CashDividendTest extends TestCase
{
    public function testCreatesCashDividendWithCalculatedHoldingAndGrossPayment(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('1'), '0.25', '1.50');

        self::assertSame('199', $cashDividend->getAmount()->getValue());
        self::assertSame('0.25', $cashDividend->getDividendPerShare()->getValue());
        self::assertSame('49.75', $cashDividend->getTotalDividendPayment()->getValue());
        self::assertSame('1.5', $cashDividend->getExpenses()->getValue());
    }

    public function testExpensesCanDefaultToZero(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');

        self::assertSame('0', $cashDividend->getExpenses()->getValue());
        self::assertSame('50', $cashDividend->getTotalDividendPayment()->getValue());
    }

    public function testPersistUpdateRecalculatesPaymentAndPreservesHolding(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('1'), '0.25', '0');
        $persistence = $this->createMock(CashDividendPersistenceInterface::class);
        $persistence->expects(self::once())->method('persist')->with($cashDividend);
        $persistence->expects(self::once())->method('flush');
        $currency = $cashDividend->getAccount()->getCurrency();

        $cashDividend->persistUpdate(
            $persistence,
            new CashDividendMoneyVO('0.5', $currency),
            new CashDividendMoneyVO('1.25', $currency)
        );

        self::assertSame('199', $cashDividend->getAmount()->getValue());
        self::assertSame('0.5', $cashDividend->getDividendPerShare()->getValue());
        self::assertSame('1.25', $cashDividend->getExpenses()->getValue());
        self::assertSame('99.5', $cashDividend->getTotalDividendPayment()->getValue());
    }

    public function testPersistUpdateRejectsNegativeExpenses(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');
        $currency = $cashDividend->getAccount()->getCurrency();
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('cashDividendExpensesCannotBeNegative');

        $cashDividend->persistUpdate(
            $this->createStub(CashDividendPersistenceInterface::class),
            new CashDividendMoneyVO('0.5', $currency),
            new CashDividendMoneyVO('-1', $currency)
        );
    }

    public function testPersistUpdateRejectsNonPositiveDividendPerShare(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');
        $currency = $cashDividend->getAccount()->getCurrency();
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('cashDividendPerShareMustBePositive');

        $cashDividend->persistUpdate(
            $this->createStub(CashDividendPersistenceInterface::class),
            new CashDividendMoneyVO('0', $currency),
            new CashDividendMoneyVO('0', $currency)
        );
    }

    public function testPersistUpdateRejectsOtherCurrency(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');
        $otherCurrency = $this->createStub(Currency::class);
        $otherCurrency->method('sameId')->willReturn(false);
        $otherCurrency->method('getDecimals')->willReturn(2);
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');

        $cashDividend->persistUpdate(
            $this->createStub(CashDividendPersistenceInterface::class),
            new CashDividendMoneyVO('0.5', $otherCurrency),
            new CashDividendMoneyVO('0', $cashDividend->getAccount()->getCurrency())
        );
    }

    public function testPersistRemoveRemovesAndFlushes(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');
        $persistence = $this->createMock(CashDividendPersistenceInterface::class);
        $persistence->expects(self::once())->method('remove')->with($cashDividend);
        $persistence->expects(self::once())->method('flush');

        $cashDividend->persistRemove($persistence);
    }

    public function testUpdateAmount(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0');

        $cashDividend->setAmountAndTotalDividendPayment(new CashDividendAmountVO(new Number('200'), new Number('100')));

        $this->assertSame(new Number('100')->getValue(), $cashDividend->getAmount()->getValue());
    }

    public function testEntityGettersAndIdentity(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('1'), '0.25', '0');

        self::assertInstanceOf(Uuid::class, $cashDividend->getId());
        self::assertTrue($cashDividend->sameId($cashDividend));
        self::assertInstanceOf(Stock::class, $cashDividend->getStock());
        self::assertInstanceOf(Account::class, $cashDividend->getAccount());
        self::assertSame('UTC', $cashDividend->getDateTimeUtc()->getTimezone()->getName());
    }

    public function testSameIdRejectsAnEntityOfAnotherType(): void
    {
        $cashDividend = $this->createCashDividend(new Number('200'), new Number('1'), '0.25', '0');
        $other = new class implements EntityInterface {
            public function sameId(EntityInterface $otherEntity): bool
            {
                return false;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $cashDividend->sameId($other);
    }

    public function testRejectsDuplicateCashDividendOnDateTime(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('cashDividendExistsOnDateTime');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0', noDuplicate: false);
    }

    public function testRejectsDividendWithoutPositiveHolding(): void
    {
        $this->expectException(CashDividendAmountException::class);
        $this->expectExceptionMessageIs('dividendRequiresPositiveHolding');

        $this->createCashDividend(new Number('200'), new Number('200'), '0.25', '0');
    }

    public function testCashAmountsRespectCurrencyPrecision(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('numberPrecision');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.001', '0');
    }

    public function testRejectsMismatchedStockCurrency(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0', null, false);
    }

    public function testRejectsMismatchedDividendPerShareCurrency(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0', null, true, false);
    }

    public function testRejectsMismatchedExpensesCurrency(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('otherCurrencyExpected');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0', null, true, true, false);
    }

    public function testRejectsNegativeExpenses(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('cashDividendExpensesCannotBeNegative');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '-0.01');
    }

    public function testRejectsZeroDividendPerShare(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('cashDividendPerShareMustBePositive');

        $this->createCashDividend(new Number('200'), new Number('0'), '0', '0');
    }

    public function testRejectsFutureDividendDate(): void
    {
        $this->expectException(DomainViolationException::class);
        $this->expectExceptionMessageIs('futureDateNotAllowed');

        $this->createCashDividend(new Number('200'), new Number('0'), '0.25', '0', new \DateTime('tomorrow', new \DateTimeZone('UTC')));
    }

    private function createCashDividend(
        Number $acquired,
        Number $liquidated,
        string $dividendPerShare,
        string $expenses,
        ?\DateTime $date = null,
        bool $stockCurrencyMatches = true,
        bool $dividendPerShareCurrencyMatches = true,
        bool $expensesCurrencyMatches = true,
        bool $noDuplicate = true
    ): CashDividend {
        $currency = $this->createStub(Currency::class);
        $currency->method('sameId')->willReturn(true);
        $currency->method('getDecimals')->willReturn(2);
        $otherCurrency = $this->createStub(Currency::class);
        $otherCurrency->method('sameId')->willReturn(false);
        $otherCurrency->method('getDecimals')->willReturn(2);

        $account = $this->createStub(Account::class);
        $account->method('getCurrency')->willReturn($currency);

        $stock = $this->createStub(Stock::class);
        $stock->method('getCurrency')->willReturn($stockCurrencyMatches ? $currency : $otherCurrency);

        $acquisitionRepository = $this->createStub(AcquisitionRepositoryInterface::class);
        $acquisitionRepository->method('totalAmountForAccountStockAtOrBefore')->willReturn($acquired);
        $acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $acquisitionPersistence->method('getRepository')->willReturn($acquisitionRepository);

        $liquidationRepository = $this->createStub(LiquidationRepositoryInterface::class);
        $liquidationRepository->method('totalAmountForAccountStockAtOrBefore')->willReturn($liquidated);
        $liquidationPersistence = $this->createStub(LiquidationPersistenceInterface::class);
        $liquidationPersistence->method('getRepository')->willReturn($liquidationRepository);

        $cashDividendRepository = $this->createStub(CashDividendRepositoryInterface::class);
        $cashDividendRepository->method('assertNoCashDividendOnDateTime')->willReturn($noDuplicate);
        $cashDividendPersistence = $this->createStub(CashDividendPersistenceInterface::class);
        $cashDividendPersistence->method('getRepository')->willReturn($cashDividendRepository);

        return new CashDividend(
            $cashDividendPersistence,
            $acquisitionPersistence,
            $liquidationPersistence,
            $stock,
            $account,
            $date ?? new \DateTime('yesterday', new \DateTimeZone('UTC')),
            new CashDividendMoneyVO($dividendPerShare, $dividendPerShareCurrencyMatches ? $currency : $otherCurrency),
            new CashDividendMoneyVO($expenses, $expensesCurrencyMatches ? $currency : $otherCurrency)
        );
    }
}
