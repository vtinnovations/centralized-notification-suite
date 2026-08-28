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
use VTInnovations\CentralizedNotificationSuite\Store\StateNotWritable;

class ActivationStoreTest extends TestCase
{
    private string $root;

    private ActivationStore $store;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/cns-store-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/var', 0o777, true);
        $this->store = new ActivationStore($this->root);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            @chmod($this->root.'/var/notification-state', 0o700);
            exec('rm -rf '.escapeshellarg($this->root));
        }
    }

    public function testReadsNothingWhenNothingIsStored(): void
    {
        $this->assertNull($this->store->read());
    }

    /**
     * The stored bytes are what the digest and signature were computed over. Round-tripping
     * them through a parser would produce a valid-looking file that fails verification.
     */
    public function testReturnsTheStoredBytesUnchanged(): void
    {
        // Deliberately not what a re-encoder would emit: odd spacing and an escaped slash.
        $bytes = '{"license_key" : "K",  "url":"https:\/\/example.com","n":1}';

        $this->store->write($bytes, (object) ['license_md5' => md5($bytes)]);

        $stored = $this->store->read();

        $this->assertSame($bytes, $stored['bytes']);
        $this->assertSame(md5($bytes), md5($stored['bytes']));
    }

    public function testKeepsItsFilesPrivate(): void
    {
        $this->store->write('{"n":1}', (object) ['a' => 1]);

        $this->assertSame('0700', substr(sprintf('%o', fileperms($this->store->directory())), -4));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($this->store->directory().'/record.json')), -4));
    }

    public function testLeavesNoStagedFilesBehind(): void
    {
        $this->store->write('{"n":1}', (object) ['a' => 1]);

        $stray = array_filter(
            scandir($this->store->directory()) ?: [],
            static fn (string $name): bool => str_starts_with($name, '.record') || str_starts_with($name, '.seal'),
        );

        $this->assertSame([], array_values($stray));
    }

    /**
     * The whole point of the staging and rollback: a site that already holds a working
     * licence must not lose it because a later write could not complete.
     */
    public function testAFailedWriteLeavesTheExistingStateIntact(): void
    {
        $good = '{"n":"original"}';
        $this->store->write($good, (object) ['license_md5' => md5($good)]);

        chmod($this->store->directory(), 0o500);

        try {
            $this->store->write('{"n":"replacement"}', (object) ['license_md5' => 'x']);
            $this->fail('A write into a read-only directory should not report success.');
        } catch (StateNotWritable) {
            // expected
        } finally {
            chmod($this->store->directory(), 0o700);
        }

        $this->assertSame($good, $this->store->read()['bytes']);
    }

    public function testRemovalTakesBothHalvesAway(): void
    {
        $this->store->write('{"n":1}', (object) ['a' => 1]);
        $this->store->clear();

        $this->assertNull($this->store->read());
        $this->assertFileDoesNotExist($this->store->directory().'/record.json');
        $this->assertFileDoesNotExist($this->store->directory().'/record.seal.json');
    }

    /**
     * A half-present pair is the one state a reader must never act on, because it would
     * either verify a record against a stale seal or reject a valid one.
     */
    public function testAHalfPresentPairIsTreatedAsNoState(): void
    {
        $this->store->write('{"n":1}', (object) ['a' => 1]);

        unlink($this->store->directory().'/record.seal.json');

        $this->assertNull($this->store->read());
    }
}
