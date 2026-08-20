<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Token;

use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;

/**
 * Documents the tokens a form submission provides. The values themselves come from the
 * submission (see ProcessFormDataListener), which is why getValues() is empty.
 */
class FormTokenProvider implements TokenProviderInterface
{
    public function getType(): string
    {
        return NotificationModel::TYPE_FORM;
    }

    public function getDefinitions(): array
    {
        return [
            '<field name>' => 'The value submitted for that field -- use the field\'s "name" from the form generator',
            'label_<field name>' => 'That field\'s label, for building your own summaries',
            'all_fields' => 'Every submitted field as "Label: value" lines (plain text)',
            'all_fields_filled' => 'The same, skipping fields the visitor left empty',
            'all_fields_html' => 'Every submitted field as a styled table (HTML)',
            'all_fields_filled_html' => 'The same table, skipping empty fields',
            'uploads' => 'Names of the files uploaded with the form, one per line',
            'uploads_html' => 'The same as an HTML list',
            'form_id' => 'ID of the submitted form',
            'form_title' => 'Title of the submitted form',
        ];
    }

    public function getValues(): array
    {
        return [];
    }
}
