<?php

declare(strict_types=1);

namespace Tests\integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Uuid;
use Xver\MiCartera\Domain\Account\Domain\Account;
use Xver\MiCartera\Domain\Account\Infrastructure\Doctrine\AccountRepository;
use Xver\MiCartera\Domain\Currency\Domain\Currency;
use Xver\MiCartera\Domain\Currency\Infrastructure\Doctrine\CurrencyRepository;
use Xver\MiCartera\Domain\Exchange\Domain\Exchange;
use Xver\MiCartera\Domain\Exchange\Infrastructure\Doctrine\ExchangeRepository;
use Xver\MiCartera\Domain\Stock\Domain\Stock;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividend;
use Xver\MiCartera\Domain\Stock\Domain\Dividend\CashDividendMoneyVO;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Acquisition;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\Liquidation;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\StockRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\Accounting\MovementRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendRepository;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Dividend\CashDividendPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\AcquisitionPersistence;
use Xver\MiCartera\Domain\Stock\Infrastructure\Doctrine\Transaction\LiquidationPersistence;

/**
 * @internal
 */
#[CoversNothing]
class LazynessTest extends IntegrationTestCase
{
    private Account $account;
    private Currency $currency;
    private Exchange $exchange;
    private Stock $stock;
    private Acquisition $acquisition;
    private Liquidation $liquidation;
    private array $classesMethods = [];

