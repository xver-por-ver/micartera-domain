<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Liquidation>
 */
interface LiquidationRepositoryInterface extends EntityRepositoryInterface
{
    public function findByAccountStockAndDateAtOrAfter(
        Account $account,
        Stock $stock,
        \DateTime $date
    ): LiquidationCollection;

    public function findById(Uuid $uuid): ?Liquidation;

    public function findByIdOrThrowException(Uuid $id): Liquidation;

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): LiquidationCollection;

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        \DateTime $datetimeutc
    ): bool;

    public function totalAmountForAccountStockAtOrBefore(Account $account, Stock $stock, \DateTime $date): Number;
}
