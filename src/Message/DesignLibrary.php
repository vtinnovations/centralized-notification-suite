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

namespace VTInnovations\CentralizedNotificationSuite\Message;

use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;

/**
 * The ready-made e-mail designs, and the markup generator behind them.
 *
 * Designs are data, not twenty HTML files: every one of them needs the same table-based,
 * Outlook-safe skeleton, and duplicating that skeleton per design is how one of them ends
 * up subtly broken. Each entry below only declares how it differs -- header treatment, card
 * style, footer treatment, typography -- and the markup is assembled from those parts.
 *
 * All colours derive from the single brand colour in the branding record, so a design is
 * on-brand the moment a logo and colour are set.
 */
class DesignLibrary
{
    /**
     * Header treatments.
     */
    private const H_LOGO_LEFT = 'logo_left';
    private const H_LOGO_CENTER = 'logo_center';
    private const H_BAR = 'bar';
    private const H_DARK = 'dark';
    private const H_WORDMARK = 'wordmark';
    private const H_RULE = 'rule';
    private const H_NONE = 'none';

    /**
     * Card treatments.
     */
    private const C_SHADOW = 'shadow';
    private const C_FLAT = 'flat';
    private const C_BORDER = 'bordered';
    private const C_TINTED = 'tinted';

    /**
     * Footer treatments.
     */
    private const F_CENTER = 'center';
    private const F_LEFT = 'left';
    private const F_DARK = 'dark';
    private const F_MINIMAL = 'minimal';

    /**
     * name => [label, group, header, card, footer, font, radius, page background]
     *
     * @var array<string, array{label: string, group: string, header: string, card: string, footer: string, font: string, radius: int, page: string}>
     */
    private const DESIGNS = [
        'card-centered' => ['label' => 'Card – centred logo', 'group' => 'Card', 'header' => self::H_LOGO_CENTER, 'card' => self::C_SHADOW, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 10, 'page' => 'grey'],
        'card-left' => ['label' => 'Card – left logo', 'group' => 'Card', 'header' => self::H_LOGO_LEFT, 'card' => self::C_SHADOW, 'footer' => self::F_LEFT, 'font' => 'sans', 'radius' => 10, 'page' => 'grey'],
        'card-sharp' => ['label' => 'Card – square corners', 'group' => 'Card', 'header' => self::H_LOGO_LEFT, 'card' => self::C_SHADOW, 'footer' => self::F_LEFT, 'font' => 'sans', 'radius' => 0, 'page' => 'grey'],
        'card-bordered' => ['label' => 'Card – outlined', 'group' => 'Card', 'header' => self::H_LOGO_CENTER, 'card' => self::C_BORDER, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 8, 'page' => 'white'],
        'brandbar-centered' => ['label' => 'Brand bar – centred logo', 'group' => 'Brand bar', 'header' => self::H_BAR, 'card' => self::C_SHADOW, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 10, 'page' => 'grey'],
        'brandbar-left' => ['label' => 'Brand bar – left logo', 'group' => 'Brand bar', 'header' => self::H_BAR, 'card' => self::C_FLAT, 'footer' => self::F_LEFT, 'font' => 'sans', 'radius' => 0, 'page' => 'grey'],
        'brandbar-dark-footer' => ['label' => 'Brand bar – dark footer', 'group' => 'Brand bar', 'header' => self::H_BAR, 'card' => self::C_FLAT, 'footer' => self::F_DARK, 'font' => 'sans', 'radius' => 0, 'page' => 'grey'],
        'dark-header' => ['label' => 'Dark header', 'group' => 'Dark', 'header' => self::H_DARK, 'card' => self::C_FLAT, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 8, 'page' => 'grey'],
        'dark-header-footer' => ['label' => 'Dark header and footer', 'group' => 'Dark', 'header' => self::H_DARK, 'card' => self::C_FLAT, 'footer' => self::F_DARK, 'font' => 'sans', 'radius' => 0, 'page' => 'grey'],
        'minimal-rule' => ['label' => 'Minimal – hairline rule', 'group' => 'Minimal', 'header' => self::H_RULE, 'card' => self::C_FLAT, 'footer' => self::F_MINIMAL, 'font' => 'sans', 'radius' => 0, 'page' => 'white'],
        'minimal-wordmark' => ['label' => 'Minimal – wordmark only', 'group' => 'Minimal', 'header' => self::H_WORDMARK, 'card' => self::C_FLAT, 'footer' => self::F_MINIMAL, 'font' => 'sans', 'radius' => 0, 'page' => 'white'],
        'minimal-plain' => ['label' => 'Minimal – no header', 'group' => 'Minimal', 'header' => self::H_NONE, 'card' => self::C_FLAT, 'footer' => self::F_MINIMAL, 'font' => 'sans', 'radius' => 0, 'page' => 'white'],
        'transactional' => ['label' => 'Transactional – plain and narrow', 'group' => 'Transactional', 'header' => self::H_WORDMARK, 'card' => self::C_FLAT, 'footer' => self::F_LEFT, 'font' => 'system', 'radius' => 0, 'page' => 'white'],
        'transactional-tinted' => ['label' => 'Transactional – tinted panel', 'group' => 'Transactional', 'header' => self::H_LOGO_LEFT, 'card' => self::C_TINTED, 'footer' => self::F_LEFT, 'font' => 'system', 'radius' => 6, 'page' => 'grey'],
        'receipt' => ['label' => 'Receipt – boxed, monospaced figures', 'group' => 'Transactional', 'header' => self::H_LOGO_LEFT, 'card' => self::C_BORDER, 'footer' => self::F_LEFT, 'font' => 'system', 'radius' => 4, 'page' => 'grey'],
        'announcement' => ['label' => 'Announcement – wide, centred', 'group' => 'Announcement', 'header' => self::H_LOGO_CENTER, 'card' => self::C_FLAT, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 0, 'page' => 'tint'],
        'announcement-tinted' => ['label' => 'Announcement – tinted background', 'group' => 'Announcement', 'header' => self::H_BAR, 'card' => self::C_TINTED, 'footer' => self::F_CENTER, 'font' => 'sans', 'radius' => 12, 'page' => 'tint'],
        'newsletter' => ['label' => 'Newsletter – serif headings', 'group' => 'Editorial', 'header' => self::H_RULE, 'card' => self::C_FLAT, 'footer' => self::F_CENTER, 'font' => 'serif', 'radius' => 0, 'page' => 'white'],
        'editorial' => ['label' => 'Editorial – serif, dark footer', 'group' => 'Editorial', 'header' => self::H_LOGO_CENTER, 'card' => self::C_FLAT, 'footer' => self::F_DARK, 'font' => 'serif', 'radius' => 0, 'page' => 'white'],
        'compact' => ['label' => 'Compact – tight spacing', 'group' => 'Minimal', 'header' => self::H_LOGO_LEFT, 'card' => self::C_BORDER, 'footer' => self::F_MINIMAL, 'font' => 'system', 'radius' => 6, 'page' => 'white'],
    ];

