<?php

declare(strict_types=1);

namespace Xver\MiCartera\Domain\Account\Domain;

use Xver\MiCartera\Domain\Currency\Domain\CurrencyRepositoryInterface;
use Xver\PhpAppCoreBundle\Entity\Domain\EntityRepositoryInterface;
use Xver\SymfonyAuthBundle\Account\Domain\AccountPersistenceInterface as AuthAccountPersistenceInterface;

interface AccountPersistenceInterface extends AuthAccountPersistenceInterface
{
    /**
     * @return AccountRepositoryInterface
     */
    #[\Override]
    public function getRepository(): EntityRepositoryInterface;

    /**
     * @return CurrencyRepositoryInterface
     */
    public function getRepositoryForCurrency(): EntityRepositoryInterface;
}
