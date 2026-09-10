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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use VTInnovations\CentralizedNotificationSuite\Http\SecurePost;
use VTInnovations\CentralizedNotificationSuite\Http\ServiceEndpoints;
use VTInnovations\CentralizedNotificationSuite\Runtime\Activation;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRecord;
use VTInnovations\CentralizedNotificationSuite\Runtime\UsageSignals;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Ordinary logs must never carry the material that makes a licence forgeable or reusable.
 *
 * A log file is copied into backups, shipped to aggregators and pasted into support tickets.
 * A licence key or a signed payload that reaches one has effectively been published, so this
 * is checked as a property of the code rather than left to reviewer discipline.
 */
class LogSecrecyTest extends TestCase
{
    /**
     * Values that must never appear in a log line, and the names that usually carry them.
     */
    private const FORBIDDEN_KEYS = [
        'license_payload_b64', 'license_md5', 'request_packet', 'response_packet',
        'request_body', 'response_body', 'nonce', 'signature',
        'request_sha256', 'response_sha256', 'licence_key_sha256', 'license_key_sha256',
        'licence_key_length', 'license_key_length',
    ];

    public function testAFailedSignalDoesNotLogItsPayload(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            public function log($level, $message, array $context = []): void
            {
                $this->lines[] = $message.' '.json_encode($context);
            }
        };

        $issuer = new SignedPackageFactory();
        $now = 1800000000;
        $record = ActivationRecord::fromDocument($issuer->record($now), $now);
        $activation = Activation::granted($record, 'example.com');

        // A transport that always fails, so the failure-logging path is the one exercised.
        $post = new class extends SecurePost {
            public function send(string $url, string $body, array $headers = [], int $timeout = 15): never
            {
                throw new \VTInnovations\CentralizedNotificationSuite\Http\TransportFailed('unreachable');
            }
        };

        $signals = new UsageSignals($post, new RequestStack(), $logger);
        $signals->invoked('example.com');
        // Deferred by design, so the delivery attempt only happens on flush.
        $signals->flush();

        $this->assertNotSame([], $logger->lines);

        foreach ($logger->lines as $line) {
            $this->assertStringNotContainsString($record->key, $line, 'A licence key reached a log line.');
            $this->assertStringNotContainsString('CNS-TEST-KEY', $line);
        }
    }

    /**
     * A static sweep, because the risky lines are the ones nobody thought to run in a test:
     * an error path in a rarely reached branch still writes to the same log.
     */
    #[DataProvider('sourceFiles')]
    public function testSourceNeverLogsForbiddenFields(string $file): void
    {
        $source = (string) file_get_contents($file);

        // Only look at lines that actually reach a logger.
        $lines = array_filter(
            explode("\n", $source),
            static fn (string $line): bool => (bool) preg_match('/->(debug|info|notice|warning|error|critical|alert|emergency)\(/', $line),
        );

        $violations = [];

        foreach ($lines as $line) {
            foreach (self::FORBIDDEN_KEYS as $forbidden) {
                if (str_contains($line, $forbidden)) {
                    $violations[] = \sprintf('%s logs "%s"', basename($file), $forbidden);
                }
            }

            // Whole-body variables are the other common way a packet reaches a log.
            if (preg_match('/->(debug|info|notice|warning|error|critical|alert|emergency)\([^)]*\$(raw|body|payload|response)\b/', $line)) {
                $violations[] = \sprintf('%s appears to log a raw body', basename($file));
            }
        }

        // Asserted unconditionally so a file with no logging still counts as checked rather
        // than being reported as a test that did nothing.
        $this->assertSame([], $violations);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function sourceFiles(): iterable
    {
        $root = \dirname(__DIR__, 2).'/src';

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                yield str_replace($root.'/', '', $file->getPathname()) => [$file->getPathname()];
            }
        }
    }

    /**
     * The key travels in exactly one place, and this pins that fact so a future change has to
     * be deliberate about it.
     */
    public function testTheKeyIsSentToOneDestinationOnly(): void
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 2).'/src/Runtime/UsageSignals.php');

        $this->assertStringContainsString("'key' => \$key", $source);
        $this->assertStringContainsString('ServiceEndpoints::signal()', $source);
        $this->assertSame(1, substr_count($source, "'key' => "));
        $this->assertSame('https://www.v-t.one/rest/api/v1/log-envoke', ServiceEndpoints::signal());
    }
}
