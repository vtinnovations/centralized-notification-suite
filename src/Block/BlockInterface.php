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

namespace VTInnovations\CentralizedNotificationSuite\Block;

use VTInnovations\CentralizedNotificationSuite\Message\Branding;

/**
 * One kind of content block a message body can be built from. Implementations are
 * registered automatically via the "centralized_notification_suite.block" tag (see config/services.yaml).
 *
 * A block declares its own backend configuration the way a gateway does: getConfigFields()
 * returns DCA field definitions that BlockDcaListener merges into tl_notification_block, and
 * getPalette() the palette shown when this type is selected. Unlike a gateway, a block
 * declares only its *type-specific* fields -- the ones several types share (heading, image,
 * link) live in contao/dca/tl_notification_block.php, so the schema does not depend on which
 * type happens to be registered first.
 */
interface BlockInterface
{
    /**
     * Stored in tl_notification_block.type, so it must stay stable once released.
     *
     * Never name a type "*Start" or "*Stop": DC_Table tests the type column against
     * $GLOBALS['TL_WRAPPERS'] for any list rendered as a grid, and would treat the block as
     * a wrapper element.
     */
    public function getName(): string;

    /**
     * Optgroup heading for the type select.
     */
    public function getGroup(): string;

    /**
     * Type-specific DCA field definitions keyed by field name. Each definition needs an
     * "sql" key, or contao:migrate will never create the column.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getConfigFields(): array;

    /**
     * Palette fragment, without the shared type/spacing/visibility legends.
     */
    public function getPalette(): string;

    /**
     * The block's e-mail-safe markup.
     *
     * Implementations escape their own field values but leave ##tokens## byte-for-byte
     * intact: MessageRenderer::renderHtml() resolves them once over the whole composed body.
     * A block must never call SimpleTokenParser or InsertTagParser itself.
     *
     * @param array<string, mixed> $row Raw tl_notification_block row
     */
    public function render(array $row, Branding $branding): string;
}
