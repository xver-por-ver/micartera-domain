<?php

declare(strict_types=1);

namespace Tests\unit\Stock\Application\Query\Portfolio;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Account\Domain\AccountPersistenceInterface;
use Xver\MiCartera\Domain\Account\Domain\AccountRepositoryInterface;
use Xver\MiCartera\Domain\Money\Domain\MoneyVO;
use Xver\MiCartera\Domain\Number\Domain\NumberOperation;
use Xver\MiCartera\Domain\Stock\Application\Query\Portfolio\PortfolioDTO;
use Xver\MiCartera\Domain\Stock\Application\Query\Portfolio\PortfolioQuery;
use Xver\MiCartera\Domain\Stock\Domain\Portfolio\SummaryVO;
use Xver\MiCartera\Domain\Stock\Domain\StockPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\StockRepositoryInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionPersistenceInterface;
use Xver\MiCartera\Domain\Stock\Domain\Transaction\AcquisitionRepositoryInterface;

/**
 * @internal
 */
#[CoversClass(PortfolioQuery::class)]
#[UsesClass(PortfolioDTO::class)]
#[UsesClass(MoneyVO::class)]
#[UsesClass(NumberOperation::class)]
class PortfolioQueryTest extends TestCase
{
    private AccountRepositoryInterface&Stub $repoAccount;
    private StockRepositoryInterface&Stub $repoStock;
    private AcquisitionRepositoryInterface&Stub $repoAcquisition;
    private StockPersistenceInterface&Stub $stockPersistence;
    private AccountPersistenceInterface&Stub $accountPersistence;
    private AcquisitionPersistenceInterface&Stub $acquisitionPersistence;

    public function setUp(): void
    {
        $this->repoAccount = $this->createStub(AccountRepositoryInterface::class);
        $this->repoStock = $this->createStub(StockRepositoryInterface::class);
        $this->repoAcquisition = $this->createStub(AcquisitionRepositoryInterface::class);
        $this->stockPersistence = $this->createStub(StockPersistenceInterface::class);
        $this->stockPersistence->method('getRepository')->willReturn($this->repoStock);
        $this->accountPersistence = $this->createStub(AccountPersistenceInterface::class);
        $this->accountPersistence->method('getRepository')->willReturn($this->repoAccount);
        $this->acquisitionPersistence = $this->createStub(AcquisitionPersistenceInterface::class);
        $this->acquisitionPersistence->method('getRepository')->willReturn($this->repoAcquisition);
    }

    public function testQueryCommandSucceeds(): void
    {
        $query = new PortfolioQuery($this->stockPersistence, $this->accountPersistence, $this->acquisitionPersistence);
        $portfolioDTO = $query->getPortfolio('test@example.com');
        $this->assertInstanceOf(PortfolioDTO::class, $portfolioDTO);
    }

    public function testGetStockPortfolioSummary(): void
    {
        $query = new PortfolioQuery($this->stockPersistence, $this->accountPersistence, $this->acquisitionPersistence);
        $summaryVO = $query->getStockPortfolioSummary('test@example.com', 'TEST');
        $this->assertInstanceOf(SummaryVO::class, $summaryVO);
    }
}
