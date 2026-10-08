<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Number\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

class Number implements NumberInterface
{
    /** @var numeric-string */
    public const string VALUE_MIN = '-9999999999999.9999999999999';

    /** @var numeric-string */
    public const string VALUE_MAX = '9999999999999.9999999999999';
    public const int DECIMALS_MAX = 13;

    protected string $numberPropertyName = 'number';

    /** @var numeric-string */
    protected string $valueMin = self::VALUE_MIN;

    /** @var numeric-string */
    protected string $valueMax = self::VALUE_MAX;
    protected int $maxDecimals = self::DECIMALS_MAX;

    /**
     * @psalm-param numeric-string $value
     */
    public function __construct(protected string $value = '0')
    {
        $this->assertValidValue();
    }

    /**
     * @psalm-return numeric-string
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     *
     * @psalm-suppress LessSpecificReturnStatement
     * this operation is guaranteed to pruduce a numeric-string, but inference can't understand it
     * @psalm-suppress MoreSpecificReturnType
     */
    #[\Override]
    public function getValue(): string
    {
        return $this->value;
    }

    public function getMaxDecimals(): int
    {
        return $this->maxDecimals;
    }

    public function getValueFormatted(): string
    {
        $decimalStrSuffix = '.' . str_pad('', $this->getMaxDecimals(), '0');
        if (false !== $pos = strpos($this->getValue(), '.')) {
            $decimalStr = substr($this->getValue(), $pos + 1);
            $decimalStrLength = strlen($decimalStr);
            $decimalStrSuffix = str_pad('', $this->getMaxDecimals() - $decimalStrLength, '0');
        }

        return $this->getValue() . $decimalStrSuffix;
    }

    public function greater(NumberInterface $operand): bool
    {
        $numberOperation = new NumberOperation();

        return $numberOperation->greater($this->maxDecimals, $this, $operand);
    }

    #[\Override]
    public function same(NumberInterface $number): bool
    {
        $numberOperation = new NumberOperation();

        return $numberOperation->same($this->maxDecimals, $this, $number);
    }

    public function smaller(NumberInterface $operand): bool
    {
        $numberOperation = new NumberOperation();

        return $numberOperation->smaller($this->maxDecimals, $this, $operand);
    }

    public function smallerOrEqual(NumberInterface $operand): bool
    {
        $numberOperation = new NumberOperation();

        return $numberOperation->smallerOrEqual($this->maxDecimals, $this, $operand);
    }

    #[\Override]
    public function different(NumberInterface $number): bool
    {
        $numberOperation = new NumberOperation();

        return $numberOperation->different($this->maxDecimals, $this, $number);
    }

    private function assertValidValue(): void
    {
        $this->assertValueFormat();
        $this->trim();
        $this->assertDecimalPlaces();
        $this->assertValueWithinLimits();
    }

    private function assertValueFormat(): void
    {
        if (0 === preg_match('/^(?:-)?\d+(?:\.{1}\d+)?$/', $this->getValue())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'numberFormat',
                    [],
                    'MiCarteraDomain'
                ),
                $this->numberPropertyName . '.value'
            );
        }
    }

    private function trim(): void
    {
        preg_match('/^(-)?(\d+)(?:(\.)?(\d+))?$/', $this->getValue(), $matches);
        $hole = intval($matches[2]);
        $decimals = isset($matches[4]) ? rtrim($matches[4], '0') : '';

        /** @psalm-var numeric-string */
        $this->value = ($hole || strlen($decimals) ? $matches[1] : '') . (string) $hole . (strlen($decimals) ? '.' . $decimals : '');
    }

    protected function assertDecimalPlaces(): void
    {
        if (0 === $this->getMaxDecimals()) {
            $regex = '/^(?:-)?\d+$/';
        } else {
            $regex = '/^(?:-)?\d+(?:\.{1}\d{1,' . $this->maxDecimals . '})?$/';
        }
        if (0 === preg_match($regex, $this->getValue())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'numberPrecision',
                    ['precision' => $this->maxDecimals],
                    'MiCarteraDomain'
                ),
                $this->numberPropertyName . '.value'
            );
        }
    }

    /**
     * @psalm-param numeric-string $min
     * @psalm-param numeric-string $max
     */
    protected function assertValueWithinLimits(): void
    {
        if (
            -1 === bccomp($this->getValue(), $this->valueMin, $this->maxDecimals)
            || 1 === bccomp($this->getValue(), $this->valueMax, $this->maxDecimals)
        ) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'enterNumberBetween',
                    ['minimum' => $this->valueMin, 'maximum' => $this->valueMax],
                    'MiCarteraDomain'
                ),
                $this->numberPropertyName . '.value'
            );
        }
    }
}
