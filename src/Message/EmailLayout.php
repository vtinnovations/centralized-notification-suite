<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

/**
 * The layout parts HtmlRenderer needs, as plain values.
 *
 * HtmlRenderer deliberately does not take a TemplateModel: assembling and inlining markup
 * has nothing to do with the database, and depending on a Contao model would mean the whole
 * pipeline could only be exercised with a booted framework. A layout can also be built by
 * hand -- which is what the preview of an unsaved template needs.
 */
class EmailLayout
{
    public const MODE_HEADER_FOOTER = 'header_footer';

    public const MODE_WRAPPER = 'wrapper';

    public function __construct(
        public readonly string $mode = self::MODE_HEADER_FOOTER,
        public readonly string $wrapperHtml = '',
        public readonly string $headerHtml = '',
        public readonly string $footerHtml = '',
        public readonly string $css = '',
        public readonly string $preheader = '',
        public readonly bool $inlineCss = true,
    ) {
    }

    /**
     * The rendered parts are passed in separately because MessageRenderer resolves their
     * tokens first, and must not write recipient-specific content back onto a model that
     * Contao shares through its registry.
     *
     * @param array<string, string> $rendered Overrides keyed by property name
     */
    public static function fromModel(TemplateModel $template, array $rendered = []): self
    {
        return new self(
            mode: self::MODE_WRAPPER === $template->layout_mode ? self::MODE_WRAPPER : self::MODE_HEADER_FOOTER,
            wrapperHtml: $rendered['wrapper_html'] ?? (string) $template->wrapper_html,
            headerHtml: $rendered['header_html'] ?? (string) $template->header_html,
            footerHtml: $rendered['footer_html'] ?? (string) $template->footer_html,
            css: (string) $template->css,
            preheader: $rendered['preheader'] ?? (string) $template->preheader,
            inlineCss: (bool) $template->inline_css,
        );
    }
}
