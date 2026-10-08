<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockCollection;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Stock>
 *
 * @psalm-api
 */
class StockRepository extends EntityRepository implements StockRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Stock::class);
    }

    #[\Override]
    public function findById(string $code): ?Stock
    {
        return $this->findOneBy(['code' => $code]);
    }

    #[\Override]
    public function findByIdOrThrowException(string $id): Stock
    {
        $entity = $this->findById($id);
        if (null === $entity) {
            throw new EntityNotFoundException('Stock', $id);
        }

        return $entity;
    }

    #[\Override]
    public function findByCurrency(
        Currency $currency,
        ?int $limit = null,
        int $offset = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): StockCollection {
        return new StockCollection(
            $this->findBy(
                ['currency' => $currency->getIso3()],
                [$sortField => $sortDir],
                $limit,
                $offset
            )
        );
    }

    #[\Override]
    public function countByCurrency(
        Currency $currency,
    ): int {
        $criteria = ['currency' => $currency->getIso3()];

        return $this->count($criteria);
    }
}
