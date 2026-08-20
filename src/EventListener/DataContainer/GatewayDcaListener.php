<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use VTInnovations\SimpleNotifyBundle\Gateway\GatewayRegistry;

/**
 * Lets every registered gateway contribute its own fields and palette to
 * tl_simple_gateway, so selecting a type shows only that type's settings.
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
        if ('tl_simple_gateway' !== $table) {
            return;
        }

        $dca = &$GLOBALS['TL_DCA']['tl_simple_gateway'];

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
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback(table: 'tl_simple_gateway', target: 'fields.type.options')]
    public function getTypeOptions(): array
    {
        $names = $this->gateways->getNames();

        return array_combine($names, $names);
    }
}
