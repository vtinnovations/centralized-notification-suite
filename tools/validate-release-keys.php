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
 * Refuses to let a build go out that could never verify anything.
 *
 * The pinned verification material is what every other control ultimately rests on. If it is
 * missing, truncated, or mangled by a packaging step, the product does not fail loudly at
 * build time by itself -- it fails much later, on a customer's site, as an activation that
 * cannot succeed for reasons nobody can see. This check turns that into a build failure.
 *
 * Run it before packaging a release, and in CI:
 *
 *     composer release:check
 *
 * The output deliberately carries no key material, no identifiers and no fingerprints. It
 * says whether the material is usable and nothing more, because build logs are widely
 * readable and there is no reason for them to describe the trust anchor.
 */

$autoload = __DIR__.'/../vendor/autoload.php';

if (!is_file($autoload)) {
    // A path install shares the host project's autoloader.
    $autoload = __DIR__.'/../../../vendor/autoload.php';
}

if (!is_file($autoload)) {
    fwrite(STDERR, "release:check — cannot locate an autoloader; run composer install first.\n");

    exit(1);
}

require $autoload;

use VTInnovations\CentralizedNotificationSuite\Distribution\IssuerKeyring;

$failures = [];

if (!\extension_loaded('sodium')) {
    $failures[] = 'the sodium extension is not available, so signatures could never be checked';
}

$ring = new IssuerKeyring();

if (!$ring->isUsable(time())) {
    // isUsable() is false when the material is absent, the wrong length, or fails its own
    // fingerprint check -- all of which mean a shipped build would reject every genuine
    // response.
    $failures[] = 'no usable pinned verification material is present in this build';
}

if ($failures) {
    fwrite(STDERR, "release:check FAILED\n");

    foreach ($failures as $failure) {
        fwrite(STDERR, '  - '.$failure."\n");
    }

    fwrite(STDERR, "\nThis build must not be distributed.\n");

    exit(1);
}

fwrite(STDOUT, \sprintf(
    "release:check OK — %d verification key(s) usable.\n",
    \count($ring->usable(time())),
));

exit(0);
