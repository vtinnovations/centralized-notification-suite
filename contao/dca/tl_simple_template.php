<?php

use Contao\DataContainer;
use Contao\DC_Table;
use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

/**
 * Reusable e-mail layouts. A message picks one and supplies only its own body, so the
 * branded frame and the CSS live in one place instead of being pasted into every message.
 */
$GLOBALS['TL_DCA']['tl_simple_template'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'title',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'preview' => [
                'icon' => 'preview.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    // Two ways to frame a body, never both at once: paste a complete document from a
    // designer, or supply a header/footer pair and let the bundle assemble the document.
    'palettes' => [
        '__selector__' => ['layout_mode'],
        'default' => '{title_legend},title;{layout_legend},preheader,layout_mode',
        'wrapper' => '{title_legend},title;{layout_legend},preheader,layout_mode,wrapper_html;{style_legend},css,inline_css;{publish_legend},published',
        'header_footer' => '{title_legend},title;{layout_legend},preheader,layout_mode,header_html,footer_html;{style_legend},css,inline_css;{publish_legend},published',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'preheader' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'layout_mode' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['header_footer', 'wrapper'],
            'reference' => &$GLOBALS['TL_LANG']['tl_simple_template']['layout_mode_options'],
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(32) NOT NULL default 'header_footer'",
        ],
        'wrapper_html' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'ace|html', 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'tl_class' => 'clr long'],
            'sql' => "text NULL",
        ],
        'header_html' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'ace|html', 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'tl_class' => 'clr long'],
            'sql' => "text NULL",
        ],
        'footer_html' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'ace|html', 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'tl_class' => 'clr long'],
            'sql' => "text NULL",
        ],
        'css' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'ace|css', 'preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'tl_class' => 'clr long'],
            'sql' => "text NULL",
        ],
        'inline_css' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default '1'",
        ],
        'published' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default '1'",
        ],
    ],
];
