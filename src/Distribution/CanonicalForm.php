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

namespace VTInnovations\CentralizedNotificationSuite\Distribution;

/**
 * Deterministic byte representation of a received document, used as the input to detached
 * signature checks.
 *
 * Implements the `vt-one/canonical-json-v1` rules. Both sides must produce identical bytes
 * from the same document, so nothing here may depend on PHP's map ordering or on the default
 * escaping of json_encode():
 *
 *   1. the top-level `signature` member is removed;
 *   2. object members are sorted ascending by byte value, recursively;
 *   3. list order is preserved exactly;
 *   4. UTF-8 without pretty printing, escaped slashes or escaped Unicode;
 *   5. scalar types are preserved (false is not "false", null is not 0).
 *
 * Decoding to objects rather than associative arrays is deliberate and load-bearing: an empty
 * JSON object and an empty JSON array both decode to [] in associative mode, so re-encoding
 * would silently turn {} into [] and produce bytes the issuer never signed.
 */
final class CanonicalForm
{
    /**
     * Encoding flags that make the output byte-comparable with the issuer.
     *
     * JSON_PRESERVE_ZERO_FRACTION keeps a float that happens to be integral from collapsing
     * to an int and changing the signed bytes.
     */
    private const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * Parses raw bytes into the value graph used for both validation and canonicalisation.
     *
     * @throws MalformedDocument
     */
    public static function decode(string $raw): \stdClass
    {
        try {
            $decoded = json_decode($raw, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MalformedDocument('The document is not valid JSON.', 0, $e);
        }

        if (!$decoded instanceof \stdClass) {
            throw new MalformedDocument('The document is not a JSON object.');
        }

        return $decoded;
    }

    /**
     * The exact bytes a detached signature is verified against.
     *
     * @throws MalformedDocument
     */
    public static function bytes(\stdClass $document): string
    {
        $copy = self::sort(self::withoutSignature($document));

        $encoded = json_encode($copy, self::FLAGS);

        if (false === $encoded) {
            throw new MalformedDocument('The document cannot be re-encoded deterministically.');
        }

        return $encoded;
    }

    /**
     * The signature covers every member except itself, so it is removed before sorting rather
     * than skipped during it -- a nested member also named "signature" must survive.
     */
    private static function withoutSignature(\stdClass $document): \stdClass
    {
        $copy = clone $document;
        unset($copy->signature);

        return $copy;
    }

    /**
     * ksort() with SORT_STRING sorts by byte value, which is what the issuer does. The default
     * comparison would order "10" before "9" and produce different bytes.
     */
    private static function sort(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $members = get_object_vars($value);
            ksort($members, SORT_STRING);

            $sorted = new \stdClass();

            foreach ($members as $name => $member) {
                $sorted->$name = self::sort($member);
            }

            return $sorted;
        }

        if (\is_array($value)) {
            // Lists keep their order; only their elements are normalised
            return array_map(static fn (mixed $item): mixed => self::sort($item), $value);
        }

        return $value;
    }
}
