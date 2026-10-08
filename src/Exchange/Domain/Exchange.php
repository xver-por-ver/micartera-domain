<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Exchange\Domain;

use Symfony\Component\Translation\TranslatableMessage;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityInterface;
use Xver\PhpAppCoreBundle\Exception\Domain\DomainViolationException;

/**
 * @psalm-api
 */
class Exchange implements EntityInterface
{
    final public const int MAX_CODE_LENGTH = 12;
    final public const int MIN_CODE_LENGTH = 3;
    final public const int MAX_NAME_LENGTH = 255;
    final public const int MIN_NAME_LENGTH = 1;

    public function __construct(
        private ExchangePersistenceInterface $exchangePersistence,
        private string $code,
        private string $name,
    ) {
        $this
            ->validCode()
            ->validName()
        ;
        $this->persistCreate();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    #[\Override]
    public function sameId(EntityInterface $otherEntity): bool
    {
        if (!$otherEntity instanceof Exchange) {
            throw new \InvalidArgumentException();
        }

        return 0 === strcmp($this->getCode(), $otherEntity->getCode());
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
                'exchange.code'
            );
        }
        $this->code = mb_strtoupper($this->code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    final public function validName(): self
    {
        $length = mb_strlen($this->name);
        if ($length > self::MAX_NAME_LENGTH || $length < self::MIN_NAME_LENGTH) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'stringLength',
                    ['minimum' => self::MIN_NAME_LENGTH, 'maximum' => self::MAX_NAME_LENGTH],
                    'MiCarteraDomain'
                ),
                'exchange.name'
            );
        }

        return $this;
    }

    private function persistCreate(): void
    {
        $repoExchange = $this->exchangePersistence->getRepository();
        if (null !== $repoExchange->findById($this->getCode())) {
            throw new DomainViolationException(
                new TranslatableMessage(
                    'ExchangeExists',
                    [],
                    'MiCarteraDomain'
                ),
                'exchange.code'
            );
        }
        $this->exchangePersistence->persist($this);
        $this->exchangePersistence->flush();
    }
}
