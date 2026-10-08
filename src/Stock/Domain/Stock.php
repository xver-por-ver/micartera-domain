<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Stock\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Stock implements EntityInterface
{
    final public const int MAX_CODE_LENGTH = 4;
    final public const int MIN_CODE_LENGTH = 1;
    final public const int MAX_NAME_LENGTH = 255;
    final public const int MIN_NAME_LENGTH = 1;

    private readonly Currency $currency;

    public function __construct(
        private readonly StockPersistenceInterface $stockPersistence,
        private string $code,
        private string $name,
        private StockPriceVO $price,
        private readonly Exchange $exchange
    ) {
        $this
            ->validCode()
            ->validName()
        ;
        $this->currency = $price->getCurrency();
        $this->persistCreate();
    }

    public function getId(): string
    {
        return $this->code;
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Stock) {
            throw new \InvalidArgumentException();
        }

        return 0 === strcmp($this->getId(), $otherEntity->getId());
    }

    private function validCode(): self
    {
        $length = mb_strlen($this->code);
        if ($length > self::MAX_CODE_LENGTH || $length < self::MIN_CODE_LENGTH) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'stringLength',
                    ['minimum' => self::MIN_CODE_LENGTH, 'maximum' => self::MAX_CODE_LENGTH],
                    'MiCarteraDomain'
                ),
                'stock.code'
            );
        }
        $this->code = mb_strtoupper($this->code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function validName(): self
    {
        $length = mb_strlen($this->name);
        if ($length > self::MAX_NAME_LENGTH || $length < self::MIN_NAME_LENGTH) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'stringLength',
                    ['minimum' => self::MIN_NAME_LENGTH, 'maximum' => self::MAX_NAME_LENGTH],
                    'MiCarteraDomain'
                ),
                'stock.name'
            );
        }

        return $this;
    }

    private function validPrice(StockPriceVO $price): self
    {
        if (false === $this->getCurrency()->sameId($price->getCurrency())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'otherCurrencyExpected',
                    [
                        'received' => $price->getCurrency()->getIso3(),
                        'expected' => $this->getCurrency()->getIso3(),
                    ],
                    'MiCarteraDomain'
                ),
                'stock.price'
            );
        }

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getPrice(): StockPriceVO
    {
        return new StockPriceVO($this->price->getValue(), $this->getCurrency());
    }

    public function getExchange(): Exchange
    {
        return $this->exchange;
    }

    private function persistCreate(): void
    {
        $repoStock = $this->stockPersistence->getRepository();
        if (null !== $repoStock->findById($this->getId())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'stockExists',
                    [],
                    'MiCarteraDomain'
                ),
                'stock.code'
            );
        }
        $this->stockPersistence->persist($this);
        $this->stockPersistence->flush();
    }

    public function persistUpdate(
        StockPersistenceInterface $stockPersistence,
        string $name,
        StockPriceVO $price
    ): self {
        $this->name = $name;
        $this
            ->validName()
            ->validPrice($price)
        ;
        $this->price = $price;
        $stockPersistence->persist($this);
        $stockPersistence->flush();

        return $this;
    }

    public function persistRemove(
        StockPersistenceInterface $stockPersistence
    ): void {
        $repoAcquisition = $stockPersistence->getRepositoryForAcquisition();
        if (0 !== $repoAcquisition->findByStockId($this)->count()) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'stockHasTransactions',
                    [],
                    'MiCarteraDomain'
                ),
                'stock.code'
            );
        }
        $stockPersistence->remove($this);
        $stockPersistence->flush();
    }
}
