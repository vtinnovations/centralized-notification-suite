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

/*
 * The bundle's own vendor/ when installed standalone, otherwise the host project's --
 * during development the package lives inside a Contao installation as a path repository,
 * so its dependencies are only present one level up.
 */
$candidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

foreach ($candidates as $autoload) {
    if (file_exists($autoload)) {
        $loader = require $autoload;

        // When the bundle is installed as a path repository the host project's autoloader
        // knows its src/ but not its autoload-dev, so test-only helpers (fixtures, traits)
        // would not resolve. PHPUnit loads *Test.php files itself, which is why this only
        // shows up once a test references a shared fixture class.
        if ($loader instanceof \Composer\Autoload\ClassLoader) {
            $loader->addPsr4('VTInnovations\\CentralizedNotificationSuite\\Tests\\', __DIR__);
        }

        return;
    }
}

throw new RuntimeException(
    'Could not find an autoloader. Run "composer install" in the bundle directory, or run the '
    .'tests from inside a Contao project that requires it.',
);
