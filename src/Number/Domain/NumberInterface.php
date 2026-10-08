<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Number\Domain;

interface NumberInterface
{
    /** @return numeric-string */
    public function getValue(): string;

    public function same(NumberInterface $number): bool;

    public function different(NumberInterface $number): bool;
}
