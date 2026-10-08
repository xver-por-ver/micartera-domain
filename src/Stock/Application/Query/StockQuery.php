<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Query;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\PhpAppCoreBundle\Entity\Application\Query\EntityCollectionQueryResponse;

final class StockQuery
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    public string $currencySymbol;

    public function __construct(
        private StockPersistenceInterface $stockPersistence,
        private AccountPersistenceInterface $accountPersistence
    ) {}

    /**
     * @return EntityCollectionQueryResponse<Stock>
     */
    public function byAccountsCurrency(
        string $accountIdentifier,
        int $limit = 0,
        int $page = 0,
        string $sortField = 'code',
        string $sortDir = 'ASC'
    ): EntityCollectionQueryResponse {
        $repoStock = $this->stockPersistence->getRepository();
        $currency = $this->accountPersistence->getRepository()->findByIdentifierOrThrowException($accountIdentifier)->getCurrency();
        $this->currencySymbol = $currency->getSymbol();

        return new EntityCollectionQueryResponse(
            $repoStock
                ->findByCurrency(
                    $currency,
                    $limit ? $limit + 1 : null,
                    $limit ? $page * $limit : 0,
                    $sortField,
                    $sortDir
                ),
            $limit,
            $page,
            $repoStock->countByCurrency($currency)
        );
    }

    public function byCode(
        string $code
    ): Stock {
        $repoStock = $this->stockPersistence->getRepository();

        return $repoStock->findByIdOrThrowException($code);
    }
}
