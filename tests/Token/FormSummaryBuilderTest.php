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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Token;

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\Token\FormSummaryBuilder;

class FormSummaryBuilderTest extends TestCase
{
    private FormSummaryBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FormSummaryBuilder();
    }

    public function testUsesLabelsAndFallsBackToFieldNames(): void
    {
        $summary = $this->builder->build(
            ['name' => 'Jane', 'nolabel' => 'x'],
            ['name' => 'Your name'],
        );

        $this->assertSame("Your name: Jane\nnolabel: x", $summary['all_fields']);
    }

    public function testExcludesTechnicalFields(): void
    {
        $summary = $this->builder->build(
            ['FORM_SUBMIT' => 'form1', 'REQUEST_TOKEN' => 'abc', 'captcha_x' => '4', 'name' => 'Jane'],
            [],
        );

        $this->assertSame('name: Jane', $summary['all_fields']);
    }

    public function testFilledVariantDropsEmptyValues(): void
    {
        $summary = $this->builder->build(
            ['name' => 'Jane', 'phone' => '', 'note' => '   '],
            ['name' => 'Name', 'phone' => 'Phone', 'note' => 'Note'],
        );

        $this->assertStringContainsString('Phone', $summary['all_fields']);
        $this->assertStringNotContainsString('Phone', $summary['all_fields_filled']);
        $this->assertStringNotContainsString('Note', $summary['all_fields_filled']);
    }

    public function testIndentsContinuationLinesInTheTextVariant(): void
    {
        $summary = $this->builder->build(['note' => "One\nTwo"], ['note' => 'Note']);

        $this->assertSame("Note: One\n    Two", $summary['all_fields']);
    }

    /**
     * The HTML variants are inserted without escaping (their names end in _html), so the
     * escaping has to happen here or a submitted "<script>" would reach the recipient.
     */
    public function testHtmlVariantEscapesValuesAndLabels(): void
    {
        $summary = $this->builder->build(
            ['name' => '<script>alert(1)</script> & co'],
            ['name' => 'Name <b>'],
        );

        $this->assertStringNotContainsString('<script>', $summary['all_fields_html']);
        $this->assertStringContainsString('&lt;script&gt;', $summary['all_fields_html']);
        $this->assertStringContainsString('&amp; co', $summary['all_fields_html']);
        $this->assertStringContainsString('Name &lt;b&gt;', $summary['all_fields_html']);
    }

    public function testHtmlVariantKeepsLineBreaks(): void
    {
        $summary = $this->builder->build(['note' => "One\nTwo"], []);

        $this->assertStringContainsString('One<br>', $summary['all_fields_html']);
    }

    public function testHtmlVariantsAreEmptyWithoutRows(): void
    {
        $summary = $this->builder->build([], []);

        $this->assertSame('', $summary['all_fields_html']);
        $this->assertSame('', $summary['uploads_html']);
        $this->assertSame('', $summary['all_fields']);
    }

    public function testUploadTokens(): void
    {
        $summary = $this->builder->build([], [], ['a.pdf', 'b & c.jpg']);

        $this->assertSame("a.pdf\nb & c.jpg", $summary['uploads']);
        $this->assertStringContainsString('<li>a.pdf</li>', $summary['uploads_html']);
        $this->assertStringContainsString('b &amp; c.jpg', $summary['uploads_html']);
    }

    /**
     * Every token this class documents in FormTokenProvider must actually be produced.
     */
    public function testProducesAllDocumentedTokens(): void
    {
        $summary = $this->builder->build(['a' => 'b'], [], ['f.pdf']);

        foreach (['all_fields', 'all_fields_filled', 'all_fields_html', 'all_fields_filled_html', 'uploads', 'uploads_html'] as $token) {
            $this->assertArrayHasKey($token, $summary);
        }
    }
}
