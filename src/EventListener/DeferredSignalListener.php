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

namespace VTInnovations\CentralizedNotificationSuite\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use VTInnovations\CentralizedNotificationSuite\Runtime\UsageSignals;

/**
 * Sends whatever the request queued, after the response has already gone out.
 *
 * kernel.terminate is the right moment: the visitor has their page, so an unresponsive
 * logging endpoint costs nobody anything visible. Under PHP-FPM this runs after
 * fastcgi_finish_request(); on other setups it still runs last, which is the best available.
 */
#[AsEventListener(event: TerminateEvent::class)]
class DeferredSignalListener
{
    public function __construct(private readonly UsageSignals $signals)
    {
    }

    public function __invoke(TerminateEvent $event): void
    {
        $this->signals->flush();
    }
}
