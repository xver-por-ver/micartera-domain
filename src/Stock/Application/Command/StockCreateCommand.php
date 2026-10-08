<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command;

use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Exchange\Domain\ExchangePersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;

/**
 * @psalm-api
 */
class StockCreateCommand
{
    public function __construct(
        private StockPersistenceInterface $stockPersistence,
        private AccountPersistenceInterface $accountPersistence,
        private ExchangePersistenceInterface $exchangePersistence
    ) {}

    /**
     * @psalm-param numeric-string $price
     */
    public function invoke(
        string $code,
        string $name,
        string $price,
        string $accountIdentifier,
        string $exchange
    ): Stock {
        return new Stock(
            $this->stockPersistence,
            $code,
            $name,
            new StockPriceVO(
                $price,
                $this->accountPersistence
                    ->getRepository()
                    ->findByIdentifierOrThrowException($accountIdentifier)
                    ->getCurrency()
            ),
            $this->exchangePersistence->getRepository()->findByIdOrThrowException($exchange)
        );
    }
}
