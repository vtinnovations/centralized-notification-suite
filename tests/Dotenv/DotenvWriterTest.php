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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Dotenv;

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Dotenv\DotenvWriter;
use VTInnovations\CentralizedNotificationSuite\Exception\DotenvWriteException;

class DotenvWriterTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/nb-dotenv-'.bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $file = $this->dir.'/.env.local';

        if (file_exists($file)) {
            chmod($file, 0644);
            unlink($file);
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    private function writer(): DotenvWriter
    {
        return new DotenvWriter($this->dir);
    }

    private function seed(string $contents): void
    {
        file_put_contents($this->dir.'/.env.local', $contents);
    }

    public function testCreatesTheFileWhenItDoesNotExist(): void
    {
        $writer = $this->writer();
        $writer->write('MAILER_DSN', 'smtp://localhost:25');

        $this->assertSame('smtp://localhost:25', $writer->read('MAILER_DSN'));
    }

    /**
     * The file also holds APP_SECRET and DATABASE_URL; losing those would take the site down.
     */
    public function testUpdatingAKeyLeavesEveryOtherLineUntouched(): void
    {
        $this->seed("APP_SECRET=abc\nMAILER_DSN=smtp://old:25\nAPP_ENV=dev\n");

        $writer = $this->writer();
        $writer->write('MAILER_DSN', 'smtp://new:587');

        $this->assertSame('smtp://new:587', $writer->read('MAILER_DSN'));
        $this->assertSame('abc', $writer->read('APP_SECRET'));
        $this->assertSame('dev', $writer->read('APP_ENV'));
        $this->assertSame(3, substr_count((string) file_get_contents($this->dir.'/.env.local'), '='), 'no key duplicated');
    }

    public function testRemoveDropsOnlyTheRequestedKey(): void
    {
        $this->seed("APP_SECRET=abc\nMAILER_DSN=smtp://x:25\n");

        $writer = $this->writer();
        $writer->remove('MAILER_DSN');

        $this->assertNull($writer->read('MAILER_DSN'));
        $this->assertSame('abc', $writer->read('APP_SECRET'));
    }

    public function testRemoveIsANoopWhenTheFileIsAbsent(): void
    {
        $this->writer()->remove('MAILER_DSN');

        $this->assertFileDoesNotExist($this->dir.'/.env.local');
    }

    public function testReadReturnsNullForAnUnknownKey(): void
    {
        $this->seed("APP_SECRET=abc\n");

        $this->assertNull($this->writer()->read('NOPE'));
    }

    public function testValuesNeedingQuotesAreQuotedAndReadBackVerbatim(): void
    {
        $writer = $this->writer();
        $writer->write('MAILER_DSN', 'smtp://u:p a#s@h:25');

        $this->assertStringContainsString('"', (string) file_get_contents($this->dir.'/.env.local'));
        $this->assertSame('smtp://u:p a#s@h:25', $writer->read('MAILER_DSN'));
    }

    public function testRejectsKeysThatAreNotValidEnvironmentNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->writer()->write('bad-key', 'x');
    }

    /**
     * An unwritable file must surface as the typed exception. Letting file_put_contents raise
     * its warning instead turns the backend save into a 500 before the check is ever reached.
     */
    public function testAnUnwritableFileThrowsTheTypedExceptionAndSaysHowToFixIt(): void
    {
        $this->seed("APP_SECRET=abc\n");
        chmod($this->dir.'/.env.local', 0444);

        if (is_writable($this->dir.'/.env.local')) {
            $this->markTestSkipped('running as a user that ignores file permissions (root)');
        }

        $writer = $this->writer();
        $this->assertFalse($writer->isWritable());

        $this->expectException(DotenvWriteException::class);
        $this->expectExceptionMessageMatches('/chmod 664/');

        $writer->write('MAILER_DSN', 'smtp://x:25');
    }
}
