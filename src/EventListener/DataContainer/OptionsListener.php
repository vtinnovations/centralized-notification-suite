<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Mailer\AvailableTransports;
use Contao\DataContainer;
use VTInnovations\SimpleNotifyBundle\Model\GatewayModel;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;
use VTInnovations\SimpleNotifyBundle\Model\TemplateModel;

class OptionsListener
{
    public function __construct(
        private readonly AvailableTransports $transports,
        private readonly Locales $locales,
    ) {
    }

    #[AsCallback(table: 'tl_simple_gateway', target: 'fields.mailer_transport.options')]
    public function getTransportOptions(): array
    {
        return $this->transports->getTransportOptions();
    }

    /**
     * Only published gateways: offering an unpublished one produces a message that looks
     * configured but silently never sends.
     */
    #[AsCallback(table: 'tl_simple_message', target: 'fields.gateway.options')]
    public function getGatewayOptions(): array
    {
        $options = [];

        foreach (GatewayModel::findAllPublished() ?? [] as $gateway) {
            $options[$gateway->id] = $gateway->title;
        }

        return $options;
    }

    #[AsCallback(table: 'tl_simple_message', target: 'fields.template.options')]
    public function getTemplateOptions(): array
    {
        $options = [];

        foreach (TemplateModel::findAllPublished() ?? [] as $template) {
            $options[$template->id] = $template->title;
        }

        return $options;
    }

    #[AsCallback(table: 'tl_simple_message', target: 'fields.language.options')]
    public function getLanguageOptions(): array
    {
        return $this->locales->getLocales();
    }

    /**
     * Only "form" notifications: offering the others would let an editor attach a message
     * whose tokens a form submission cannot possibly fill.
     */
    #[AsCallback(table: 'tl_form', target: 'fields.simple_notify_notifications.options')]
    public function getFormNotificationOptions(): array
    {
        return $this->optionsForType(NotificationModel::TYPE_FORM);
    }

    /**
     * The module's own type decides which notifications it may trigger, so a registration
     * module never lists newsletter notifications and vice versa.
     */
    #[AsCallback(table: 'tl_module', target: 'fields.simple_notify_notifications.options')]
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
}
