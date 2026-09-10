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

namespace VTInnovations\CentralizedNotificationSuite\Token;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tokens available in every notification, whatever triggered it. These are the values
 * editors otherwise hard-code into a message and then have to hunt down when the site
 * moves domain or the admin address changes.
 */
class UniversalTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getType(): string
    {
        return self::TYPE_ANY;
    }

    public function getDefinitions(): array
    {
        return [
            'admin_email' => 'The administrator address from the Contao settings',
            'date' => 'Today\'s date in the site\'s date format',
            'time' => 'The current time in the site\'s time format',
            'datim' => 'The current date and time',
            'host' => 'The host name the notification was triggered on',
            'url' => 'The base URL of the site, e.g. https://example.com',
            'page_id' => 'ID of the page the notification was triggered from',
            'page_title' => 'Title of that page',
            'page_url' => 'Absolute URL of that page',
        ];
    }

    public function getValues(): array
    {
        $this->framework->initialize();

        $request = $this->requestStack->getCurrentRequest();

        $values = [
            'admin_email' => (string) Config::get('adminEmail'),
            'date' => Date::parse((string) Config::get('dateFormat')),
            'time' => Date::parse((string) Config::get('timeFormat')),
            'datim' => Date::parse((string) Config::get('datimFormat')),
            'host' => (string) $request?->getHost(),
            'url' => null !== $request ? $request->getSchemeAndHttpHost() : '',
            'page_id' => '',
            'page_title' => '',
            'page_url' => '',
        ];

        // Only set in a front-end request; a notification sent from the console or a cron
        // job has no page, and blank is more honest than a stale value.
        $page = $GLOBALS['objPage'] ?? null;

        if ($page instanceof PageModel) {
            $values['page_id'] = (string) $page->id;
            $values['page_title'] = (string) $page->title;
            $values['page_url'] = $page->getAbsoluteUrl();
        }

        return $values;
    }
}
