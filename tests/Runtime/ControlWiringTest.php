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

namespace VTInnovations\CentralizedNotificationSuite\Tests\Runtime;

use PHPUnit\Framework\TestCase;
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuiteBundle;
use VTInnovations\CentralizedNotificationSuite\Controller\StateUpdateEndpoint;
use VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer\BackendAssetListener;
use VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer\SettingsSectionListener;
use VTInnovations\CentralizedNotificationSuite\Widget\ActivationPanel;

/**
 * Proves every visible control reaches a real handler.
 *
 * A licensing screen that renders is not a licensing screen that works. The failure this
 * guards against is a button that looks alive and does nothing -- which, for the Remove
 * control, would leave an administrator unable to revoke a compromised record.
 *
 * The controls here are plain submit buttons inside Contao's own settings form, so there is
 * no JavaScript to load, no asset ordering to get wrong and no event binding to miss. That is
 * why this file can establish the wiring statically: the chain is
 *
 *   button (name="cns_op") -> Contao settings form -> request token check -> onsubmit
 *   callback -> SettingsSectionListener::handle() -> ActivationChange -> ActivationStore
 *   -> ActivationGate -> re-rendered panel
 *
 * with no step that depends on the browser doing anything beyond submitting the form.
 */
class ControlWiringTest extends TestCase
{
    public function testEveryButtonNameHasAHandlerBranch(): void
    {
        $widget = (string) file_get_contents((new \ReflectionClass(ActivationPanel::class))->getFileName());
        $listener = (string) file_get_contents((new \ReflectionClass(SettingsSectionListener::class))->getFileName());

        preg_match_all('/name="cns_op" value="([a-z]+)"/', $widget, $matches);
        $rendered = array_unique($matches[1]);

        $this->assertNotEmpty($rendered, 'The panel renders no action buttons.');

        // Checked against the handler's own allow-list rather than against the shape of an if
        // statement. The list is what admits a submission *and* what the dispatch is written
        // around, so membership of it is the thing that actually means "this button is wired".
        preg_match('/private const OPERATIONS = \[(.*?)\];/s', $listener, $declared);

        $this->assertNotEmpty($declared, 'The handler no longer declares its operations in one list.');

        foreach ($rendered as $operation) {
            $this->assertStringContainsString(
                "'".$operation."'",
                $declared[1],
                \sprintf('The "%s" button is not an operation the handler accepts.', $operation),
            );
        }

        // And the guard has to be built from that list, or it could drift from the dispatch.
        $this->assertStringContainsString('\in_array($operation, self::OPERATIONS, true)', $listener);
    }

    /**
     * Both buttons and the key field must be reachable from the submitted request, or the
     * handler would silently do nothing.
     */
    public function testTheHandlerReadsTheFieldsThePanelRenders(): void
    {
        $listener = (string) file_get_contents((new \ReflectionClass(SettingsSectionListener::class))->getFileName());

        $this->assertStringContainsString("Input::post('cns_op')", $listener);
        $this->assertStringContainsString("Input::post('cns_licence_key')", $listener);
    }

    /**
     * The callbacks are registered as service callbacks. Registered any other way, Contao
     * would build the class with no constructor arguments and the screen would fatal.
     */
    public function testTheCallbacksAreRegisteredAsServiceCallbacks(): void
    {
        $listener = new \ReflectionClass(SettingsSectionListener::class);

        $targets = [];

        foreach ($listener->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if (str_ends_with($attribute->getName(), 'AsCallback')) {
                    $arguments = $attribute->getArguments();
                    $targets[$arguments['target'] ?? ''] = $method->getName();
                }
            }
        }

