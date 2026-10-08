<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Dividend;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<CashDividend>
 */
interface CashDividendRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(Uuid $id): ?CashDividend;

    public function findByIdOrThrowException(Uuid $id): CashDividend;

    public function assertNoCashDividendOnDateTime(Account $account, Stock $stock, \DateTime $datetimeutc): bool;

    public function findByAccountStockAtOrAfter(Account $account, Stock $stock, \DateTime $date): CashDividendCollection;
}
