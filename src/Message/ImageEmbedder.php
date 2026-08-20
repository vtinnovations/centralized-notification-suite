<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Rewrites <img src> references to local files into cid: attachments.
 *
 * Most mail clients block remote images until the reader clicks "show images", so a logo
 * loaded over HTTP is invisible on first read -- and on an intranet or staging host the URL
 * may not resolve for the recipient at all. Embedding the bytes makes the image part of the
 * message, at the cost of a larger mail.
 */
class ImageEmbedder
{
    private const EMBEDDABLE = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

    /**
     * Content IDs are addr-specs: Symfony's DataPart::setContentId() rejects anything
     * without an "@", and Email::prepareParts() pairs a "cid:" reference with its part by
     * comparing the reference against the content ID verbatim. Both the ID and the src
     * therefore have to carry this suffix, or the image is demoted to a plain attachment
     * and the <img> renders broken.
     */
    private const CID_DOMAIN = '@simple-notify';

    public function __construct(
        private readonly string $projectDir,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{0: string, 1: list<Attachment>} Rewritten markup and the inline attachments it now references
     */
    public function embed(string $html): array
    {
        if ('' === trim($html)) {
            return [$html, []];
        }

        /** @var array<string, Attachment> $attachments Keyed by path, so one file used twice is attached once */
        $attachments = [];

        $rewritten = preg_replace_callback(
            '#(<img\b[^>]*?\bsrc=)(["\'])(.*?)\2#is',
            function (array $matches) use (&$attachments): string {
                $path = $this->resolve(html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if (null === $path) {
                    return $matches[0];
                }

                if (!isset($attachments[$path])) {
                    $attachments[$path] = new Attachment(
                        $path,
                        basename($path),
                        mime_content_type($path) ?: null,
                        // Unique within the message and stable per file
                        'sn'.substr(hash('xxh128', $path), 0, 16).self::CID_DOMAIN,
                    );
                }

                return $matches[1].$matches[2].'cid:'.$attachments[$path]->cid.$matches[2];
            },
            $html,
        );

        return [$rewritten ?? $html, array_values($attachments)];
    }

    /**
     * Maps an img src onto a readable file inside the project, or null when it points
     * somewhere we should not or cannot embed from.
     */
    private function resolve(string $src): string|null
    {
        if ('' === $src || str_starts_with($src, 'cid:') || str_starts_with($src, 'data:')) {
            return null;
        }

        $src = $this->stripHost($src);

        if (null === $src) {
            return null;
        }

        // Query strings and fragments are not part of the filename
        $src = (string) preg_replace('/[?#].*$/', '', $src);
        $src = rawurldecode(ltrim($src, '/'));

        if ('' === $src || !\in_array(strtolower(pathinfo($src, PATHINFO_EXTENSION)), self::EMBEDDABLE, true)) {
            return null;
        }

        // Resolve against both layouts Contao supports for the web root
        foreach (['', 'public/', 'web/'] as $prefix) {
            $candidate = $this->projectDir.'/'.$prefix.$src;
            $real = realpath($candidate);

            // Containment check: a "../" in the markup must not reach outside the project
            if (false !== $real && is_file($real) && is_readable($real) && str_starts_with($real, $this->projectDir.\DIRECTORY_SEPARATOR)) {
                return $real;
            }
        }

        return null;
    }

    /**
     * Absolute URLs are only embedded when they point at this site; anything else is left
     * as a remote reference.
     */
    private function stripHost(string $src): string|null
    {
        if (!preg_match('#^(?:https?:)?//#i', $src)) {
            return $src;
        }

        $parts = parse_url($src);

        if (false === $parts || !isset($parts['host'])) {
            return null;
        }

        $host = $this->requestStack->getCurrentRequest()?->getHost();

        if (null === $host || 0 !== strcasecmp($parts['host'], $host)) {
            return null;
        }

        return $parts['path'] ?? null;
    }
}
