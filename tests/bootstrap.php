<?php

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
        require $autoload;

        return;
    }
}

throw new RuntimeException(
    'Could not find an autoloader. Run "composer install" in the bundle directory, or run the '
    .'tests from inside a Contao project that requires it.',
);