    private const FONTS = [
        'sans' => "'Helvetica Neue', Helvetica, Arial, sans-serif",
        'serif' => "Georgia, 'Times New Roman', Times, serif",
        'system' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif",
    ];

    public const DEFAULT_DESIGN = 'card-left';

    /**
     * Design name => label, grouped for the backend select.
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedOptions(): array
    {
        $grouped = [];

        foreach (self::DESIGNS as $name => $design) {
            $grouped[$design['group']][$name] = $design['label'];
        }

        return $grouped;
    }

    public function has(string $name): bool
    {
        return isset(self::DESIGNS[$name]);
    }

    /**
     * Builds the layout for a design. Falls back to the default design rather than throwing:
     * a renamed design must not stop a notification from going out.
     *
     * Wrapper mode rather than header/footer: a design owns the page background and the card
     * the body sits inside, and header/footer mode only concatenates around the body.
     */
    public function build(string $name, Branding $branding): EmailLayout
    {
        $design = self::DESIGNS[$name] ?? self::DESIGNS[self::DEFAULT_DESIGN];

        // A complete document, not a bare table: without a head the output ends up with a
        // <style> block ahead of the doctype (quirks mode) and no charset, viewport or
        // colour-scheme hints -- which is what mobile stacking and dark-mode control need.
        // HtmlRenderer::embedStyleBlock() already prefers </head>, so the media query lands
        // in the right place with no change there.
        $wrapper = \sprintf(
            '<!doctype html><html><head>'
            .'<meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<meta name="color-scheme" content="light dark">'
            .'<meta name="supported-color-schemes" content="light dark">'
            .'</head><body>'
            .'<table role="presentation" class="sn-wrap" width="100%%" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td align="center" class="sn-wrap-cell">'
            .'<table role="presentation" class="sn-card" width="600" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td>%s</td></tr>'
            .'<tr><td class="sn-body">%s</td></tr>'
            .'<tr><td>%s</td></tr>'
            .'</table></td></tr></table>'
            .'</body></html>',
            $this->header($design, $branding),
            TemplateModel::BODY_PLACEHOLDER,
            $this->footer($design, $branding),
        );

        return new EmailLayout(
            mode: EmailLayout::MODE_WRAPPER,
            wrapperHtml: $wrapper,
            css: $this->css($design, $branding),
            inlineCss: true,
        );
    }

