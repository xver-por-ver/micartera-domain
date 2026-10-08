<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Account\Infrastructure\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Entity\Infrastructure\Doctrine\EntityRepository;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityNotFoundException;

/**
 * @template-extends EntityRepository<Account>
 *
 * @psalm-api
 */
class AccountRepository extends EntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Account::class);
    }

    #[\Override]
    public function findByIdentifier(string $identifier): ?Account
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a, c')
            ->innerJoin('a.currency', 'c')
            ->where('a.email = :email')
            ->setParameter('email', $identifier, 'string')
        ;
        $query = $qb->getQuery();

        /** @var ?Account */
        return $query->getOneOrNullResult($query::HYDRATE_OBJECT);
    }

    /**
     * @throws EntityNotFoundException
     */
    #[\Override]
    public function findByIdentifierOrThrowException(string $identifier): Account
    {
        $entity = $this->findByIdentifier($identifier);
        if (null === $entity) {
            throw new EntityNotFoundException('Account', $identifier);
        }

        return $entity;
    }
}
