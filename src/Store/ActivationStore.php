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

namespace VTInnovations\CentralizedNotificationSuite\Store;

/**
 * Keeps the authenticated record and its seal on disk, as one unit.
 *
 * The record is stored as the exact bytes that were received. That is not fussiness: the
 * digest and the signature were computed over those bytes, so re-encoding a parsed copy --
 * even to identical-looking JSON -- would break both on the next read and lock the site out
 * of a licence it legitimately holds.
 *
 * The two files must never disagree. A reader that saw a new record beside an old seal, or
 * the reverse, would reject a valid installation. So writers take an exclusive lock, stage
 * both files, and swap them while holding it; readers take a shared lock and therefore never
 * observe the intermediate state. If the second swap fails, the first is rolled back.
 *
 * The directory lives under var/ and is never web-served, and no path here is ever taken from
 * a request.
 */
class ActivationStore
{
    private const RECORD = 'record.json';

    private const SEAL = 'record.seal.json';

    private const LOCK = '.lock';

    private readonly string $dir;

    public function __construct(string $projectDir)
    {
        $this->dir = $projectDir.'/var/notification-state';
    }

    /**
     * The stored pair, or null when nothing is stored.
     *
     * Returns raw material only. Nothing here decides whether the record is trustworthy --
     * that is settled by verifying the seal, every time, on the way out.
     *
     * @return array{bytes: string, seal: \stdClass}|null
     */
    public function read(): array|null
    {
        $handle = $this->lock(LOCK_SH);

        try {
            $bytes = @file_get_contents($this->path(self::RECORD));
            $seal = @file_get_contents($this->path(self::SEAL));

            if (!\is_string($bytes) || !\is_string($seal) || '' === $bytes || '' === $seal) {
                return null;
            }

            $decoded = json_decode($seal, false);

            return $decoded instanceof \stdClass ? ['bytes' => $bytes, 'seal' => $decoded] : null;
        } finally {
            $this->release($handle);
        }
    }

    /**
     * Replaces the stored pair.
     *
     * Every step before the swap is reversible, and the swap itself is two renames on the
     * same filesystem under an exclusive lock. A failure part-way restores what was there
     * before, because losing a working licence to a half-finished write would be worse than
     * refusing the update.
     *
     * @throws StateNotWritable
     */
    public function write(string $bytes, \stdClass $seal): void
    {
        $encoded = json_encode($seal, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $encoded) {
            throw new StateNotWritable('The seal cannot be serialised.');
        }

        $this->prepareDirectory();
        $handle = $this->lock(LOCK_EX);

        try {
            $recordTmp = $this->stage(self::RECORD, $bytes);
            $sealTmp = $this->stage(self::SEAL, $encoded);

            // Re-read the staged copies before they become authoritative: a short write or a
            // full disk must be caught here, not on the next request.
            if (@file_get_contents($recordTmp) !== $bytes || @file_get_contents($sealTmp) !== $encoded) {
                @unlink($recordTmp);
                @unlink($sealTmp);

                throw new StateNotWritable('The staged state did not read back correctly.');
            }

            $backup = $this->backup();

            if (!@rename($recordTmp, $this->path(self::RECORD))) {
                @unlink($recordTmp);
                @unlink($sealTmp);

                throw new StateNotWritable('The record could not be activated.');
            }

            if (!@rename($sealTmp, $this->path(self::SEAL))) {
                // The pair would now be mismatched, which is the one state that must never
                // be visible, so the first rename is undone.
                @unlink($sealTmp);
                $this->restore($backup);

                throw new StateNotWritable('The seal could not be activated.');
            }

            if (@file_get_contents($this->path(self::RECORD)) !== $bytes) {
                $this->restore($backup);

                throw new StateNotWritable('The activated state did not verify.');
            }

            $this->discard($backup);
        } finally {
            $this->release($handle);
        }
    }

    /**
     * Removes the stored pair, returning the product to its unlicensed behaviour at once.
     *
     * Both files go, under the same exclusive lock. Leaving either behind would let a later
     * read reconstruct a partial state.
     */
    public function clear(): void
    {
        if (!is_dir($this->dir)) {
            return;
        }

        $handle = $this->lock(LOCK_EX);

        try {
            @unlink($this->path(self::RECORD));
            @unlink($this->path(self::SEAL));
        } finally {
            $this->release($handle);
        }
    }

    public function directory(): string
    {
        return $this->dir;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function backup(): array
    {
        $record = @file_get_contents($this->path(self::RECORD));
        $seal = @file_get_contents($this->path(self::SEAL));

        return [\is_string($record) ? $record : null, \is_string($seal) ? $seal : null];
    }

    /**
     * @param array{0: string|null, 1: string|null} $backup
     */
    private function restore(array $backup): void
    {
        [$record, $seal] = $backup;

        if (null === $record || null === $seal) {
            @unlink($this->path(self::RECORD));
            @unlink($this->path(self::SEAL));

            return;
        }

        @file_put_contents($this->path(self::RECORD), $record, LOCK_EX);
        @file_put_contents($this->path(self::SEAL), $seal, LOCK_EX);
    }

    /**
     * @param array{0: string|null, 1: string|null} $backup
     */
    private function discard(array $backup): void
    {
        unset($backup);
    }

    /**
     * @throws StateNotWritable
     */
    private function stage(string $name, string $contents): string
    {
        // Same directory, so the later rename stays within one filesystem and is atomic
        $tmp = $this->dir.'/.'.$name.'.'.bin2hex(random_bytes(6));

        $handle = @fopen($tmp, 'wb');

        if (false === $handle) {
            throw new StateNotWritable(\sprintf('The state directory "%s" is not writable.', $this->dir));
        }

        try {
            if (false === @fwrite($handle, $contents)) {
                throw new StateNotWritable('The state could not be written.');
            }

            @fflush($handle);
            // Durability matters more than speed here: this is written once per activation
            @fsync($handle);
        } finally {
            @fclose($handle);
        }

        @chmod($tmp, 0o600);

        return $tmp;
    }

    /**
     * @throws StateNotWritable
     */
    private function prepareDirectory(): void
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0o700, true) && !is_dir($this->dir)) {
            throw new StateNotWritable(\sprintf('The state directory "%s" could not be created.', $this->dir));
        }

        if (!is_writable($this->dir)) {
            throw new StateNotWritable(\sprintf('The state directory "%s" is not writable.', $this->dir));
        }
    }

    /**
     * @return resource|null
     */
    private function lock(int $mode)
    {
        if (!is_dir($this->dir)) {
            return null;
        }

        $handle = @fopen($this->dir.'/'.self::LOCK, 'c');

        if (false === $handle) {
            return null;
        }

        // Blocking on purpose: two administrators saving at once should serialise rather
        // than one of them silently losing.
        @flock($handle, $mode);

        return $handle;
    }

    /**
     * @param resource|null $handle
     */
    private function release($handle): void
    {
        if (null !== $handle) {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    private function path(string $name): string
    {
        return $this->dir.'/'.$name;
    }
}
