<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\LiquidationRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Liquidation>
 *
 * @psalm-api
 */
class LiquidationRepository extends EntityRepository implements LiquidationRepositoryInterface
{
    private const string ACCOUNT_WHERE = 't.account = :account_id';
    private const string STOCK_WHERE = 't.stock = :stock_code';
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Liquidation::class);
    }

    /**
     * @psalm-return Liquidation|null
     */
    #[\Override]
    public function findById(Uuid $uuid): ?Liquidation
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    #[\Override]
    public function findByIdOrThrowException(Uuid $id): Liquidation
    {
        $entity = $this->findById($id);
        if (null === $entity) {
            throw new EntityNotFoundException('Liquidation', $id->toString());
        }

        return $entity;
    }

    /**
     * @deprecated use findByStock() instead
     */
    #[\Override]
    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): LiquidationCollection
    {
        return $this->findByStock(
            $stock,
            $limit,
            $offset
        );
    }

    #[\Override]
    public function findByStock(Stock $stock, int $limit = 1, int $offset = 0): LiquidationCollection
    {
        return new LiquidationCollection(
            $this->findBy(
                ['stock' => $stock->getId()],
                ['datetimeutc' => 'ASC'],
                $limit,
                $offset
            )
        );
    }

    #[\Override]
    public function assertNoTransWithSameAccountStockOnDateTime(
        Account $account,
        Stock $stock,
        \DateTime $datetimeutc
    ): bool {
        $qb = $this->createQueryBuilder('t')
            ->where(self::ACCOUNT_WHERE)
            ->andWhere(self::STOCK_WHERE)
            ->andWhere('t.datetimeutc = :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $datetimeutc->format(self::DATE_FORMAT))
        ;

        return null === $qb->getQuery()->getOneOrNullResult();
    }

    #[\Override]
    public function findByAccountStockAndDateAtOrAfter(
        Account $account,
        Stock $stock,
        \DateTime $date
    ): LiquidationCollection {
        $qb = $this->createQueryBuilder('t')
            ->where(self::ACCOUNT_WHERE)
            ->andWhere(self::STOCK_WHERE)
            ->andWhere('t.datetimeutc >= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format(self::DATE_FORMAT))
            ->orderBy('t.datetimeutc', 'ASC')
        ;

        $query = $qb->getQuery();
        if ($this->getEntityManager()->getConnection()->isTransactionActive()) {
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        }

        /** @var array<int, Liquidation> $result */
        $result = $query->getResult();

        return new LiquidationCollection($result);
    }

    #[\Override]
    public function totalAmountForAccountStockAtOrBefore(Account $account, Stock $stock, \DateTime $date): Number
    {
        $qr = $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.amount.value), 0)')
            ->where(self::ACCOUNT_WHERE)
            ->andWhere(self::STOCK_WHERE)
            ->andWhere('t.datetimeutc <= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format(self::DATE_FORMAT))
            ->getQuery()
            ->getSingleScalarResult();

        /** @var numeric-string */
        $qr = is_null($qr) || false === $qr ? '0' : (string) $qr;
        return new Number($qr);
    }
}
