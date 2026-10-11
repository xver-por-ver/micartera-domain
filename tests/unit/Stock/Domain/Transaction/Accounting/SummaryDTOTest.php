<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction\Accounting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryDTO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;

/**
 * @internal
 */
#[CoversClass(SummaryDTO::class)]
#[UsesClass(TransactionExpenseVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(MovementPriceVO::class)]
class SummaryDTOTest extends TestCase
{
    private Currency&Stub $currency;

    public function setUp(): void
    {
        $this->currency = $this->createStub(Currency::class);
        $this->currency->method('getDecimals')->willReturn(2);
    }

    public function testSummaryDTO(): void
    {
        $summaryDTO = new SummaryDTO(
            new MovementPriceVO(new Number('1.00'), $this->currency),
            new TransactionExpenseVO(new Number('2.00'), $this->currency),
            new MovementPriceVO(new Number('3.00'), $this->currency),
            new TransactionExpenseVO(new Number('4.00'), $this->currency)
        );
        $this->assertSame('1.0000', $summaryDTO->acquisitionsPrice->getValueFormatted());
        $this->assertSame('2.00', $summaryDTO->acquisitionsExpenses->getValueFormatted());
        $this->assertSame('3.0000', $summaryDTO->liquidationsPrice->getValueFormatted());
        $this->assertSame('4.00', $summaryDTO->liquidationsExpenses->getValueFormatted());
    }
}
