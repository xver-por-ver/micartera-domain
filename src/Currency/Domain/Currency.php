<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Currency\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Currency implements EntityInterface
{
    final public const int LENGTH_ISO3 = 3;
    final public const int MAX_LENGTH_SYMBOL = 10;
    final public const int MIN_LENGTH_SYMBOL = 1;
    final public const int MAX_DECIMALS = 4;
    final public const int MIN_DECIMALS = 1;

    public function __construct(
        private readonly CurrencyPersistenceInterface $currencyPersistance,
        private string $iso3,
        private readonly string $symbol,
        private readonly int $decimals
    ) {
        $this->validIso3();
        $this->validSymbol();
        $this->validDecimals();
        $this->persistCreate();
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Currency) {
            throw new \InvalidArgumentException();
        }

        return 0 === strcmp($this->getIso3(), $otherEntity->getIso3());
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    private function validIso3(): void
    {
        if (self::LENGTH_ISO3 !== strlen($this->iso3)) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'invalidIso3',
                    [],
                    'MiCarteraDomain'
                ),
                'currency.iso3'
            );
        }
        $this->iso3 = strtoupper($this->iso3);
    }

    private function validSymbol(): void
    {
        $symbolLength = strlen($this->symbol);
        if ($symbolLength < self::MIN_LENGTH_SYMBOL || $symbolLength > self::MAX_LENGTH_SYMBOL) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'invalidCurrencySymbol',
                    ['minimum' => self::MIN_LENGTH_SYMBOL, 'maximum' => self::MIN_LENGTH_SYMBOL],
                    'MiCarteraDomain'
                ),
                'currency.symbol'
            );
        }
    }

    private function validDecimals(): void
    {
        if ($this->decimals < self::MIN_DECIMALS || $this->decimals > self::MAX_DECIMALS) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'enterNumberBetween',
                    ['minimum' => self::MIN_DECIMALS, 'maximum' => self::MAX_DECIMALS],
                    'MiCarteraDomain'
                ),
                'currency.amount'
            );
        }
    }

    private function persistCreate(): void
    {
        $repoCurrency = $this->currencyPersistance->getRepository();
        if (null !== $repoCurrency->findById($this->getIso3())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'currencyCodeAlreadyExists',
                    [],
                    'MiCarteraDomain'
                ),
                'currency.code'
            );
        }
        $this->currencyPersistance->persist($this);
        $this->currencyPersistance->flush();
    }
}
