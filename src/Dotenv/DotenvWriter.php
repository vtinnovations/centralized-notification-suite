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

namespace VTInnovations\CentralizedNotificationSuite\Dotenv;

use VTInnovations\CentralizedNotificationSuite\Exception\DotenvWriteException;

/**
 * Reads and writes single keys in .env.local.
 *
 * .env.local rather than a database row: MAILER_DSN has to be readable before the container
 * is built, and it is the file Contao and Symfony already agree on for environment overrides.
 *
 * Ported from vtinnovations/smtp-bundle (LGPL-3.0-or-later, VT Innovations Team).
 */
class DotenvWriter
{
    private readonly string $path;

    public function __construct(string $projectDir)
    {
        $this->path = rtrim($projectDir, '/\\').'/.env.local';
    }

    /**
     * Whether the file can actually be written, so a caller can fail fast instead of doing
     * expensive work (sending a test mail) it will not be able to persist.
     */
    public function isWritable(): bool
    {
        return file_exists($this->path) ? is_writable($this->path) : is_writable(\dirname($this->path));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function write(string $key, string $value): void
    {
        $this->assertValidKey($key);
        $this->assertWritable();

        $content = file_exists($this->path) ? (string) file_get_contents($this->path) : '';
        $line = $key.'='.$this->quoteValue($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        // Replace in place when present, so unrelated keys and their order survive
        if (preg_match($pattern, $content)) {
            $content = (string) preg_replace($pattern, $line, $content);
        } else {
            $content = rtrim($content)."\n".$line."\n";
        }

        $this->put($content);
    }

    public function remove(string $key): void
    {
        $this->assertValidKey($key);
        $this->assertWritable();

        if (!file_exists($this->path)) {
            return;
        }

        $content = (string) preg_replace(
            '/^'.preg_quote($key, '/').'=.*$\n?/m',
            '',
            (string) file_get_contents($this->path),
        );

        $this->put($content);
    }

    public function read(string $key): string|null
    {
        if (!file_exists($this->path)) {
            return null;
        }

        $content = (string) file_get_contents($this->path);

        if (!preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $content, $matches)) {
            return null;
        }

        return trim($matches[1], '"\'');
    }

    /**
     * The @ is deliberate: an unwritable .env.local makes file_put_contents raise a warning,
     * which Symfony's error handler turns into an ErrorException and a 500 before the return
     * value can be checked. Suppressing it keeps the typed exception -- and the backend
     * message built from it -- as the thing the operator actually sees.
     */
    private function put(string $content): void
    {
        if (false === @file_put_contents($this->path, $content)) {
            throw new DotenvWriteException($this->notWritableMessage());
        }
    }

    private function assertWritable(): void
    {
        if (!$this->isWritable()) {
            throw new DotenvWriteException($this->notWritableMessage());
        }
    }

    private function notWritableMessage(): string
    {
        return \sprintf(
            'Cannot write to "%s". Give the web server user write access to this file, e.g. '
            .'chown <you>:www-data %s && chmod 664 %s',
            $this->path,
            $this->path,
            $this->path,
        );
    }

    private function quoteValue(string $value): string
    {
        // A DSN's : @ ? are safe unquoted; whitespace, quotes, # and backslashes are not
        if ('' === $value || preg_match('/[\s"\'#\\\\]/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    private function assertValidKey(string $key): void
    {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid .env key "%s". Keys must be uppercase letters, digits and underscores.',
                $key,
            ));
        }
    }
}
