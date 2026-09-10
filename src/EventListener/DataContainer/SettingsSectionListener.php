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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
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
    /**
     * The shared V-T.ONE section, not one of our own.
     *
     * Every V-T.ONE package renders one card in this legend, so an administrator manages all of
     * them in one place instead of hunting through a settings screen with a section per product.
     */
    private const LEGEND = 'vtone_licence_legend';

    /**
     * The operations the panel's buttons may ask for.
     *
     * The one list, used to admit a submission and to dispatch it, so a button can never be
     * rendered for something this handler does not implement.
     *
     * @var list<string>
     */
    private const OPERATIONS = ['activate', 'refresh', 'remove'];

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

        // One field for the whole card. The key input is rendered by the panel rather than
        // being a DCA field of its own: Contao repopulates a text field from the POST, which
        // left the entered key sitting on screen after every save.
        $GLOBALS['TL_DCA']['tl_settings']['fields']['cns_panel'] = [
            'inputType' => 'cnsActivationPanel',
            // Nothing to persist: the panel reports state that lives in the signed record.
            'eval' => ['doNotSave' => true, 'tl_class' => 'clr'],
        ];

        // PaletteManipulator rather than string surgery, because the legend is shared. Contao's
        // Palette::addLegend() does nothing when the legend already exists, so this joins the
        // section another V-T.ONE package has already declared and creates it only when this is
        // the only such package installed -- either way exactly one section, never two.
        PaletteManipulator::create()
            ->addLegend(self::LEGEND, null, PaletteManipulator::POSITION_PREPEND)
            ->addField('cns_panel', self::LEGEND, PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('default', 'tl_settings')
        ;
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

        // Named operations only. Every other submit of the settings form -- Contao's own save,
        // or the panel's no-op button that catches a stray Enter -- passes straight through.
        if (!\in_array($operation, self::OPERATIONS, true)) {
            return;
        }

        // Settings is an administrator screen, but the check is made here as well: this
        // method changes entitlement, and it must not rely on the menu having hidden itself.
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'settings')) {
            throw new AccessDeniedException('Not allowed to change the product activation.');
        }

        $key = trim((string) Input::post('cns_licence_key'));
        $labels = $GLOBALS['TL_LANG']['tl_settings'] ?? [];

        try {
            if ('remove' === $operation) {
                $this->change->remove();
                $this->signals->forgetClaim();
                Message::addConfirmation($labels['cnsRemoved'] ?? 'The licence has been removed.');

                return;
            }

            if ('refresh' === $operation) {
                // An empty field means "re-check what is stored", so the browser never has to
                // send back a key the server already holds.
                $this->change->refresh($key);
                Message::addConfirmation($labels['cnsUpdated'] ?? 'The licence has been updated.');

                return;
            }

            // Only "activate" is left: the guard above admitted nothing else. Testing for it
            // again would be a condition that can never be false.
            $this->change->activate($key);
            Message::addConfirmation($labels['cnsActivated'] ?? 'The licence has been activated.');
        } catch (ActivationRefused $e) {
            Message::addError($this->refusal($e->category(), $labels));
        }
    }

    /**
     * One message for every reason the licence server gave. Distinguishing "unknown key" from
     * "wrong host" would let someone test keys against this form.
     *
     * The two exceptions are decided here without asking anyone: an empty field and an empty
     * store are facts about this screen, not about any key, so saying so tells an attacker
     * nothing and saves an administrator from a verification error that never happened.
     *
     * @param array<string, mixed> $labels
     */
    private function refusal(string $category, array $labels): string
    {
        return match ($category) {
            'key_missing' => (string) ($labels['cnsKeyMissing'] ?? 'Enter a licence key first.'),
            'nothing_to_refresh' => (string) ($labels['cnsNothingStored'] ?? 'There is no stored licence to update. Enter a licence key and activate it.'),
            default => (string) ($labels['cnsFailed'] ?? 'The licence could not be verified. Please check the key and try again.'),
        };
    }
}
