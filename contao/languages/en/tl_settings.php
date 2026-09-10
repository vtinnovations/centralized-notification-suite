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
 * The heading of the shared V-T.ONE section, which every V-T.ONE package renders a card in.
 *
 * Assigned with ??= and not =: whichever package's language file loads first supplies the text,
 * and none of them overwrites another's. Without the fallback the heading would read
 * "vtone_licence_legend" on a site where this is the only V-T.ONE package installed.
 */
$GLOBALS['TL_LANG']['tl_settings']['vtone_licence_legend'] ??= 'V-T.ONE Licence management';

/*
 * The field label is the product name: it is the heading above the card, and the licence
 * manager stacks one such card per V-T.ONE package.
 */
$GLOBALS['TL_LANG']['tl_settings']['cns_panel'] = ['Centralized Notification Suite', ''];

$GLOBALS['TL_LANG']['tl_settings']['cnsTier'] = 'Package';
$GLOBALS['TL_LANG']['tl_settings']['cnsHost'] = 'Licensed host';
$GLOBALS['TL_LANG']['tl_settings']['cnsHosts'] = 'Covered hosts';
$GLOBALS['TL_LANG']['tl_settings']['cnsVersion'] = 'Version';
$GLOBALS['TL_LANG']['tl_settings']['cnsTerm'] = 'Term';
$GLOBALS['TL_LANG']['tl_settings']['cnsPerpetual'] = 'Perpetual';
$GLOBALS['TL_LANG']['tl_settings']['cnsActive'] = 'Licence active';
$GLOBALS['TL_LANG']['tl_settings']['cnsInactive'] = 'No active licence';

$GLOBALS['TL_LANG']['tl_settings']['cnsKeyLabel'] = 'Licence key';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyPlaceholder'] = 'XXXXX-XXXXX-XXXXX-XXXXX';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyHelp'] = 'Enter the licence key issued for this website. The key is stored inside the signed licence record, not in the settings file, and is never shown again.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivate'] = 'Verify and Activate Licence';
$GLOBALS['TL_LANG']['tl_settings']['cnsRefresh'] = 'Update Licence';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemove'] = 'Remove Licence';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoveConfirm'] = 'Remove the stored licence? Notifications will stop being sent until a licence is activated again.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivated'] = 'The licence has been activated.';
$GLOBALS['TL_LANG']['tl_settings']['cnsUpdated'] = 'The licence has been updated.';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoved'] = 'The licence has been removed.';
$GLOBALS['TL_LANG']['tl_settings']['cnsFailed'] = 'The licence could not be verified. Please check the key and try again.';
$GLOBALS['TL_LANG']['tl_settings']['cnsKeyMissing'] = 'Enter a licence key first.';
$GLOBALS['TL_LANG']['tl_settings']['cnsNothingStored'] = 'There is no stored licence to update. Enter a licence key and activate it.';

$GLOBALS['TL_LANG']['tl_settings']['cnsReasonNone'] = 'No licence has been activated yet.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonHost'] = 'No website root page has a domain configured, or the licence does not cover any configured domain.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonExpired'] = 'The stored licence has expired.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonFuture'] = 'The stored licence is not valid yet.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonGeneric'] = 'The stored licence could not be verified. Please activate it again.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonRevoked'] = 'The licence for this website has been withdrawn. Please contact us if you believe this is a mistake.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonSuperseded'] = 'The stored licence is older than the last one this installation received, so it has been ignored. Activate the current licence again.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonStale'] = 'The licence has not been re-checked within the required period. Use "Update Licence" once this website can reach the licence service again.';
