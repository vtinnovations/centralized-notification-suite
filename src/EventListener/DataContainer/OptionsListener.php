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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Mailer\AvailableTransports;
use Contao\DataContainer;
use VTInnovations\CentralizedNotificationSuite\Message\DesignLibrary;
use VTInnovations\CentralizedNotificationSuite\Model\BlockModel;
use VTInnovations\CentralizedNotificationSuite\Model\GatewayModel;
use VTInnovations\CentralizedNotificationSuite\Model\MessageModel;
use VTInnovations\CentralizedNotificationSuite\Model\NotificationModel;
use VTInnovations\CentralizedNotificationSuite\Model\TemplateModel;
use VTInnovations\CentralizedNotificationSuite\Token\TokenRegistry;

class OptionsListener
{
    public function __construct(
        private readonly AvailableTransports $transports,
        private readonly Locales $locales,
        private readonly DesignLibrary $designs,
        private readonly TokenRegistry $tokens,
    ) {
    }

    #[AsCallback(table: 'tl_notification_gateway', target: 'fields.mailer_transport.options')]
    public function getTransportOptions(): array
    {
        return $this->transports->getTransportOptions();
    }

    /**
     * Only published gateways: offering an unpublished one produces a message that looks
     * configured but silently never sends.
     */
    #[AsCallback(table: 'tl_notification_message', target: 'fields.gateway.options')]
    public function getGatewayOptions(): array
    {
        $options = [];

        foreach (GatewayModel::findAllPublished() ?? [] as $gateway) {
            $options[$gateway->id] = $gateway->title;
        }

        return $options;
    }

    #[AsCallback(table: 'tl_notification_message', target: 'fields.template.options')]
    public function getTemplateOptions(): array
    {
        $options = [];

        foreach (TemplateModel::findAllPublished() ?? [] as $template) {
            $options[$template->id] = $template->title;
        }

        return $options;
    }

    #[AsCallback(table: 'tl_notification_message', target: 'fields.language.options')]
    public function getLanguageOptions(): array
    {
        return $this->locales->getLocales();
    }

    /**
     * Only "form" notifications: offering the others would let an editor attach a message
     * whose tokens a form submission cannot possibly fill.
     */
    #[AsCallback(table: 'tl_form', target: 'fields.notification_ids.options')]
    public function getFormNotificationOptions(): array
    {
        return $this->optionsForType(NotificationModel::TYPE_FORM);
    }

    /**
     * The module's own type decides which notifications it may trigger, so a registration
     * module never lists newsletter notifications and vice versa.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.notification_ids.options')]
    public function getModuleNotificationOptions(DataContainer|null $dc = null): array
    {
        $moduleType = (string) ($dc?->activeRecord->type ?? '');
        $type = AddNotificationFieldListener::getNotificationTypeForModule($moduleType);

        return null !== $type ? $this->optionsForType($type) : [];
    }

    /**
     * @return array<int, string>
     */
    private function optionsForType(string $type): array
    {
        $options = [];

        foreach (NotificationModel::findByType($type) ?? [] as $notification) {
            $options[(int) $notification->id] = $notification->title.' ('.$notification->alias.')';
        }

        return $options;
    }

    #[AsCallback(table: 'tl_notification_template', target: 'fields.design.options')]
    public function getDesignOptions(): array
    {
        return $this->designs->getGroupedOptions();
    }

    /**
     * The generated-content tokens a block may insert.
     *
     * Resolved from the notification the block ultimately belongs to, so a form notification
     * offers form tokens and nothing else -- offering a token the trigger can never fill is
     * how a message ends up with an empty section in it.
     *
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_notification_block', target: 'fields.token_name.options')]
    public function getBlockTokenOptions(DataContainer|null $dc = null): array
    {
        $type = NotificationModel::TYPE_CUSTOM;

        if ($dc && ($block = BlockModel::findByPk($dc->id))) {
            $message = MessageModel::findByPk($block->pid);
            $notification = $message ? NotificationModel::findByPk($message->pid) : null;
            $type = (string) ($notification?->type ?: NotificationModel::TYPE_CUSTOM);
        }

        $options = [];

        foreach ($this->tokens->getDefinitionsFor($type) as $token => $description) {
            // Documented families such as ##<field name>## are not real token names
            if (str_contains($token, '<')) {
                continue;
            }

            $options[$token] = $token.' - '.$description;
        }

        return $options;
    }
}
