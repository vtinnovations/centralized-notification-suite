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
use VTInnovations\CentralizedNotificationSuite\Controller\StateUpdateEndpoint;
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

        foreach ($rendered as $operation) {
            $this->assertStringContainsString(
                "'".$operation."' === \$operation",
                $listener,
                \sprintf('The "%s" button has no branch in the handler.', $operation),
            );
        }
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

        $assets = glob(\dirname(__DIR__, 2).'/public/*.js') ?: [];
        $this->assertSame([], $assets, 'The bundle now ships JavaScript; browser lifecycle tests are required.');
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
