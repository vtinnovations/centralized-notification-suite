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

namespace VTInnovations\CentralizedNotificationSuite\Token;

/**
 * Builds the "everything that was submitted" tokens.
 *
 * This is the single most repeated piece of work in notification setups: someone lists
 * every form field by hand in the message body, then has to remember to edit the message
 * each time the form changes -- and quietly loses submissions from any field they forgot.
 * These tokens are generated from the actual submission, so they cannot fall out of date.
 */
class FormSummaryBuilder
{
    /**
     * Fields that are noise in a summary rather than content.
     */
    private const IGNORED = ['FORM_SUBMIT', 'REQUEST_TOKEN', 'captcha', 'submit'];

    /**
     * @param array<string, string> $values Field name => flattened value
     * @param array<string, string> $labels Field name => label
     * @param list<string>          $uploads Uploaded file names
     *
     * @return array<string, string>
     */
    public function build(array $values, array $labels, array $uploads = []): array
    {
        $rows = [];

        foreach ($values as $name => $value) {
            if (\in_array($name, self::IGNORED, true) || str_starts_with($name, 'captcha_')) {
                continue;
            }

            $rows[$name] = [
                'label' => $labels[$name] ?? $name,
                'value' => $value,
            ];
        }

        $filled = array_filter($rows, static fn (array $r): bool => '' !== trim($r['value']));

        return [
            'all_fields' => $this->text($rows),
            'all_fields_filled' => $this->text($filled),
            // The _html suffix marks these as markup, so MessageRenderer inserts them
            // without escaping (see MessageRenderer::RAW_HTML_TOKEN_SUFFIX).
            'all_fields_html' => $this->table($rows),
            'all_fields_filled_html' => $this->table($filled),
            'uploads' => implode("\n", $uploads),
            'uploads_html' => $this->list($uploads),
        ];
    }

    /**
     * @param array<string, array{label: string, value: string}> $rows
     */
    private function text(array $rows): string
    {
        $lines = [];

        foreach ($rows as $row) {
            // Indent continuation lines so a multi-line answer stays visually attached
            $value = str_replace("\n", "\n    ", $row['value']);
            $lines[] = \sprintf('%s: %s', $row['label'], $value);
        }

        return implode("\n", $lines);
    }

    /**
     * Inline styles rather than classes: this markup can land in a message that has no
     * layout, where a stylesheet would not exist to style it.
     *
     * @param array<string, array{label: string, value: string}> $rows
     */
    private function table(array $rows): string
    {
        if (!$rows) {
            return '';
        }

        $cells = '';

        foreach ($rows as $row) {
            $cells .= \sprintf(
                '<tr><th style="text-align:left;vertical-align:top;padding:7px 10px;border:1px solid #e3e5e8;background:#f6f8fa;font-size:13px;color:#57606a;white-space:nowrap">%s</th>'
                .'<td style="vertical-align:top;padding:7px 10px;border:1px solid #e3e5e8;font-size:14px">%s</td></tr>',
                $this->escape($row['label']),
                nl2br($this->escape($row['value']), false),
            );
        }

        return '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 16px">'.$cells.'</table>';
    }

    /**
     * @param list<string> $items
     */
    private function list(array $items): string
    {
        if (!$items) {
            return '';
        }

        $li = '';

        foreach ($items as $item) {
            $li .= '<li>'.$this->escape($item).'</li>';
        }

        return '<ul style="margin:0 0 16px;padding-left:20px">'.$li.'</ul>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
