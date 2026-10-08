<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendCollection;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<CashDividend>
 *
 * @psalm-api
 */
class CashDividendRepository extends EntityRepository implements CashDividendRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, CashDividend::class);
    }

    #[\Override]
    public function findById(Uuid $id): ?CashDividend
    {
        return $this->findOneBy(['id' => $id]);
    }

    #[\Override]
    public function findByIdOrThrowException(Uuid $id): CashDividend
    {
        $entity = $this->findById($id);
        if (null === $entity) {
            throw new EntityNotFoundException('CashDividend', $id->toString());
        }

        return $entity;
    }

    #[\Override]
    public function assertNoCashDividendOnDateTime(Account $account, Stock $stock, \DateTime $datetimeutc): bool
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.id')
            ->where('d.account = :account_id')
            ->andWhere('d.stock = :stock_code')
            ->andWhere('d.datetimeutc = :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $datetimeutc->format('Y-m-d H:i:s'))
        ;

        return null === $qb->getQuery()->getOneOrNullResult();
    }

    #[\Override]
    public function findByAccountStockAtOrAfter(Account $account, Stock $stock, \DateTime $date): CashDividendCollection
    {
        /** @var array<int, CashDividend> $dividends */
        $dividends = $this->createQueryBuilder('d')
            ->where('d.account = :account_id')
            ->andWhere('d.stock = :stock_code')
            ->andWhere('d.datetimeutc >= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format('Y-m-d H:i:s'))
            ->orderBy('d.datetimeutc', 'ASC')
            ->getQuery()
            ->getResult();

        return new CashDividendCollection($dividends);
    }
}
