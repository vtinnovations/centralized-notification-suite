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
use VTInnovations\CentralizedNotificationSuite\Runtime\InstallationHosts;

/**
 * Normalisation may change how a host is spelled. It may never change which host it is.
 */
class InstallationHostsTest extends TestCase
{
    #[DataProvider('equivalentSpellings')]
    public function testFoldsSpellingsOfTheSameHost(string $input, string $expected): void
    {
        $this->assertSame($expected, InstallationHosts::normalise($input));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function equivalentSpellings(): iterable
    {
        yield 'uppercase' => ['Example.COM', 'example.com'];
        yield 'root dot' => ['example.com.', 'example.com'];
        yield 'explicit port' => ['example.com:8443', 'example.com'];
        yield 'written as a URL' => ['https://example.com/some/path', 'example.com'];
        yield 'unicode form' => ['münchen.de', 'xn--mnchen-3ya.de'];
        yield 'punycode form' => ['XN--MNCHEN-3YA.DE', 'xn--mnchen-3ya.de'];
        yield 'ipv4' => ['127.0.0.1', '127.0.0.1'];
        yield 'ipv4 with port' => ['127.0.0.1:8080', '127.0.0.1'];
        yield 'ipv6 keeps its colons' => ['2001:db8::1', '2001:db8::1'];
        yield 'bracketed ipv6 with port' => ['[2001:db8::1]:8443', '[2001:db8::1]'];
    }

    #[DataProvider('unusableValues')]
    public function testRejectsValuesThatAreNotOneHost(string $input): void
    {
        $this->assertNull(InstallationHosts::normalise($input));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function unusableValues(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
        yield 'wildcard' => ['*.example.com'];
        yield 'embedded space' => ['exa mple.com'];
        yield 'leading hyphen' => ['-bad.example'];
        yield 'a path' => ['example.com/admin'];
        yield 'scheme only' => ['http://'];
    }

    /**
     * The rule that matters most: hosts which differ by a label are different identities.
     * If any pair here collapsed, one licence would silently cover sites it never bought.
     */
    public function testRelatedHostsRemainDistinct(): void
    {
        $related = [
            'example.com',
            'www.example.com',
            'shop.example.com',
            'admin.shop.example.com',
            'example.com.evil.test',
            'notexample.com',
        ];

        $normalised = array_map(
            static fn (string $host): string => (string) InstallationHosts::normalise($host),
            $related,
        );

        $this->assertCount(\count($related), array_unique($normalised));
    }
}
