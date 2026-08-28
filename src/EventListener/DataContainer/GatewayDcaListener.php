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
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;
use Contao\System;
use VTInnovations\CentralizedNotificationSuite\Gateway\GatewayRegistry;
use VTInnovations\CentralizedNotificationSuite\Gateway\WebhookGateway;

/**
 * Lets every registered gateway contribute its own fields and palette to
 * tl_notification_gateway, so selecting a type shows only that type's settings.
 *
 * This also drives the database schema: Contao's DcaSchemaProvider loads each DCA through
 * DcaLoader, which fires this hook, so the "sql" keys of gateway-contributed fields are
 * picked up by contao:migrate exactly like fields declared in the DCA file.
 */
#[AsHook('loadDataContainer')]
class GatewayDcaListener
{
    public function __construct(private readonly GatewayRegistry $gateways)
    {
    }

    public function __invoke(string $table): void
    {
        if ('tl_notification_gateway' !== $table) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_notification_gateway'];

        // "type" is the palette selector, so changing it has to reload the edit mask
        $dca['fields']['type']['eval']['submitOnChange'] = true;
        $dca['palettes']['__selector__'] = array_values(array_unique([...$dca['palettes']['__selector__'] ?? [], 'type']));

        foreach ($this->gateways->all() as $name => $gateway) {
            foreach ($gateway->getConfigFields() as $field => $definition) {
                // First gateway to claim a field name wins; a second one reusing it shares
                // the column rather than silently overwriting the definition.
                $dca['fields'][$field] ??= $definition;
            }

            $palette = $gateway->getPalette();

            $dca['palettes'][$name] = '{title_legend},title,type'
                .('' !== $palette ? ';'.$palette : '')
                .';{publish_legend},published';
        }

        $this->registerPayloadHelp();
    }

    /**
     * Publishes the payload examples for the help wizard.
     *
     * Done here rather than in a language file alone: the wizard is rendered by a separate
     * request (contao_backend_help) which loads the DCA but no language file of its own, so
     * the rows have to be pushed into XPL from a loadDataContainer hook -- the same reason
     * TokenHelpListener exists.
     */
    private function registerPayloadHelp(): void
    {
        System::loadLanguageFile('tl_notification_gateway');

        $rows = $GLOBALS['TL_LANG']['tl_notification_gateway']['payloadHelp'] ?? [];

        if (!$rows) {
            return;
        }

        $GLOBALS['TL_LANG']['XPL'][WebhookGateway::PAYLOAD_HELP_KEY] = array_map(
            static function (array $row): array {
                // Contao's be_help template prints every cell unescaped and turns a row into a
                // full-width heading when its first value is "headspan", so headings pass
                // through and everything else has to be escaped here.
                if ('headspan' === ($row[0] ?? '')) {
                    return $row;
                }

                $value = (string) ($row[1] ?? '');

                // JSON examples are indented and must survive as written, hence the <pre>
                $rendered = str_contains($value, '{')
                    ? '<pre style="margin:0;white-space:pre-wrap">'.StringUtil::specialchars($value).'</pre>'
                    : StringUtil::specialchars($value);

                return [StringUtil::specialchars((string) ($row[0] ?? '')), $rendered];
            },
            $rows,
        );
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_notification_gateway', target: 'fields.type.options')]
    public function getTypeOptions(): array
    {
        $names = $this->gateways->getNames();

        return array_combine($names, $names);
    }
}
