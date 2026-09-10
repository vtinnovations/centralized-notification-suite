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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Token;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Token\NcTokenAliasResolver;

class NcTokenAliasResolverTest extends TestCase
{
    private NcTokenAliasResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new NcTokenAliasResolver();
    }

    #[DataProvider('mappingProvider')]
    public function testMapsNotificationCenterTokens(string $ncToken, string|null $expected): void
    {
        $this->assertSame($expected, $this->resolver->map($ncToken));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function mappingProvider(): iterable
    {
        yield 'form field loses its prefix' => ['form_email', 'email'];
        yield 'form metadata keeps a prefix' => ['formconfig_title', 'form_title'];
        yield 'whole submission' => ['raw_data', 'all_fields'];
        yield 'whole submission, non-empty only' => ['raw_data_filled', 'all_fields_filled'];
        yield 'environment host' => ['env_host', 'host'];
        yield 'admin address is unchanged' => ['admin_email', 'admin_email'];
        yield 'member fields are unchanged' => ['member_firstname', 'member_firstname'];
        yield 'unknown token has no mapping' => ['my_own_extension_token', null];
    }

    /**
     * formconfig_ must win over form_, or "formconfig_id" would become "config_id".
     */
    public function testLongerPrefixWins(): void
    {
        $this->assertSame('form_id', $this->resolver->map('formconfig_id'));
    }

    public function testRewritesTokensInABody(): void
    {
        $before = '<p>From ##form_name## (##form_email##)</p>##raw_data_filled##<p>on ##env_host##</p>';
        $after = '<p>From ##name## (##email##)</p>##all_fields_filled##<p>on ##host##</p>';

        $this->assertSame($after, $this->resolver->rewrite($before));
    }

    public function testLeavesUnknownTokensAloneAndReportsThem(): void
    {
        $body = 'Ref: ##my_own_token## and ##my_own_token## again, plus ##form_name##';

        $this->assertSame(
            'Ref: ##my_own_token## and ##my_own_token## again, plus ##name##',
            $this->resolver->rewrite($body),
        );

        $this->assertSame(['my_own_token' => 2], $this->resolver->getUnmapped());
    }

    public function testDoesNotTouchTextWithoutTokens(): void
    {
        $this->assertSame('<p>Nothing to do</p>', $this->resolver->rewrite('<p>Nothing to do</p>'));
        $this->assertSame([], $this->resolver->getUnmapped());
    }

    public function testResetClearsTheReport(): void
    {
        $this->resolver->rewrite('##unknown_one##');
        $this->assertNotSame([], $this->resolver->getUnmapped());

        $this->resolver->reset();
        $this->assertSame([], $this->resolver->getUnmapped());
    }
}
