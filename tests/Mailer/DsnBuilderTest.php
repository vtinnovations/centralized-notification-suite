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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Mailer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Exception\InvalidDsnException;
use VTInnovations\CentralizedNotificationSuite\Mailer\DsnBuilder;

class DsnBuilderTest extends TestCase
{
    private DsnBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DsnBuilder();
    }

    public function testImplicitTlsUsesTheSmtpsScheme(): void
    {
        $this->assertSame(
            'smtps://user%40ex.de:p%40ss@smtp.example.com:465',
            $this->builder->build('smtp.example.com', 465, 'user@ex.de', 'p@ss', 'ssl'),
        );
    }

    public function testStartTlsIsAParameterRatherThanAScheme(): void
    {
        $this->assertSame(
            'smtp://u:p@smtp.example.com:587?encryption=tls',
            $this->builder->build('smtp.example.com', 587, 'u', 'p', 'tls'),
        );
    }

    public function testCredentialsAreOmittedEntirelyWhenThereIsNoUsername(): void
    {
        $this->assertSame('smtp://localhost:25', $this->builder->build('localhost', 25, '', '', 'none'));
    }

    /**
     * Credentials routinely contain @ : / #, which would otherwise re-write the rest of the DSN.
     */
    public function testCredentialsAreUrlEncoded(): void
    {
        $dsn = $this->builder->build('h.example.com', 25, 'a@b.de', 'p:a/s#s', 'none');

        $this->assertStringContainsString('a%40b.de', $dsn);
        $this->assertStringContainsString('p%3Aa%2Fs%23s', $dsn);
        $this->assertSame('h.example.com', parse_url($dsn, PHP_URL_HOST), 'host must survive intact');
    }

    public function testAcceptsIpv6Literals(): void
    {
        $this->assertSame('smtp://[::1]:25', $this->builder->build('[::1]', 25, '', '', 'none'));
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalidInput(string $host, int $port, string $encryption, string $expectedMessage): void
    {
        $this->expectException(InvalidDsnException::class);
        $this->expectExceptionMessageMatches($expectedMessage);

        $this->builder->build($host, $port, '', '', $encryption);
    }

    public static function invalidProvider(): array
    {
        return [
            'empty host' => ['', 25, 'none', '/must not be empty/'],
            'host with space' => ['bad host', 25, 'none', '/Invalid host/'],
            // an @ would let a pasted value inject its own credentials into the DSN
            'host with at sign' => ['evil@example.com', 25, 'none', '/Invalid host/'],
            'host with slash' => ['example.com/x', 25, 'none', '/Invalid host/'],
            'port too low' => ['example.com', 0, 'none', '/Invalid port/'],
            'port too high' => ['example.com', 70000, 'none', '/Invalid port/'],
            'unknown encryption' => ['example.com', 25, 'quantum', '/Invalid encryption/'],
        ];
    }
}
