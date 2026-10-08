<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Money\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Number\Domain\Number;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-consistent-constructor
 */
class MoneyVO extends Number
{
    protected string $numberPropertyName = 'money';

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(string $value, private readonly Currency $currency)
    {
        if (MoneyVO::class === get_class($this)) {
            $this->maxDecimals = $this->currency->getDecimals();
        }
        parent::__construct($value);
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(MoneyVO $aux): static
    {
        $this->validateOperand($aux);

        $numberOperation = new NumberOperation();

        return new static(
            $numberOperation->add(
                $this->getMaxDecimals(),
                $this,
                $aux
            ),
            $this->getCurrency()
        );
    }

    public function subtract(MoneyVO $aux): static
    {
        $this->validateOperand($aux);

        $numberOperation = new NumberOperation();

        return new static(
            $numberOperation->subtract(
                $this->getMaxDecimals(),
                $this,
                $aux
            ),
            $this->getCurrency()
        );
    }

    public function percentageDifference(MoneyVO $aux, int $decimals = 2): Number
    {
        $this->validateOperand($aux);

        $numberOperation = new NumberOperation();

        return new Number(
            $numberOperation->percentageDifference(
                $this->getMaxDecimals(),
                $decimals,
                $this,
                $aux
            )
        );
    }

    protected function validateOperand(MoneyVO $aux): void
    {
        $this->assertOperandSameType($aux);
        $this->assertOperandSameCurrency($aux);
    }

    private function assertOperandSameType(MoneyVO $aux): void
    {
        if (!is_a($this, get_class($aux)) || !$aux instanceof static) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'operationRequiresBothOperandsWithSameType',
                    ['type1' => get_class($aux), 'type2' => get_class($this)],
                    'MiCarteraDomain'
                )
            );
        }
    }

    private function assertOperandSameCurrency(MoneyVO $aux): void
    {
        if (false === $this->getCurrency()->sameId($aux->getCurrency())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'operationRequiresBothOperandsWithSameCurrency',
                    ['currency1' => $aux->getCurrency()->getIso3(), 'currency2' => $this->getCurrency()->getIso3()],
                    'MiCarteraDomain'
                )
            );
        }
    }
}
