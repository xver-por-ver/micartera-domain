<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Domain\Dividend;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;

/** @internal */
#[CoversClass(CashDividendCollection::class)]
class CashDividendCollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $cashDividendCollection = new CashDividendCollection([]);

        self::assertSame(CashDividend::class, $cashDividendCollection->type());
    }
}
