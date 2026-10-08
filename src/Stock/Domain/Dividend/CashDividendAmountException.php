<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Exception\Domain\ExceptionAbstract;

final class CashDividendAmountException extends ExceptionAbstract
{
    public function __construct(int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            new TranslatableMessage(
                'dividendRequiresPositiveHolding',
                [],
                'MiCarteraDomain'
            ),
            $code,
            $previous
        );
    }
}
