<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Transaction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionAmountActionableVO;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @internal
 */
#[CoversClass(TransactionAmountActionableVO::class)]
#[UsesClass(Number::class)]
#[UsesClass(NumberOperation::class)]
class TransactionAmountActionableVOTest extends TestCase
{
    #[DataProvider('valuesToTest')]
    public function testValidValues(string $amount, bool $exception, string $exceptionMsg): void
    {
        if ($exception) {
            $this->expectException(DomainViolationException::class);
            $this->expectExceptionMessage($exceptionMsg);
        } else {
            $this->expectNotToPerformAssertions();
        }
        new TransactionAmountActionableVO(new Number($amount));
    }

    public static function valuesToTest(): array
    {
        return [
            ['0', false, ''],
            ['0.000000001', false, ''],
            ['1', false, ''],
            ['1.000000001', false, ''],
            ['999999999.999999999', false, ''],
            ['0.0000000001', true, 'numberPrecision'],
            ['1.0000000001', true, 'numberPrecision'],
            ['0.9999999999', true, 'numberPrecision'],
            ['999999999.9999999999', true, 'numberPrecision'],
            ['1000000000', true, 'enterNumberBetween'],
            ['-0.1', true, 'enterNumberBetween'],
            ['-0.000000001', true, 'enterNumberBetween'],
        ];
    }
}
