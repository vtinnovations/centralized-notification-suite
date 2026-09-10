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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Store;

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Store\ActivationStore;

/**
 * The durable floor that makes a withdrawal stick.
 *
 * Every signature on yesterday's record.json is genuine and still verifies, so no amount of
 * cryptography can tell it apart from today's. The only thing that can is a number this
 * installation keeps for itself, which is what the watermark is -- and why it has to outlive
 * the record it was taken from.
 */
class WatermarkTest extends TestCase
{
    private string $projectDir;

    private ActivationStore $store;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/cns-watermark-'.bin2hex(random_bytes(6));
        mkdir($this->projectDir.'/var', 0o777, true);

        $this->store = new ActivationStore($this->projectDir);
    }

    protected function tearDown(): void
    {
        $directory = $this->store->directory();

        foreach (glob($directory.'/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($directory);
        @rmdir($this->projectDir.'/var');
        @rmdir($this->projectDir);
    }

    public function testNothingIsRecordedBeforeAnyStateIsAccepted(): void
    {
        $this->assertNull($this->store->watermark());
    }

    public function testItRemembersTheVersionAndTheStateItArrivedIn(): void
    {
        $this->store->raiseWatermark(7, 'valid');

        $this->assertSame(['version' => 7, 'status' => 'valid'], $this->store->watermark());
    }

    public function testItRises(): void
    {
        $this->store->raiseWatermark(7, 'valid');
        $this->store->raiseWatermark(9, 'revoked');

        $this->assertSame(['version' => 9, 'status' => 'revoked'], $this->store->watermark());
    }

    /**
     * The whole point. A caller recording something older is recording something that has
     * already been superseded, and honouring it would reopen the downgrade.
     */
    public function testItNeverFalls(): void
    {
        $this->store->raiseWatermark(9, 'revoked');
        $this->store->raiseWatermark(4, 'valid');

        $this->assertSame(['version' => 9, 'status' => 'revoked'], $this->store->watermark());
    }

    /**
     * Removing the licence must not be the way to undo a withdrawal.
     *
     * Without this, the sequence is: receive a revocation, click "Remove licence", drop
     * yesterday's record.json back in place, and the installation is licensed again -- every
     * signature intact, every check passed.
     */
    public function testClearingTheRecordLeavesTheFloorInPlace(): void
    {
        $this->store->write('{"license_version":9}', (object) ['license_md5' => 'x']);
        $this->store->raiseWatermark(9, 'revoked');

        $this->store->clear();

        $this->assertNull($this->store->read(), 'The record should be gone.');
        $this->assertSame(['version' => 9, 'status' => 'revoked'], $this->store->watermark());
    }

    /**
     * A hand-edited or truncated watermark must read as "nothing recorded" rather than
     * throwing on every request.
     */
    public function testAnUnreadableWatermarkIsIgnored(): void
    {
        $this->store->raiseWatermark(9, 'revoked');
        file_put_contents($this->store->directory().'/watermark.json', 'not json');

        $this->assertNull($this->store->watermark());
    }
}
