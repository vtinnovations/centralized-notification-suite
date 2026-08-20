<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Token;

/**
 * Rewrites Notification Center token names to this bundle's names.
 *
 * Without this, an imported message would still *look* right in the backend but render with
 * every token blank -- the worst possible migration outcome, because nothing errors and the
 * damage only shows up in mail that has already gone out.
 *
 * Applied to imported subjects and bodies. Unknown tokens are left alone: a project's own
 * token from a custom NC extension is better preserved verbatim (and reported) than guessed
 * at or silently dropped.
 */
class NcTokenAliasResolver
{
    /**
     * Exact renames.
     */
    private const EXACT = [
        // NC exposes the whole submission under these names
        'raw_data' => 'all_fields',
        'raw_data_filled' => 'all_fields_filled',
        'form_raw_data' => 'all_fields',
        'formconfig_id' => 'form_id',
        'formconfig_title' => 'form_title',
        'form' => 'form_title',
        // NC's e-mail/date helpers
        'admin_email' => 'admin_email',
        'date' => 'date',
        'last_update' => 'datim',
        'env_host' => 'host',
        'env_url' => 'url',
        'env_request' => 'page_url',
        'page_id' => 'page_id',
        'page_title' => 'page_title',
        'page_url' => 'page_url',
    ];

    /**
     * Prefix renames, longest first so "formconfig_" is never treated as "form_".
     */
    private const PREFIXES = [
        'formconfig_' => 'form_',
        // NC prefixes every submitted field; ours are the bare field names
        'form_' => '',
        'recipient_' => 'recipient_',
        'member_' => 'member_',
        'comment_' => 'comment_',
        'newsletter_' => 'newsletter_',
    ];

    /** @var array<string, int> Tokens seen that could not be mapped, name => count */
    private array $unmapped = [];

    /**
     * Rewrites every ##token## in the given text.
     */
    public function rewrite(string $text): string
    {
        if ('' === $text) {
            return $text;
        }

        return (string) preg_replace_callback(
            '/##([^#=!<>\s][^=!<>\s]*?)##/',
            function (array $matches): string {
                $mapped = $this->map($matches[1]);

                if (null === $mapped) {
                    $this->unmapped[$matches[1]] = ($this->unmapped[$matches[1]] ?? 0) + 1;

                    return $matches[0];
                }

                return '##'.$mapped.'##';
            },
            $text,
        );
    }

    /**
     * The new name for an NC token, or null when there is no known mapping.
     */
    public function map(string $token): string|null
    {
        if (isset(self::EXACT[$token])) {
            return self::EXACT[$token];
        }

        foreach (self::PREFIXES as $from => $to) {
            if (!str_starts_with($token, $from)) {
                continue;
            }

            $rest = substr($token, \strlen($from));

            if ('' === $rest) {
                continue;
            }

            // "form_*" maps to the bare field name, but NC also uses that namespace for
            // form metadata; those are handled by EXACT above and never reach here.
            return $to.$rest;
        }

        return null;
    }

    /**
     * Tokens the import could not translate, so the operator can check them by hand.
     *
     * @return array<string, int> Token name => how often it appeared
     */
    public function getUnmapped(): array
    {
        ksort($this->unmapped);

        return $this->unmapped;
    }

    public function reset(): void
    {
        $this->unmapped = [];
    }
}
