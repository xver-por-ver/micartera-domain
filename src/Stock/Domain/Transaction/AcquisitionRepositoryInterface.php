<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Acquisition>
 */
interface AcquisitionRepositoryInterface extends EntityRepositoryInterface
{
    public function findById(Uuid $uuid): ?Acquisition;

    public function findByIdOrThrowException(Uuid $id): Acquisition;

    public function findByAccountStockWithActionableAmountAndDateAtOrBefore(
        Account $account,
        Stock $stock,
        \DateTime $date
    ): AcquisitionCollection;

    public function findByAccountWithActionableAmount(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        int $limit = 1,
        int $offset = 0
    ): AcquisitionCollection;

    public function findByStockId(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AcquisitionCollection;

    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        \DateTime $datetimeutc
    ): bool;

    public function portfolioSummary(
        Account $account,
        ?Stock $stock = null
    ): SummaryVO;

    public function totalAmountForAccountStockAtOrBefore(Account $account, Stock $stock, \DateTime $date): Number;
}
