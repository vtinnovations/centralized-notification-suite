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

namespace VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;

/**
 * Adds a "Notifications" picker to the form generator and to the front-end modules that
 * can trigger a notification.
 *
 * Per-module rather than a global mapping: a site commonly has more than one registration
 * or comment form (different member groups, different sections) and they rarely want to
 * send the same mail. This also matches how editors already configure Contao.
 */
#[AsHook('loadDataContainer')]
class AddNotificationFieldListener
{
    public const FIELD = 'notification_ids';

    /**
     * Front-end module type => the notification type it can trigger. Determines which
     * notifications the picker offers, so a registration module cannot be wired to a
     * newsletter notification whose tokens it could never fill.
     */
    private const MODULE_TYPES = [
        'registration' => NotificationModel::TYPE_MEMBER,
        'personalData' => NotificationModel::TYPE_MEMBER,
        'closeAccount' => NotificationModel::TYPE_MEMBER,
        'changePassword' => NotificationModel::TYPE_MEMBER,
        'lostPassword' => NotificationModel::TYPE_MEMBER,
        'subscribe' => NotificationModel::TYPE_NEWSLETTER,
        'unsubscribe' => NotificationModel::TYPE_NEWSLETTER,
    ];

    public function __invoke(string $table): void
    {
        // Comments are deliberately absent: a stored comment cannot be traced back to the
        // content element that rendered its form, so a per-element setting would be
        // unreadable at send time. See CommentNotificationListener.
        match ($table) {
            'tl_form' => $this->addToForm(),
            'tl_module' => $this->addToModules(),
            default => null,
        };
    }

    private function addToForm(): void
    {
        $this->addField('tl_form');

        PaletteManipulator::create()
            ->addLegend('notification_legend', 'store_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(self::FIELD, 'notification_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', 'tl_form')
        ;
    }

    private function addToModules(): void
    {
        $this->addField('tl_module');

        $manipulator = PaletteManipulator::create()
            ->addLegend('notification_legend', 'template_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(self::FIELD, 'notification_legend', PaletteManipulator::POSITION_APPEND)
        ;

        foreach (array_keys(self::MODULE_TYPES) as $type) {
            // Only touch palettes that exist: the newsletter bundle may not be installed
            if (isset($GLOBALS['TL_DCA']['tl_module']['palettes'][$type])) {
                $manipulator->applyToPalette($type, 'tl_module');
            }
        }
    }

    private function addField(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['fields'][self::FIELD] = [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ];
    }

    /**
     * The notification type a given front-end module may trigger.
     */
    public static function getNotificationTypeForModule(string $moduleType): string|null
    {
        return self::MODULE_TYPES[$moduleType] ?? null;
    }
}
