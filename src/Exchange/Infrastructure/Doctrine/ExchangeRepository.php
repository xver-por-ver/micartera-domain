<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeCollection;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangeRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Exchange>
 *
 * @psalm-api
 */
class ExchangeRepository extends EntityRepository implements ExchangeRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Exchange::class);
    }

    #[\Override]
    public function findById(string $code): ?Exchange
    {
        return $this->find($code);
    }

    #[\Override]
    public function findByIdOrThrowException(string $code): Exchange
    {
        $entity = $this->findById($code);
        if (null === $entity) {
            throw new EntityNotFoundException('Exchange', $code);
        }

        return $entity;
    }

    #[\Override]
    public function all(): ExchangeCollection
    {
        return new ExchangeCollection($this->findAll());
    }
}
