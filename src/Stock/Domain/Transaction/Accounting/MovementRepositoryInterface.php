<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting;

use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;

/**
 * @template-extends EntityRepositoryInterface<Movement>
 */
interface MovementRepositoryInterface extends EntityRepositoryInterface
{
    public function findByIdOrThrowException(Uuid $acquisitionUuid, Uuid $liquidationUuid): Movement;

    public function findByAccountAndYear(
        Account $account,
        int $year,
        ?int $limit = 1,
        int $offset = 0
    ): MovementCollection;

    public function accountingSummaryByAccount(Account $account, int $displayedYear): SummaryVO;

    public function findByAccountStockAcquisitionDateAfter(
        Account $account,
        Stock $stock,
        \DateTime $dateTime
    ): MovementCollection;
}
