<?php

declare(strict_types=1);

namespace Tests\unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Xver\MiCartera\Domain\Kernel;

#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    public function testKernelBootsInConfiguredEnvironments(): void
    {
        foreach (['prod', 'dev', 'test'] as $environment) {
            $kernel = new Kernel($environment, false);

            try {
                $kernel->boot();
                self::assertSame($environment, $kernel->getEnvironment());
            } finally {
                $kernel->shutdown();
            }
        }
    }

    public function testKernelBootsInAnUnregisteredEnvironment(): void
    {
        $kernel = new Kernel('staging', false);

        try {
            $kernel->boot();
            self::assertSame('staging', $kernel->getEnvironment());
        } finally {
            $kernel->shutdown();
        }
    }
}
