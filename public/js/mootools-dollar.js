/*
 * Centralized Notification Suite
 *
 * Package: vtinnovations/centralized-notification-suite
 * Copyright: V&T Innovations Team
 * Licence: proprietary
 * Website: https://www.v-t.one
 */

/*
 * Gives "$" back to MooTools on this product's backend screens.
 *
 * Contao's backend is MooTools, and its widgets bind themselves with inline scripts that call
 * the global "$". The file picker's is:
 *
 *     $("ft_logo").addEvent("click", function (e) { ... Backend.openModalSelector({...}) ... })
 *
 * An extension that loads jQuery into the backend without calling noConflict() takes that
 * global over. jQuery's "$" has no addEvent, so the inline call throws, the picker link is
 * never bound, and clicking it navigates to the file manager as a full page instead of opening
 * the modal -- which is why Apply and Cancel are missing: openModalSelector is what creates
 * them. Contao's own domready code fails the same way, e.g. tableWizardSetWidth, because
 * jQuery returns a truthy empty set where MooTools returns null.
 *
 * Three things keep this from becoming somebody else's bug:
 *
 *   1. It is loaded only on this product's own screens, so an extension that wants "$" keeps it
 *      everywhere else. See BackendAssetListener for the registration.
 *   2. It does nothing unless jQuery has actually taken the global, so on a backend without
 *      such an extension -- nearly all of them -- it is a no-op.
 *   3. window.jQuery is left in place, so code written as jQuery(...) or
 *      (function ($) { ... })(jQuery) keeps working.
 *
 * No DOM is read or written, so there is no lifecycle or readiness question here. The only
 * ordering requirement is that this runs after whichever asset loaded jQuery and before the
 * body is parsed, which is why it is appended to TL_JAVASCRIPT rather than prepended.
 */
(function () {
    'use strict';

    if (!window.jQuery || window.$ !== window.jQuery) {
        return;
    }

    // MooTools loads first in Contao's backend template, so this hands "$" back to exactly the
    // function jQuery displaced.
    window.jQuery.noConflict();

    // Belt and braces: had jQuery somehow loaded before MooTools, the line above would have
    // restored an undefined "$" rather than MooTools'.
    if (typeof window.$ !== 'function' && typeof window.document.id === 'function') {
        window.$ = window.document.id;
    }
})();
