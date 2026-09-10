<?php

/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

declare(strict_types=1);

namespace VTInnovations\CentralizedNotificationSuite\Tests\Cache;

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Cache\CacheClearService;

/**
 * The shape of the console command, without running one.
 *
 * The memory limit is the part worth pinning. Contao's warmup loads every installed bundle's
 * XLIFF language files in one process; under a stock 128M CLI limit that dies inside
 * XliffFileLoader, the rebuild fails, and the SMTP credentials the administrator just saved stay
 * inert until somebody clears the cache by hand.
 */
class CacheClearServiceTest extends TestCase
{
    public function testTheMemoryLimitIsRaisedForTheSubprocess(): void
    {
        $command = $this->command($this->service('-1'), ['cache:warmup', '--env=prod', '--no-interaction']);

        $this->assertSame(
            ['/usr/bin/php', '-d', 'memory_limit=-1', 'bin/console', 'cache:warmup', '--env=prod', '--no-interaction'],
            $command,
        );
    }

    public function testAFixedLimitIsPassedThrough(): void
    {
        $command = $this->command($this->service('512M'), ['cache:clear', '--no-warmup']);

        $this->assertSame(
            ['/usr/bin/php', '-d', 'memory_limit=512M', 'bin/console', 'cache:clear', '--no-warmup'],
            $command,
        );
    }

    /**
     * An empty setting means "leave the CLI php.ini alone", which a host that kills processes by
     * resident size may well want. It must not turn into "-d memory_limit=".
     */
    public function testAnEmptySettingAddsNoFlag(): void
    {
        $command = $this->command($this->service(''), ['cache:warmup']);

        $this->assertSame(['/usr/bin/php', 'bin/console', 'cache:warmup'], $command);
    }

    /**
     * The environment is taken from the kernel, never hard-coded: the compiled container caches
     * the resolved MAILER_DSN, so rebuilding the wrong one leaves the saved credentials inert.
     */
    public function testTheEnvironmentComesFromTheKernel(): void
    {
        $source = (string) file_get_contents((new \ReflectionClass(CacheClearService::class))->getFileName());

        $this->assertStringContainsString("'--env='.\$this->environment", $source);
        $this->assertStringNotContainsString("'--env=prod'", $source);
    }

    private function service(string $memoryLimit): CacheClearService
    {
        return new CacheClearService(sys_get_temp_dir(), '/usr/bin/php', 120, $memoryLimit);
    }

    /**
     * @param list<string> $arguments
     *
     * @return list<string>
     */
    private function command(CacheClearService $service, array $arguments): array
    {
        $method = new \ReflectionMethod($service, 'command');

        return $method->invoke($service, '/usr/bin/php', $arguments);
    }
}
