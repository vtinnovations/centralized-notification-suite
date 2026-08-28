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

namespace VTInnovations\CentralizedNotificationSuite\Runtime;

/**
 * How the issuing service identifies this product.
 *
 * These are registration values, not preferences: the service matches on them, so changing
 * one here without changing it there makes every activation fail. They live apart from the
 * service endpoints and from the verification keys so that no single file describes the whole
 * exchange.
 */
final class ProductProfile
{
    public const NAME = 'Centralized Notification Suite';

    public const SLUG = 'centralized-notification-suite';

    public const CATALOGUE_ID = 'vt-centralized-notification-suite';

    /**
     * The wire format this build understands. A document announcing anything else is rejected
     * rather than read optimistically -- a future revision may move a field this code trusts.
     */
    public const SCHEMA = 2;

    /**
     * The package identifiers this product accepts.
     *
     * This build is distributed under the free tier, so "free" is the only value that grants
     * anything. "Free" describes price, not obligation: an authenticated record is still
     * required, and a paid or time-limited record is rejected here because it does not match
     * how this product is sold.
     *
     * @var list<string>
     */
    public const ACCEPTED_PACKAGES = ['free'];

    /**
     * Whether this product's records are expected to be perpetual. Kept next to the accepted
     * packages because the two together are the tier contract.
     */
    public const REQUIRES_LIFETIME = true;
}
