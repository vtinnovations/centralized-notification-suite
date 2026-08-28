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

namespace VTInnovations\CentralizedNotificationSuite\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationChange;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationGate;
use VTInnovations\CentralizedNotificationSuite\Runtime\ActivationRefused;
use VTInnovations\CentralizedNotificationSuite\Runtime\UsageSignals;

/**
 * The product's section in Contao's own Settings screen -- the one place this installation is
 * activated, updated or deactivated.
 *
 * It is part of the settings form rather than a screen of its own, which means Contao's
 * request token, its permission handling and its save cycle all apply without this code
 * reimplementing any of them. Every control is a submit button in that form; there is no
 * JavaScript, so there is no asset that can fail to load and leave a button that looks alive
 * and does nothing.
 *
 * Neither the key nor any part of the record is written to the settings file. The key belongs
 * in the signed record, and localconfig.php is committed to version control on plenty of
 * sites.
 */
#[AsHook('loadDataContainer')]
class SettingsSectionListener
{
    private const LEGEND = 'cns_licence_legend';

    public function __construct(
        private readonly ActivationGate $gate,
        private readonly ActivationChange $change,
        private readonly UsageSignals $signals,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $table): void
    {
        if ('tl_settings' !== $table) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = \sprintf(
            '{%s},cns_panel,cns_licence_key;%s',
            self::LEGEND,
            $GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] ?? '',
        );

        $GLOBALS['TL_DCA']['tl_settings']['fields']['cns_panel'] = [
            'inputType' => 'cnsActivationPanel',
            // Nothing to persist: the panel reports state that lives in the signed record.
            'eval' => ['doNotSave' => true, 'tl_class' => 'clr'],
        ];

        $GLOBALS['TL_DCA']['tl_settings']['fields']['cns_licence_key'] = [
            'inputType' => 'text',
            'eval' => [
                'doNotSave' => true,
                'maxlength' => 255,
                'decodeEntities' => true,
                'tl_class' => 'long clr',
                // Not a password field: an administrator pasting a key needs to see it, and
                // it is never rendered back into the page afterwards.
                'preserveTags' => false,
            ],
        ];

    }

    /**
     * Runs on every save of the settings form.
     *
     * Registered as a service callback rather than appended to the DCA by hand: Contao builds
     * a plain [class, method] callback with no constructor arguments, which would fail the
     * moment this class needed a dependency.
     *
     * Reached only after Contao has verified the request token and the backend session, so it
     * starts from an authenticated administrator and checks what they may do rather than
     * whether they are signed in at all.
     */
    #[AsCallback(table: 'tl_settings', target: 'config.onsubmit')]
    public function handle(DataContainer|null $dc = null): void
    {
        $this->dispatch();
    }

    #[AsCallback(table: 'tl_settings', target: 'config.onload')]
    public function announceSection(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || 'settings' !== $request->query->get('do')) {
            return;
        }

        // The one place the session notice is raised: an administrator has actually opened
        // this product's section. Not from a kernel listener, which would fire for every
        // request in the backend and for screens belonging to other products.
        $this->signals->sectionOpened($this->gate->current());
    }

    /**
     * Performs whichever operation the submitted button asked for.
     */
    private function dispatch(): void
    {
        $operation = (string) Input::post('cns_op');
        $key = trim((string) Input::post('cns_licence_key'));

        if ('' === $operation && '' === $key) {
            return;
        }

        // Settings is an administrator screen, but the check is made here as well: this
        // method changes entitlement, and it must not rely on the menu having hidden itself.
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'settings')) {
            throw new AccessDeniedException('Not allowed to change the product activation.');
        }

        $labels = $GLOBALS['TL_LANG']['tl_settings'] ?? [];

        try {
            if ('remove' === $operation) {
                $this->change->remove();
                $this->signals->forgetClaim();
                Message::addConfirmation($labels['cnsRemoved'] ?? 'The licence has been removed.');

                return;
            }

            if ('refresh' === $operation) {
                $this->change->refresh($key);
                Message::addConfirmation($labels['cnsUpdated'] ?? 'The licence has been updated.');

                return;
            }

            if ('' !== $key) {
                $this->change->activate($key);
                Message::addConfirmation($labels['cnsActivated'] ?? 'The licence has been activated.');
            }
        } catch (ActivationRefused $e) {
            // One message for every reason. Distinguishing "unknown key" from "wrong host"
            // would let someone test keys against this form.
            Message::addError($labels['cnsFailed'] ?? 'The licence could not be verified. Please check the key and try again.');
        }
    }
}
