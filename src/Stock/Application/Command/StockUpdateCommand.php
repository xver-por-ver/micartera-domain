<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Application\Command;

use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockPriceVO;

final class StockUpdateCommand
{
    public function __construct(private StockPersistenceInterface $stockPersistence) {}

    /**
     * @psalm-param numeric-string $price
     */
    public function invoke(
        string $code,
        string $name,
        string $price
    ): Stock {
        $stock = $this->stockPersistence->getRepository()->findByIdOrThrowException($code);

        return $stock->persistUpdate(
            $this->stockPersistence,
            $name,
            new StockPriceVO(
                $price,
                $stock->getCurrency()
            )
        );
    }
}