    public function testLazzyDoesNotThrowException(): void
    {
        $this->getClassesMethods();
        $this->loadEntities();
        $instance = new AccountRepository(self::$registry);
        $this->invokeMethod($instance, 'findByIdentifier', $this->account->getIdentifier());
        $this->invokeMethod($instance, 'findByIdentifierOrThrowException', $this->account->getIdentifier());
        $instance = new CurrencyRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', $this->currency->getIso3());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->currency->getIso3());
        $this->invokeMethod($instance, 'all');
        $instance = new StockRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', $this->stock->getId());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->stock->getId());
        $this->invokeMethod($instance, 'findByCurrency', $this->currency);
        $this->invokeMethod($instance, 'countByCurrency', $this->currency);
        $instance = new ExchangeRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', $this->exchange->getCode());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->exchange->getCode());
        $this->invokeMethod($instance, 'all');
        $instance = new AcquisitionRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', $this->acquisition->getId());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->acquisition->getId());
        $this->invokeMethod($instance, 'findByAccountStockWithActionableAmountAndDateAtOrBefore', $this->account, $this->stock, new \DateTime('first day of january', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'findByStockId', $this->stock);
        $this->invokeMethod($instance, 'assertNoTransWithSameAccountStockOnDateTime', $this->account, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'findByAccountWithActionableAmount', $this->account, 'ASC');
        $this->invokeMethod($instance, 'portfolioSummary', $this->account);
        $this->invokeMethod($instance, 'totalAmountForAccountStockAtOrBefore', $this->account, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')));
        $instance = new LiquidationRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', $this->liquidation->getId());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->liquidation->getId());
        $this->invokeMethod($instance, 'findByStockId', $this->stock);
        $this->invokeMethod($instance, 'assertNoTransWithSameAccountStockOnDateTime', $this->account, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'findByAccountStockAndDateAtOrAfter', $this->account, $this->stock, new \DateTime('2 years ago', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'totalAmountForAccountStockAtOrBefore', $this->account, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')));
        $this->loadEntities();
        $cashDividend = new CashDividend(
            new CashDividendPersistence(self::$registry),
            new AcquisitionPersistence(self::$registry),
            new LiquidationPersistence(self::$registry),
            $this->acquisition->getStock(),
            $this->acquisition->getAccount(),
            $this->acquisition->getDateTimeUtc(),
            new CashDividendMoneyVO('0.25', $this->acquisition->getAccount()->getCurrency()),
            new CashDividendMoneyVO('0', $this->acquisition->getAccount()->getCurrency())
        );
        $instance = new CashDividendRepository(self::$registry);
        $this->invokeMethod($instance, 'findById', Uuid::v4());
        $this->invokeMethod($instance, 'findByIdOrThrowException', $cashDividend->getId());
        $this->invokeMethod($instance, 'assertNoCashDividendOnDateTime', $this->account, $this->stock, new \DateTime('now', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'findByAccountStockAtOrAfter', $this->account, $this->stock, new \DateTime('2 years ago', new \DateTimeZone('UTC')));
        $this->invokeMethod($instance, 'findByAccount', $this->account);
        $this->invokeMethod($instance, 'findByAccountStock', $this->account, $this->stock);
        $instance = new MovementRepository(self::$registry);
        $this->invokeMethod($instance, 'findByIdOrThrowException', $this->acquisition->getId(), $this->liquidation->getId());
        $this->invokeMethod($instance, 'findByAccountAndYear', $this->account, (int) new \DateTime('now', new \DateTimeZone('UTC'))->format('Y'), null);
        $this->invokeMethod($instance, 'accountingSummaryByAccount', $this->account, (int) new \DateTime('now', new \DateTimeZone('UTC'))->format('Y'));
        $this->invokeMethod($instance, 'findByAccountStockAcquisitionDateAfter', $this->account, $this->stock, new \DateTime('first day of january', new \DateTimeZone('UTC')));

        $allTested = true;
        $msgTemplate = 'Class `%s`, method `%s` not tested';
        $msg = '';
        foreach ($this->classesMethods as $class => $methods) {
            foreach ($methods as $method => $value) {
                if (true !== $value) {
                    $msg .= sprintf($msgTemplate, $class, $method) . PHP_EOL;
                    $allTested = false;
                }
            }
        }
        $this->assertTrue($allTested, $msg);
    }

    private function loadEntities(): void
    {
        $this->account = self::$registry->getRepository(Account::class)->findOneBy([]);
        $this->currency = self::$registry->getRepository(Currency::class)->findOneBy([]);
        $this->exchange = self::$registry->getRepository(Exchange::class)->findOneBy([]);
        $this->stock = self::$registry->getRepository(Stock::class)->findOneBy([]);
        $this->acquisition = self::$registry->getRepository(Acquisition::class)->findOneBy([]);
        $this->liquidation = self::$registry->getRepository(Liquidation::class)->findOneBy([]);
    }

    private function invokeMethod($instance, $method, ...$args): void
    {
        $this->resetEntityManager();
        $instance->{$method}(...$args);
        $this->assertArrayHasKey(get_class($instance), $this->classesMethods);
        $this->assertArrayHasKey($method, $this->classesMethods[get_class($instance)]);
        $this->classesMethods[get_class($instance)][$method] = true;
    }

    private function getClassesMethods(): void
    {
        $srcDir = realpath(__DIR__ . '/../../src');
        $finder = new Finder();
        $finder->files()->in($srcDir)->name('*Repository.php');
        $excluded = [
            'persist', 'remove', 'flush', 'beginTransaction', 'commit', 'rollBack',
        ];
        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $contents = file_get_contents($filePath);
            if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatch)) {
                $namespace = trim($nsMatch[1]);
                if (preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
                    $className = $classMatch[1];
                    $fqcn = $namespace . '\\' . $className;
                    if (class_exists($fqcn)) {
                        $reflection = new \ReflectionClass($fqcn);
                        if ($reflection->isAbstract()) {
                            continue;
                        }
                        $parentMethods = [];
                        $parent = $reflection->getParentClass();
                        while ($parent) {
                            foreach ($parent->getMethods(\ReflectionMethod::IS_PUBLIC) as $pm) {
                                $parentMethods[] = $pm->name;
                            }
                            $parent = $parent->getParentClass();
                        }
                        $methods = array_filter(
                            array_map(
                                fn($m) => $m->name,
                                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
                            ),
                            fn($name) => !str_starts_with($name, '__') && !in_array(strtolower($name), $excluded, true) && !in_array($name, $parentMethods, true)
                        );
                        foreach ($methods as $method) {
                            $this->classesMethods[$fqcn][$method] = false;
                        }
                    }
                }
            }
        }
    }
}
