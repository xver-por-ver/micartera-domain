<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting;

use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\Movement;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementCollection;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementPriceVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\MovementRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryDTO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Accounting\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\TransactionExpenseVO;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Movement>
 *
 * @psalm-api
 */
class MovementRepository extends EntityRepository implements MovementRepositoryInterface
{
    final public const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Movement::class);
    }

    #[\Override]
    public function findByIdOrThrowException(Uuid $acquisitionUuid, Uuid $liquidationUuid): Movement
    {
        $entity = $this->findOneBy(['acquisition' => $acquisitionUuid, 'liquidation' => $liquidationUuid]);
        if (
            null === $entity
        ) {
            throw new EntityNotFoundException('Movement', $acquisitionUuid->toString() . ' ' . $liquidationUuid->toString());
        }

        return $entity;
    }

    #[\Override]
    public function findByAccountAndYear(
        Account $account,
        int $year,
        ?int $limit = 1,
        int $offset = 0
    ): MovementCollection {
        $dateFrom = new \DateTime($year . '-01-01 00:00:00', $account->getTimeZone());
        $dateTo = new \DateTime(($year + 1) . '-01-01 00:00:00', $account->getTimeZone());
        $dateFrom->setTimezone(new \DateTimeZone('UTC'));
        $dateTo->setTimezone(new \DateTimeZone('UTC'));
        $qb = $this->createQueryBuilder('m')
            ->select('m, l, a, sl, sa, esl, esa')
            ->innerJoin('m.liquidation', 'l')
            ->innerJoin('m.acquisition', 'a')
            ->innerJoin('l.stock', 'sl')
            ->innerJoin('a.stock', 'sa')
            ->innerJoin('sl.exchange', 'esl')
            ->innerJoin('sa.exchange', 'esa')
            ->where('l.account = :account_id')
            ->andWhere('l.datetimeutc >= :date_from')
            ->andWhere('l.datetimeutc < :date_to')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('date_from', $dateFrom->format(self::DATE_FORMAT))
            ->setParameter('date_to', $dateTo->format(self::DATE_FORMAT))
            ->orderBy('a.datetimeutc', 'ASC')
            ->addOrderBy('l.datetimeutc', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        /** @var array<int, Movement> $result */
        $result = $qb->getQuery()->getResult();

        return new MovementCollection($result);
    }

    #[\Override]
    public function accountingSummaryByAccount(Account $account, int $displayedYear): SummaryVO
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                '
            COALESCE(SUM(a.acquisitionPrice.value),0) acquisitionPrice,
            COALESCE(SUM(a.acquisitionExpenses.value),0) acquisitionExpenses,
            COALESCE(SUM(a.liquidationPrice.value),0) liquidationPrice,
            COALESCE(SUM(a.liquidationExpenses.value),0) liquidationExpenses,
            MIN(ts.datetimeutc) firstDateTimeUtc
            '
            )
            ->innerJoin('a.liquidation', 'ts')
            ->where('ts.account = :account_id')
            ->setParameter('account_id', $account->getId(), 'uuid')
        ;

        /** @var array{acquisitionPrice: numeric-string, acquisitionExpenses: numeric-string, liquidationPrice: numeric-string, liquidationExpenses: numeric-string, firstDateTimeUtc: string} $allTimeresult */
        $allTimeresult = $qb->getQuery()->getSingleResult();

        $acquisitionPrice = new Number($allTimeresult['acquisitionPrice']);
        $liquidationPrice = new Number($allTimeresult['liquidationPrice']);
        $numberOperation = new NumberOperation();

        $summaryAllTimeDTO = new SummaryDTO(
            new MovementPriceVO($numberOperation->round($account->getCurrency()->getDecimals(), $acquisitionPrice, \RoundingMode::HalfAwayFromZero), $account->getCurrency()),
            new TransactionExpenseVO($allTimeresult['acquisitionExpenses'], $account->getCurrency()),
            new MovementPriceVO($numberOperation->round($account->getCurrency()->getDecimals(), $liquidationPrice, \RoundingMode::HalfAwayFromZero), $account->getCurrency()),
            new TransactionExpenseVO($allTimeresult['liquidationExpenses'], $account->getCurrency())
        );

        $qb = $this->createQueryBuilder('a')
            ->select(
                '
            COALESCE(SUM(a.acquisitionPrice.value),0) acquisitionPrice,
            COALESCE(SUM(a.acquisitionExpenses.value),0) acquisitionExpenses,
            COALESCE(SUM(a.liquidationPrice.value),0) liquidationPrice,
            COALESCE(SUM(a.liquidationExpenses.value),0) liquidationExpenses
            '
            )
            ->innerJoin('a.liquidation', 'ts')
            ->where('ts.account = :account_id')
            ->andWhere('ts.datetimeutc >= :date_from')
            ->andWhere('ts.datetimeutc < :date_to')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter(
                'date_from',
                new \DateTime($displayedYear . '-01-01 00:00:00', $account->getTimeZone())
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(self::DATE_FORMAT)
            )
            ->setParameter(
                'date_to',
                new \DateTime(($displayedYear + 1) . '-01-01 00:00:00', $account->getTimeZone())
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(self::DATE_FORMAT)
            )
        ;

        /** @var array{acquisitionPrice: numeric-string, acquisitionExpenses: numeric-string, liquidationPrice: numeric-string, liquidationExpenses: numeric-string} $displayedYearResult */
        $displayedYearResult = $qb->getQuery()->getSingleResult(Query::HYDRATE_OBJECT);

        $acquisitionPrice = new Number($displayedYearResult['acquisitionPrice']);
        $liquidationPrice = new Number($displayedYearResult['liquidationPrice']);

        $summaryDisplayedYearDTO = new SummaryDTO(
            new MovementPriceVO($numberOperation->round($account->getCurrency()->getDecimals(), $acquisitionPrice, \RoundingMode::HalfAwayFromZero), $account->getCurrency()),
            new TransactionExpenseVO($displayedYearResult['acquisitionExpenses'], $account->getCurrency()),
            new MovementPriceVO($numberOperation->round($account->getCurrency()->getDecimals(), $liquidationPrice, \RoundingMode::HalfAwayFromZero), $account->getCurrency()),
            new TransactionExpenseVO($displayedYearResult['liquidationExpenses'], $account->getCurrency())
        );

        return new SummaryVO(
            $account,
            $allTimeresult['firstDateTimeUtc'] ? \DateTime::createFromFormat(self::DATE_FORMAT, $allTimeresult['firstDateTimeUtc'], new \DateTimeZone('UTC')) : null,
            $summaryAllTimeDTO,
            $summaryDisplayedYearDTO
        );
    }

    #[\Override]
    public function findByAccountStockAcquisitionDateAfter(
        Account $account,
        Stock $stock,
        \DateTime $dateTime
    ): MovementCollection {
        $qb = $this->createQueryBuilder('a')
            ->select('a, t2, t3')
            ->innerJoin('a.liquidation', 't2')
            ->innerJoin('a.acquisition', 't3')
            ->where('t3.account = :account_id')
            ->andWhere('t3.stock = :stock_id')
            ->andWhere('t3.datetimeutc > :date')
            ->setParameter('account_id', $account->getId(), 'uuid')
            ->setParameter('stock_id', $stock->getId())
            ->setParameter('date', $dateTime->format(self::DATE_FORMAT))
            ->addOrderBy('t3.datetimeutc', 'ASC')
            ->addOrderBy('t2.datetimeutc', 'ASC')
        ;

        /** @var array<int, Movement> $result */
        $result = $qb->getQuery()->getResult();

        return new MovementCollection($result);
    }
}