        $this->assertSame('handle', $targets['config.onsubmit'] ?? null);
        $this->assertSame('announceSection', $targets['config.onload'] ?? null);
    }

    /**
     * A state-changing action must not rely on the menu having hidden itself.
     */
    public function testTheHandlerChecksPermissionBeforeChangingAnything(): void
    {
        $listener = (string) file_get_contents((new \ReflectionClass(SettingsSectionListener::class))->getFileName());

        $this->assertStringContainsString('isGranted', $listener);
        $this->assertStringContainsString('AccessDeniedException', $listener);

        // The check must come before any of the three operations are dispatched.
        $guard = strpos($listener, 'isGranted');
        foreach (['$this->change->remove()', '$this->change->refresh(', '$this->change->activate('] as $call) {
            $this->assertGreaterThan($guard, strpos($listener, $call), 'An operation is dispatched before the permission check.');
        }
    }

    /**
     * There is no browser-side code in the control surface, which removes an entire class of
     * silent failure. If that ever changes, the lifecycle rules start applying and this test
     * should be replaced rather than deleted.
     */
    public function testTheControlSurfaceShipsNoJavaScript(): void
    {
        $widget = (string) file_get_contents((new \ReflectionClass(ActivationPanel::class))->getFileName());

        $this->assertStringNotContainsString('<script', $widget);
        $this->assertStringNotContainsString('addEventListener', $widget);
        $this->assertStringNotContainsString('TL_JAVASCRIPT', $widget);
    }

    /**
     * The bundle ships exactly one script, and it is the compatibility shim.
     *
     * Anything else appearing here means a control surface has grown browser-side wiring, and
     * the lifecycle rules -- asset load order, readiness, event binding, duplicate submission --
     * start applying to it.
     */
    public function testTheBundleShipsOnlyTheCompatibilityShim(): void
    {
        $root = \dirname(__DIR__, 2);

        $scripts = array_map(
            static fn (string $path): string => substr($path, \strlen($root) + 1),
            $this->shippedScripts($root.'/public'),
        );

        $this->assertSame(['public/js/mootools-dollar.js'], $scripts);
    }

    /**
     * The shim must stay a no-op on a healthy backend and must never touch the DOM.
     *
     * It runs on every one of this product's screens, on every installation. The guard is what
     * keeps it from taking "$" away from an extension that legitimately owns it, and the absence
     * of DOM work is what keeps it free of load-order questions.
     */
    public function testTheCompatibilityShimIsGuardedAndTouchesNoDom(): void
    {
        $script = (string) file_get_contents(\dirname(__DIR__, 2).'/public/js/mootools-dollar.js');
        $code = preg_replace('#/\*.*?\*/#s', '', $script) ?? $script;

        // Acts only once jQuery has actually taken the global.
        $this->assertStringContainsString('window.$ !== window.jQuery', $code);
        $this->assertStringContainsString('return;', $code);

        // Hands "$" back without taking window.jQuery away from anyone.
        $this->assertStringContainsString('window.jQuery.noConflict()', $code);
        $this->assertStringNotContainsString('noConflict(true)', $code);

        foreach (['querySelector', 'getElementById', 'addEventListener', 'addEvent', 'innerHTML'] as $dom) {
            $this->assertStringNotContainsString($dom, $code, 'The shim now touches the DOM and needs lifecycle tests.');
        }
    }

    /**
     * The published path is derived from the bundle class name by Symfony, so the two must not
     * drift apart -- a stale path is a silent 404 and the picker stays broken.
     */
    public function testTheAssetPathMatchesWhereSymfonyPublishesIt(): void
    {
        $bundle = (new \ReflectionClass(CentralizedNotificationSuiteBundle::class))->getShortName();
        $directory = preg_replace('/bundle$/', '', strtolower($bundle));

        $listener = (string) file_get_contents((new \ReflectionClass(BackendAssetListener::class))->getFileName());

        $this->assertStringContainsString(
            'bundles/'.$directory.'/js/mootools-dollar.js',
            $listener,
        );

        $this->assertFileExists(\dirname(__DIR__, 2).'/public/js/mootools-dollar.js');
    }

    /**
     * The asset is scoped to this product's own screens.
     *
     * tl_settings is shared with every other product that adds a section to it, so taking "$"
     * away there would break somebody else's field on a page that is not ours to change.
     */
    public function testTheAssetIsNotLoadedOnTheSharedSettingsScreen(): void
    {
        $listener = (string) file_get_contents((new \ReflectionClass(BackendAssetListener::class))->getFileName());

        preg_match('/private const TABLES = \[(.*?)\];/s', $listener, $tables);

        $this->assertNotEmpty($tables, 'The table list is no longer where this test can read it.');
        $this->assertStringNotContainsString("'tl_settings'", $tables[1]);
        $this->assertStringContainsString("'tl_notification_branding'", $tables[1]);

        // A front-end request reading these tables must not be handed a backend asset.
        $this->assertStringContainsString('isBackendRequest', $listener);
    }

    /**
     * @return list<string>
     */
    private function shippedScripts(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $found = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && 'js' === $file->getExtension()) {
                $found[] = $file->getPathname();
            }
        }

        sort($found);

        return $found;
    }

    /**
     * The key must never be echoed back into the page: rendering it would put it into browser
     * history, shared screens and page caches for no benefit.
     */
    public function testThePanelNeverRendersTheStoredKey(): void
    {
        $widget = (string) file_get_contents((new \ReflectionClass(ActivationPanel::class))->getFileName());

        $this->assertStringNotContainsString('->key()', $widget);
        $this->assertStringNotContainsString('license_key', $widget);

        // The input is rendered by the panel with a hard-coded empty value.
        $this->assertStringContainsString('value=""', $widget);
    }

    /**
     * The key must not be a DCA field.
     *
     * Contao repopulates a text field from the POST, so an entered key stayed on screen after
     * every save -- in browser history, in screen shares and in any page cache. The panel owns
     * the input instead, and renders it empty every time.
     */
    public function testTheKeyIsNotADataContainerField(): void
    {
        $listener = (string) file_get_contents((new \ReflectionClass(SettingsSectionListener::class))->getFileName());

        $this->assertStringNotContainsString(
            "\$GLOBALS['TL_DCA']['tl_settings']['fields']['cns_licence_key']",
            $listener,
            'The licence key is a DCA field again, so Contao will echo it back after a save.',
        );

        // The card is the only field put into the palette; the key input lives inside it.
        $this->assertStringContainsString("->addField('cns_panel'", $listener);
        $this->assertStringNotContainsString("addField('cns_licence_key'", $listener);
    }

    /**
     * The card goes into the shared V-T.ONE section rather than a section of our own.
     *
     * Contao's Palette::addLegend() does nothing when the legend already exists, so the same two
     * calls join another V-T.ONE package's section and create it when this is the only one
     * installed. Building the palette by string concatenation instead would produce a second
     * section with the same heading whenever another package got there first.
     */
    public function testTheCardJoinsTheSharedVtOneSection(): void
    {
        $listener = (string) file_get_contents((new \ReflectionClass(SettingsSectionListener::class))->getFileName());

        $this->assertStringContainsString("private const LEGEND = 'vtone_licence_legend';", $listener);
        $this->assertStringContainsString('PaletteManipulator::create()', $listener);
        $this->assertStringContainsString('->addLegend(self::LEGEND', $listener);

        // String surgery on the palette is what would duplicate the shared legend.
        $this->assertStringNotContainsString("['palettes']['default'] =", $listener);

        // A heading is supplied only if no other V-T.ONE package has already set one.
        foreach (['en', 'de'] as $language) {
            $file = (string) file_get_contents(\dirname(__DIR__, 2)."/contao/languages/$language/tl_settings.php");

            $this->assertStringContainsString(
                "\$GLOBALS['TL_LANG']['tl_settings']['vtone_licence_legend'] ??=",
                $file,
                \sprintf('The %s heading is assigned outright and would overwrite another package.', $language),
            );
        }
    }

    /**
     * The public endpoint must answer a wrong verb honestly rather than pretending not to
     * exist, and must refuse anything that is not JSON before it parses.
     */
    public function testTheInboundEndpointDeclaresItsMethodAndTypeRules(): void
    {
        $source = (string) file_get_contents((new \ReflectionClass(StateUpdateEndpoint::class))->getFileName());

        $this->assertStringContainsString('HTTP_METHOD_NOT_ALLOWED', $source);
        $this->assertStringContainsString("'Allow' => 'POST'", $source);
        $this->assertStringContainsString('HTTP_UNSUPPORTED_MEDIA_TYPE', $source);
        $this->assertStringContainsString('HTTP_REQUEST_ENTITY_TOO_LARGE', $source);
    }
}