    /**
     * @param array<string, mixed> $design
     */
    private function header(array $design, Branding $branding): string
    {
        $logo = $this->logo($branding);
        $name = $this->escape($branding->displayName());

        return match ($design['header']) {
            self::H_LOGO_LEFT => $this->row('sn-head', 'left', $logo ?: '<span class="sn-wordmark">'.$name.'</span>'),
            self::H_LOGO_CENTER => $this->row('sn-head', 'center', $logo ?: '<span class="sn-wordmark">'.$name.'</span>'),
            self::H_BAR => $this->row('sn-head sn-head-bar', 'left', $logo ?: '<span class="sn-wordmark sn-on-brand">'.$name.'</span>'),
            self::H_DARK => $this->row('sn-head sn-head-dark', 'left', $logo ?: '<span class="sn-wordmark sn-on-dark">'.$name.'</span>'),
            self::H_WORDMARK => $this->row('sn-head', 'left', '<span class="sn-wordmark">'.$name.'</span>'),
            self::H_RULE => $this->row('sn-head sn-head-rule', 'left', $logo ?: '<span class="sn-wordmark">'.$name.'</span>'),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $design
     */
    private function footer(array $design, Branding $branding): string
    {
        if (self::F_MINIMAL === $design['footer']) {
            return $this->row('sn-foot sn-foot-minimal', 'left', $this->footerLines($branding, true));
        }

        $class = 'sn-foot'.(self::F_DARK === $design['footer'] ? ' sn-foot-dark' : '');
        $align = self::F_CENTER === $design['footer'] ? 'center' : 'left';

        return $this->row($class, $align, $this->footerLines($branding, false));
    }

    /**
     * The footer body. Every part is optional, so an empty branding record produces an empty
     * footer rather than stray separators.
     */
    private function footerLines(Branding $branding, bool $minimal): string
    {
        $lines = [];

        if ('' !== ($name = $branding->displayName()) && !$minimal) {
            $lines[] = '<strong>'.$this->escape($name).'</strong>';
        }

        if ('' !== $branding->address) {
            $lines[] = nl2br($this->escape($branding->address), false);
        }

        $links = [];

        if ('' !== $branding->website) {
            $links[] = '<a class="sn-foot-link" href="'.$this->escape($branding->website).'">'
                .$this->escape((string) (parse_url($branding->website, PHP_URL_HOST) ?: $branding->website)).'</a>';
        }

        if ('' !== $branding->supportEmail) {
            $links[] = '<a class="sn-foot-link" href="mailto:'.$this->escape($branding->supportEmail).'">'
                .$this->escape($branding->supportEmail).'</a>';
        }

        if ($links) {
            $lines[] = implode(' &middot; ', $links);
        }

        if ('' !== $branding->footerNote) {
            $lines[] = '<span class="sn-foot-note">'.nl2br($this->escape($branding->footerNote), false).'</span>';
        }

        return implode('<br>', $lines);
    }

    private function logo(Branding $branding): string
    {
        if ('' === $branding->logoUrl) {
            return '';
        }

        // width as an attribute as well as CSS: Outlook ignores the style but honours the attribute
        return \sprintf(
            '<img class="sn-logo" src="%s" width="%d" alt="%s">',
            $this->escape($branding->logoUrl),
            $branding->logoWidth,
            $this->escape($branding->displayName()),
        );
    }

    private function row(string $class, string $align, string $content): string
    {
        if ('' === $content) {
            return '';
        }

        return \sprintf(
            '<table role="presentation" class="sn-row" width="100%%" cellpadding="0" cellspacing="0" border="0"><tr><td class="%s" align="%s">%s</td></tr></table>',
            $class,
            $align,
            $content,
        );
    }

    /**
     * @param array<string, mixed> $design
     */
    private function css(array $design, Branding $branding): string
    {
        $font = self::FONTS[$design['font']] ?? self::FONTS['sans'];
        $brand = $branding->brandColor;
        $onBrand = $branding->onBrandColor();
        $tint = $branding->tint();
        $dark = $branding->shade();
        $radius = (int) $design['radius'];

        $page = match ($design['page']) {
            'grey' => '#eef1f4',
            'tint' => $branding->tint(0.86),
            default => '#ffffff',
        };

        $card = match ($design['card']) {
            self::C_SHADOW => "background:#ffffff;border-radius:{$radius}px;box-shadow:0 1px 4px rgba(16,24,40,.08)",
            self::C_BORDER => "background:#ffffff;border:1px solid #dfe3e8;border-radius:{$radius}px",
            self::C_TINTED => "background:{$tint};border-radius:{$radius}px",
            default => 'background:#ffffff',
        };

        // width:100% with a max-width, not the bare width="600" attribute: the attribute alone
        // makes the card overflow a phone and cuts the footer off. Outlook ignores max-width
        // and keeps honouring the attribute, which is why both are present.
        $card .= ';width:100%;max-width:600px';

        return <<<CSS
            body { margin:0; padding:0; background:{$page}; }
            .sn-wrap { background:{$page}; padding:24px 12px; font-family:{$font}; }
            .sn-card { {$card}; overflow:hidden; }
            .sn-row { width:100%; }
            .sn-body { padding:28px 32px; font-family:{$font}; font-size:15px; line-height:1.6; color:#1f2933; }
            .sn-body h1, .sn-body h2, .sn-body h3 { font-family:{$font}; color:#11181c; margin:0 0 12px; line-height:1.25; }
            .sn-body a { color:{$brand}; }
            .sn-body table { border-collapse:collapse; }
            .sn-head { padding:24px 32px 8px; }
            .sn-head-bar { background:{$brand}; padding:20px 32px; }
            .sn-head-dark { background:{$dark}; padding:20px 32px; }
            .sn-head-rule { border-bottom:1px solid #e3e7eb; padding-bottom:16px; }
            .sn-logo { display:block; border:0; outline:none; text-decoration:none; height:auto; max-width:100%; }
            .sn-wordmark { font-family:{$font}; font-size:18px; font-weight:700; color:{$brand}; letter-spacing:.2px; }
            .sn-on-brand { color:{$onBrand}; }
            .sn-on-dark { color:#ffffff; }
            .sn-foot { padding:20px 32px 26px; font-family:{$font}; font-size:12px; line-height:1.6; color:#69737d; border-top:1px solid #e9edf0; }
            .sn-foot-minimal { padding:16px 32px 22px; font-size:11px; border-top:1px solid #eef1f4; }
            .sn-foot-dark { background:{$dark}; color:rgba(255,255,255,.78); border-top:0; }
            .sn-foot-link { color:{$brand}; text-decoration:underline; }
            .sn-foot-dark .sn-foot-link { color:#ffffff; }
            .sn-foot-note { color:#8a949d; }
            .sn-foot-dark .sn-foot-note { color:rgba(255,255,255,.6); }
            img { max-width:100%; }
            /* Blocks style themselves inline, which is what keeps the design's own selectors
               from overriding them. These two classes exist purely so the columns of an
               image-with-text block can be reclaimed on a phone -- the only thing an inline
               style cannot express. */
            .sn-blk-col { vertical-align:top; }
            @media only screen and (max-width:600px) {
              /* !important because the declarations these override are inlined onto the
                 elements, and an inline style otherwise wins over a media query */
              .sn-blk-cols { font-size:0 !important; }
              .sn-blk-col { display:block !important; width:100% !important; max-width:100% !important; padding:0 0 14px !important; }
              /* !important because the declarations above are inlined onto the elements,
                 and an inline style otherwise wins over a media query */
              .sn-wrap { padding:12px 0 !important; }
              .sn-card { max-width:100% !important; border-radius:0 !important; }
              .sn-body { padding:20px 18px !important; }
              .sn-head, .sn-head-bar, .sn-head-dark { padding:16px 18px !important; }
              .sn-foot, .sn-foot-minimal { padding:16px 18px 20px !important; }
            }
            CSS;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
