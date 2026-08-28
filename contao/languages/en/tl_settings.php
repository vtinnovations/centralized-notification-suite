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
 * The product's section in Contao's Settings screen. The legend text is the section heading
 * an administrator sees.
 */
$GLOBALS['TL_LANG']['tl_settings']['cns_licence_legend'] = 'Centralized Notification Suite Licence management';

$GLOBALS['TL_LANG']['tl_settings']['cns_panel'] = ['Licence status', 'The current activation of this installation.'];
$GLOBALS['TL_LANG']['tl_settings']['cns_licence_key'] = ['Licence key', 'Enter the licence key issued for this website, then save. The key is stored inside the signed licence record, not in the settings file.'];

$GLOBALS['TL_LANG']['tl_settings']['cnsStatus'] = 'Status';
$GLOBALS['TL_LANG']['tl_settings']['cnsTier'] = 'Package';
$GLOBALS['TL_LANG']['tl_settings']['cnsHost'] = 'Licensed host';
$GLOBALS['TL_LANG']['tl_settings']['cnsHosts'] = 'Covered hosts';
$GLOBALS['TL_LANG']['tl_settings']['cnsVersion'] = 'Version';
$GLOBALS['TL_LANG']['tl_settings']['cnsTerm'] = 'Term';
$GLOBALS['TL_LANG']['tl_settings']['cnsPerpetual'] = 'Perpetual';
$GLOBALS['TL_LANG']['tl_settings']['cnsDetail'] = 'Detail';
$GLOBALS['TL_LANG']['tl_settings']['cnsActive'] = 'Active';
$GLOBALS['TL_LANG']['tl_settings']['cnsInactive'] = 'Not activated';

$GLOBALS['TL_LANG']['tl_settings']['cnsRefresh'] = 'Update licence';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemove'] = 'Remove licence';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoveConfirm'] = 'Remove the stored licence? Notifications will stop being sent until a licence is activated again.';

$GLOBALS['TL_LANG']['tl_settings']['cnsActivated'] = 'The licence has been activated.';
$GLOBALS['TL_LANG']['tl_settings']['cnsUpdated'] = 'The licence has been updated.';
$GLOBALS['TL_LANG']['tl_settings']['cnsRemoved'] = 'The licence has been removed.';
$GLOBALS['TL_LANG']['tl_settings']['cnsFailed'] = 'The licence could not be verified. Please check the key and try again.';

$GLOBALS['TL_LANG']['tl_settings']['cnsReasonNone'] = 'No licence has been activated yet.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonHost'] = 'No website root page has a domain configured, or the licence does not cover any configured domain.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonExpired'] = 'The stored licence has expired.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonFuture'] = 'The stored licence is not valid yet.';
$GLOBALS['TL_LANG']['tl_settings']['cnsReasonGeneric'] = 'The stored licence could not be verified. Please activate it again.';
