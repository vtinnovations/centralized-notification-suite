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
 * A genuine record that this installation may not use.
 *
 * Distinct from a verification failure on purpose: the signatures held, so the document was
 * really issued -- it simply names another product, tier, host or schema. The message is a
 * fixed internal category shown to no one; administrators get one generic sentence.
 */
class RecordNotApplicable extends \RuntimeException
{
    public function category(): string
    {
        return $this->getMessage();
    }
}
