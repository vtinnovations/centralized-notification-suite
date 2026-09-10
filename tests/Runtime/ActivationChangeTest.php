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

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;
use VTInnovations\CentralizedNotificationSuite\Distribution\SealedPackage;
use VTInnovations\CentralizedNotificationSuite\Http\IssuerExchange;
use VTInnovations\CentralizedNotificationSuite\Http\SecurePost;
use VTInnovations\CentralizedNotificationSuite\Http\TransportFailed;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationChange;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRefused;
use VTInnovations\CentralizedNotificationSuite\Runtime\InstallationHosts;
use VTInnovations\CentralizedNotificationSuite\Store\ActivationStore;
use VTInnovations\CentralizedNotificationSuite\Tests\Fixtures\SignedPackageFactory;

/**
 * The administrator path end to end, with the remote side stubbed.
 *
 * No live call is made: these tests must run in CI and must never depend on, or reach, the
 * issuing service.
 */
class ActivationChangeTest extends TestCase
{
    private SignedPackageFactory $issuer;

    private ActivationStore $store;

    private ActivationGate $gate;

    private string $root;

    private int $now;

    protected function setUp(): void
    {
        $this->issuer = new SignedPackageFactory();
        $this->now = time();

        $this->root = sys_get_temp_dir().'/cns-change-'.bin2hex(random_bytes(6));
        mkdir($this->root.'/var', 0o777, true);

        $this->store = new ActivationStore($this->root);
        $this->gate = new ActivationGate($this->store, $this->issuer->keyring(), $this->hosts(['example.com']));
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    public function testActivationStoresTheRecordAndOpensTheGate(): void
    {
        $this->assertFalse($this->gate->current()->granted);

        $change = $this->change($this->exchangeReturning($this->package()));
        $result = $change->activate('CNS-TEST-KEY');

        $this->assertTrue($result->granted);
        $this->assertSame('example.com', $result->host);
        $this->assertNotNull($this->store->read());
    }

    public function testAnEmptyKeyNeverReachesTheNetwork(): void
    {
        $change = $this->change($this->exchangeThatMustNotBeCalled());

        $this->expectExceptionMessage('key_missing');

        $change->activate('   ');
    }

    /**
     * The most important failure behaviour in the whole product: an outage must not cost a
     * site the licence it already holds.
     */
    public function testAFailedRefreshLeavesTheExistingRecordInPlace(): void
    {
        $this->change($this->exchangeReturning($this->package()))->activate('CNS-TEST-KEY');
        $this->assertTrue($this->gate->current()->granted);

        $failing = new class(new SecurePost(), $this->issuer->keyring()) extends IssuerExchange {
            public function refresh(string $key, string $host, int $now, int $currentVersion): SealedPackage
            {
                throw new TransportFailed('unreachable');
            }
        };

        try {
            $this->change($failing)->refresh();
            $this->fail('A failed refresh should be reported, not silently ignored.');
        } catch (ActivationRefused $e) {
            $this->assertSame('unreachable', $e->category());
        }

        // Still licensed, still the same record.
        $this->assertTrue($this->gate->current()->granted);
        $this->assertSame(7, $this->gate->current()->version());
    }

    /**
     * An older record is genuine and passes every signature check, so replaying yesterday's
     * package is a real way to undo a revocation. Only the version comparison stops it.
     */
    public function testAnOlderRecordCannotReplaceANewerOne(): void
    {
        $this->change($this->exchangeReturning($this->package(['license_version' => 9])))->activate('CNS-TEST-KEY');
        $this->assertSame(9, $this->gate->current()->version());

        $this->expectExceptionMessage('would_roll_back');

        $this->change($this->exchangeReturning($this->package(['license_version' => 4])))->refresh();
    }

    public function testRemovalReturnsTheInstallationToItsUnlicensedState(): void
    {
        $change = $this->change($this->exchangeReturning($this->package()));
        $change->activate('CNS-TEST-KEY');

        $this->assertTrue($this->gate->current()->granted);

        $result = $change->remove();

        $this->assertFalse($result->granted);
        $this->assertSame('no_record', $result->reason);
        $this->assertNull($this->store->read());
    }

    /**
     * Removal is local. An administrator must be able to revoke state on their own server
     * even when the issuing service cannot be reached.
     */
    public function testRemovalWorksWhileTheServiceIsUnreachable(): void
    {
        $this->change($this->exchangeReturning($this->package()))->activate('CNS-TEST-KEY');

        $offline = new class(new SecurePost(), $this->issuer->keyring()) extends IssuerExchange {
            public function activate(string $key, string $host, int $now): SealedPackage
            {
                throw new TransportFailed('unreachable');
            }

            public function refresh(string $key, string $host, int $now, int $currentVersion): SealedPackage
            {
                throw new TransportFailed('unreachable');
            }
        };

        $this->assertFalse($this->change($offline)->remove()->granted);
    }

    /**
     * With no configured domain there is nothing to bind a record to, and guessing a hostname
     * would produce a record bound to a name the site does not answer for.
     */
    public function testActivationIsRefusedWhenNoDomainIsConfigured(): void
    {
        $gate = new ActivationGate($this->store, $this->issuer->keyring(), $this->hosts([]));

        $change = new ActivationChange(
            $this->exchangeThatMustNotBeCalled(),
            $this->store,
            $gate,
            $this->hosts([]),
            new NullLogger(),
        );

        $this->expectExceptionMessage('no_configured_host');

        $change->activate('CNS-TEST-KEY');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function package(array $overrides = []): SealedPackage
    {
        [$response] = $this->issuer->response($this->issuer->record($this->now, $overrides));

        return SealedPackage::open($response, $this->issuer->keyring(), $this->now);
    }

    private function change(IssuerExchange $exchange): ActivationChange
    {
        return new ActivationChange(
            $exchange,
            $this->store,
            $this->gate,
            $this->hosts(['example.com']),
            new NullLogger(),
        );
    }

    private function exchangeReturning(SealedPackage $package): IssuerExchange
    {
        return new class(new SecurePost(), new IssuerKeyring([]), $package) extends IssuerExchange {
            public function __construct(SecurePost $post, IssuerKeyring $keys, private readonly SealedPackage $package)
            {
                parent::__construct($post, $keys);
            }

            public function activate(string $key, string $host, int $now): SealedPackage
            {
                return $this->package;
            }

            public function refresh(string $key, string $host, int $now, int $currentVersion): SealedPackage
            {
                return $this->package;
            }
        };
    }

    private function exchangeThatMustNotBeCalled(): IssuerExchange
    {
        return new class(new SecurePost(), new IssuerKeyring([])) extends IssuerExchange {
            public function activate(string $key, string $host, int $now): SealedPackage
            {
                throw new \LogicException('The network must not be reached for this case.');
            }

            public function refresh(string $key, string $host, int $now, int $currentVersion): SealedPackage
            {
                throw new \LogicException('The network must not be reached for this case.');
            }
        };
    }

    /**
     * @param list<string> $configured
     */
    private function hosts(array $configured): InstallationHosts
    {
        return new class($configured) extends InstallationHosts {
            /**
             * @param list<string> $configured
             */
            public function __construct(private readonly array $configured)
            {
            }

            public function configured(): array
            {
                return $this->configured;
            }

            public function currentTrusted(): string|null
            {
                return $this->configured[0] ?? null;
            }
        };
    }
}
