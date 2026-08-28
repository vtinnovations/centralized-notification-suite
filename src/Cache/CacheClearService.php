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

namespace VTInnovations\CentralizedNotificationSuite\Cache;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use VTInnovations\CentralizedNotificationSuite\Exception\CacheClearException;

/**
 * Rebuilds the production cache after MAILER_DSN changed.
 *
 * A subprocess rather than an in-process cache:clear: the running request already holds a
 * booted container built from the old environment, and clearing underneath it leaves the
 * request serving from files it has just deleted. A maintenance page is put up for the few
 * seconds the rebuild takes, and removed again even when the rebuild fails.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team).
 */
class CacheClearService
{
    private readonly string $maintenancePath;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $phpBinary,
        private readonly int $processTimeout,
        private readonly string $environment = 'prod',
    ) {
        $this->maintenancePath = rtrim($projectDir, '/\\').'/var/maintenance.html';
    }

    public function clearAndWarmup(): void
    {
        $this->enableMaintenance();

        try {
            $binary = $this->resolveBinary();

            // The environment the site actually runs in, not a hard-coded "prod": the compiled
            // container caches the resolved MAILER_DSN, so clearing the wrong environment
            // leaves the saved credentials inert -- and removing the value later makes the
            // stale container throw "Environment variable not found: MAILER_DSN".
            $env = '--env='.$this->environment;

            $this->run([$binary, 'bin/console', 'cache:clear', '--no-warmup', $env, '--no-interaction']);
            $this->run([$binary, 'bin/console', 'cache:warmup', $env, '--no-interaction']);
        } catch (ProcessFailedException $e) {
            throw new CacheClearException('Cache clear failed: '.$e->getMessage(), 0, $e);
        } finally {
            $this->disableMaintenance();
        }
    }

    private function enableMaintenance(): void
    {
        $varDir = \dirname($this->maintenancePath);

        if (!is_dir($varDir)) {
            mkdir($varDir, 0777, true);
        }

        file_put_contents($this->maintenancePath, $this->maintenanceHtml());
    }

    private function disableMaintenance(): void
    {
        if (file_exists($this->maintenancePath)) {
            unlink($this->maintenancePath);
        }
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command): void
    {
        $process = new Process($command, $this->projectDir, null, null, $this->processTimeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Finds a PHP CLI binary. The web SAPI's own path is tried first, then $PATH, then the
     * layouts Plesk and cPanel use -- where the web user often cannot stat the binary but can
     * still execute it, which is why those two are probed by running them.
     */
    private function resolveBinary(): string
    {
        if ('' !== $this->phpBinary) {
            return $this->phpBinary;
        }

        $version = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $candidates = [];

        if ('' !== PHP_BINARY) {
            $candidates[] = PHP_BINARY;
        }

        if (is_readable('/proc/self/exe') && false !== ($exe = readlink('/proc/self/exe')) && '' !== $exe) {
            $candidates[] = $exe;
        }

        foreach ($candidates as $candidate) {
            // php-fpm cannot run a console command; its sibling CLI binary can
            $candidate = str_replace(['php-fpm', '/sbin/'], ['php', '/bin/'], $candidate);

            if ('' !== $candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        if (false !== ($found = (new PhpExecutableFinder())->find(false)) && '' !== $found) {
            return $found;
        }

        foreach (['/usr/bin/php'.$version, '/usr/bin/php', '/usr/local/bin/php'] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $probes = [
            '/opt/plesk/php/'.$version.'/bin/php',
            '/opt/cpanel/ea-php'.str_replace('.', '', $version).'/root/usr/bin/php',
        ];

        foreach ($probes as $probe) {
            $test = new Process([$probe, '--version'], null, null, null, 5);
            $test->run();

            if ($test->isSuccessful()) {
                return $probe;
            }
        }

        throw new CacheClearException(
            'No PHP CLI binary found. Set centralized_notification_suite.mailer.php_binary in config/config.yaml.',
        );
    }

    private function maintenanceHtml(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="utf-8">
            <meta http-equiv="refresh" content="10">
            <title>Maintenance</title>
            <style>
            body { font-family: sans-serif; text-align: center; padding: 80px 20px; color: #444; background: #f9f9f9; }
            h1 { font-size: 1.8em; margin-bottom: .5em; }
            p { color: #777; }
            </style>
            </head>
            <body>
            <h1>Back in a moment</h1>
            <p>This site is being updated and will be available again shortly.</p>
            </body>
            </html>
            HTML;
    }
}
