<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

// TemplateModel is referenced only for its BODY_PLACEHOLDER constant; the layout itself
// arrives as an EmailLayout value object so this class needs no database.

/**
 * Assembles the HTML part of a message: body into layout, then CSS inlined.
 *
 * The inlining step is the reason this exists. Gmail's web client strips <style> blocks and
 * Outlook's Word-based renderer ignores most of what survives, so a pasted design that
 * looks right in a browser routinely arrives unstyled. Every element therefore gets its
 * declarations copied onto a style attribute before the mail goes out.
 *
 * @see MessageRenderer for token and insert tag handling, which happens before this
 */
class HtmlRenderer
{
    /**
     * Media queries cannot be expressed as inline styles, so the inliner drops them. They
     * are extracted first and re-inserted as a <style> block: clients that honour them get
     * the responsive behaviour, and the ones that do not still have the inline styles.
     */
    private const AT_RULE_PATTERN = '/@(?:media|supports|font-face|keyframes|-webkit-keyframes)[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/is';

    public function __construct(private readonly CssToInlineStyles $inliner)
    {
    }

    /**
     * @param string           $body      The already token-resolved HTML body of the message
     * @param EmailLayout|null $layout    Null sends the body exactly as entered
     * @param bool             $inlineCss Overridden to false when the layout disables it
     */
    public function render(string $body, EmailLayout|null $layout, bool $inlineCss = true): string
    {
        if (null === $layout) {
            return $inlineCss ? $this->inline($body, '') : $body;
        }

        $html = $this->applyLayout($body, $layout);
        $inlineCss = $inlineCss && $layout->inlineCss;

        if (!$inlineCss) {
            return '' !== trim($layout->css) ? $this->embedStyleBlock($html, $layout->css) : $html;
        }

        return $this->inline($html, $layout->css);
    }

    /**
     * Wraps the body in its layout.
     */
    private function applyLayout(string $body, EmailLayout $layout): string
    {
        $preheader = trim($layout->preheader);

        if ('' !== $preheader) {
            $body = $this->preheaderMarkup($preheader).$body;
        }

        if (EmailLayout::MODE_WRAPPER === $layout->mode) {
            $wrapper = $layout->wrapperHtml;

            if ('' === trim($wrapper)) {
                return $body;
            }

            // No placeholder means the editor forgot it -- appending the body is better
            // than sending a layout with the message silently missing from it.
            if (!str_contains($wrapper, TemplateModel::BODY_PLACEHOLDER)) {
                return $wrapper.$body;
            }

            return str_replace(TemplateModel::BODY_PLACEHOLDER, $body, $wrapper);
        }

        return $layout->headerHtml.$body.$layout->footerHtml;
    }

    /**
     * The hidden line most clients show next to the subject in the inbox list. Without one
     * they show the first words of the markup instead, which is usually "View in browser".
     */
    private function preheaderMarkup(string $preheader): string
    {
        return \sprintf(
            '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all">%s</div>',
            htmlspecialchars($preheader, ENT_QUOTES, 'UTF-8'),
        );
    }

    private function inline(string $html, string $css): string
    {
        // Collect CSS from the template field and from any <style> blocks in the markup,
        // so a pasted full document gets inlined just like a configured stylesheet.
        [$html, $embedded] = $this->extractStyleBlocks($html);
        $allCss = trim($css."\n".$embedded);

        if ('' === $allCss) {
            return $html;
        }

        $atRules = $this->extractAtRules($allCss);
        $inlinable = preg_replace(self::AT_RULE_PATTERN, '', $allCss) ?? $allCss;

        $result = $this->inliner->convert($html, $inlinable);

        return '' !== $atRules ? $this->embedStyleBlock($result, $atRules) : $result;
    }

    /**
     * @return array{0: string, 1: string} Markup without <style> blocks, and their contents
     */
    private function extractStyleBlocks(string $html): array
    {
        $collected = '';

        $stripped = preg_replace_callback(
            '#<style\b[^>]*>(.*?)</style>#is',
            static function (array $matches) use (&$collected): string {
                $collected .= $matches[1]."\n";

                return '';
            },
            $html,
        );

        return [$stripped ?? $html, $collected];
    }

    private function extractAtRules(string $css): string
    {
        preg_match_all(self::AT_RULE_PATTERN, $css, $matches);

        return implode("\n", $matches[0] ?? []);
    }

    /**
     * Puts a <style> block into <head> when the markup is a full document, otherwise in
     * front of the content.
     */
    private function embedStyleBlock(string $html, string $css): string
    {
        $block = \sprintf('<style type="text/css">%s</style>', $css);

        if (preg_match('#</head>#i', $html)) {
            return (string) preg_replace('#</head>#i', $block.'</head>', $html, 1);
        }

        return $block.$html;
    }
}
