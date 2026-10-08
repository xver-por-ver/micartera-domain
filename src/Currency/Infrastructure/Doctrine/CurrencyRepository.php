<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyCollection;
use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Currency>
 *
 * @psalm-api
 */
class CurrencyRepository extends EntityRepository implements CurrencyRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Currency::class);
    }

    /**
     * @psalm-return Currency|null
     */
    #[\Override]
    public function findById(string $iso3): ?Currency
    {
        return $this->find($iso3);
    }

    #[\Override]
    public function findByIdOrThrowException(string $iso3): Currency
    {
        $entity = $this->findById($iso3);
        if (null === $entity) {
            throw new EntityNotFoundException('Currency', $iso3);
        }

        return $entity;
    }

    #[\Override]
    public function all(): CurrencyCollection
    {
        return new CurrencyCollection(
            $this->findAll()
        );
    }
}
