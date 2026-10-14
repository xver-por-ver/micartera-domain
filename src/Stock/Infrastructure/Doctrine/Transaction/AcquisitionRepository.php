<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Acquisition>
 *
 * @psalm-api
 */
class AcquisitionRepository extends EntityRepository implements AcquisitionRepositoryInterface
{
    private const string ACCOUNT_WHERE = 't.account = :account_id';
    private const string STOCK_WHERE = 't.stock = :stock_code';
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Acquisition::class);
    }

    #[\Override]
    public function findById(Uuid $uuid): ?Acquisition
    {
        return $this->findOneBy(['id' => $uuid]);
    }

    #[\Override]
    public function findByIdOrThrowException(Uuid $id): Acquisition
    {
        $entity = $this->findById($id);
        if (null === $entity) {
            throw new EntityNotFoundException('Acquisition', $id->toString());
        }

        return $entity;
    }

    #[\Override]
    public function findByAccountStockWithActionableAmountAndDateAtOrBefore(
        Account $account,
        Stock $stock,
        \DateTime $date
    ): AcquisitionCollection {
        $qb = $this->createQueryBuilder('t')
            ->where(self::ACCOUNT_WHERE)
            ->andWhere(self::STOCK_WHERE)
            ->andWhere('t.amountActionable.value > 0')
            ->andWhere('t.datetimeutc <= :datetimeutc')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_code', $stock->getId())
            ->setParameter('datetimeutc', $date->format(self::DATE_FORMAT))
            ->orderBy('t.datetimeutc', 'ASC')
        ;

        $query = $qb->getQuery();
        if ($this->getEntityManager()->getConnection()->isTransactionActive()) {
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        }

        /** @var array<int, Acquisition> $result */
        $result = $query->getResult();

        return new AcquisitionCollection($result);
    }

    /**
     * @deprecated use findByStock() instead
     */
    #[\Override]
    public function findByStockId(Stock $stock, int $limit = 1, int $offset = 0): AcquisitionCollection
    {
        return $this->findByStock(
            $stock,
            $limit,
            $offset
        );
    }

    #[\Override]
    public function findByStock(
        Stock $stock,
        int $limit = 1,
        int $offset = 0
    ): AcquisitionCollection {
        return new AcquisitionCollection(
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
    public function findByAccountWithActionableAmount(
        Account $account,
        string $sortOrder,
        string $sortField = 'datetimeutc',
        int $limit = 1,
        int $offset = 0
    ): AcquisitionCollection {
        $qb = $this->createQueryBuilder('t')
            ->select('t, s, e')
            ->innerJoin('t.stock', 's')
            ->innerJoin('s.exchange', 'e')
            ->where(self::ACCOUNT_WHERE)
            ->andWhere('t.amountActionable.value > 0')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->orderBy('t.' . $this->sortFieldToString($sortField), $this->sortOrderToString($sortOrder))
        ;
        if (0 < $limit) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }
        $query = $qb->getQuery();

        /** @var array<int, Acquisition> $result */
        $result = $query->getResult();

        return new AcquisitionCollection($result);
    }

    #[\Override]
    public function portfolioSummary(Account $account, ?Stock $stock = null): SummaryVO
    {
        $and = [
            self::ACCOUNT_WHERE,
            't.amountActionable.value > 0',
        ];

        $parameters = new ArrayCollection([new Parameter('account_id', $account->getId(), 'uuid')]);
        if (false === is_null($stock)) {
            $and[] = 's.code = :stock_code';
            $parameters->add(new Parameter('stock_code', $stock->getId(), 'string'));
        }
        $qb = $this->createQueryBuilder('t')
            ->select(
                '
            COALESCE(SUM(t.amountActionable.value * t.price.value),0) totalAcquisitionPrice,
            COALESCE(SUM(t.amountActionable.value * s.price.value),0) totalMarketPrice,
            COALESCE(SUM(t.expensesUnaccountedFor.value), 0) totalAcquisitionFee
            '
            )
            ->innerJoin('t.stock', 's')
            ->where($and)
            ->setParameters($parameters)
        ;

        /** @var array{totalAcquisitionPrice: numeric-string, totalMarketPrice: numeric-string, totalAcquisitionFee: numeric-string} $result */
        $result = $qb->getQuery()->getSingleResult();

        $numberOperation = new NumberOperation();

        $totalAcquisitionPrice = new MovementPriceVO(
            $numberOperation->round(
                4,
                new Number($result['totalAcquisitionPrice']),
                \RoundingMode::HalfAwayFromZero
            ),
            $account->getCurrency()
        );

        $totalMarketPrice = new MovementPriceVO(
            $numberOperation->round(
                4,
                new Number($result['totalMarketPrice']),
                \RoundingMode::HalfAwayFromZero
            ),
            $account->getCurrency()
        );

        $expenses = new TransactionExpenseVO(new Number($result['totalAcquisitionFee']), $account->getCurrency());

        return new SummaryVO(
            $totalAcquisitionPrice,
            $expenses,
            $totalMarketPrice,
            $account->getCurrency()
        );
    }

    private function sortFieldToString(string $sortField): string
    {
        return 'amount' === $sortField ? 'amount' : 'datetimeutc';
    }

    private function sortOrderToString(string $sortOrder): string
    {
        return 'ASC' === $sortOrder ? 'ASC' : 'DESC';
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
