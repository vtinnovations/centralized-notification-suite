# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

## [1.1.0]

### Added

- **Licence activation (V-T.ONE).** The suite now requires an activated licence before it
  dispatches anything. Activation lives in **Contao → Settings** under "V-T.ONE Licence
  management" -- the `vtone_licence_legend` section every V-T.ONE package shares, rather than a
  section of this product's own. The card sits alongside the other packages': the product name
  above the card, the current state on its first line, then the key field and the buttons Verify
  and Activate Licence, Update Licence and Remove Licence. Status, package, licensed host,
  covered hosts, version and term are shown.

  The legend is joined through `PaletteManipulator`, whose `addLegend()` does nothing when the
  legend already exists. So the section is shared when another V-T.ONE package declared it first
  and created when this is the only one installed -- one section either way, never two.

  The key field is owned by the card rather than being a settings field, so the entered key is
  never echoed back into the page — not even directly after an activation. All three buttons are
  always offered, which is what lets an expired or host-mismatched licence be cleared; that was
  previously impossible because Remove appeared only while a licence verified.

  The product is distributed under a free, perpetual tier. "Free" refers to price only — an
  issued licence is still required.

  A licence applies to the hostnames it was issued for, matched against the domains configured
  on your root pages. Related spellings such as an apex domain and its `www` form are separate
  identities and are not covered automatically.

  Licence data is authenticated and integrity-checked before use, stored outside the public
  web root, and replaced only as a whole; a failed update leaves the previous state in place.
  Without a valid licence the suite sends nothing and Contao behaves exactly as it does
  without the bundle installed. Notifications, messages, layouts, branding and the send log
  are untouched.

  The suite also accepts licence updates delivered by the licence service at
  `/rest/api/v1/centralized-notification-suite-license-updater`. The endpoint accepts
  authenticated requests only and must remain reachable. Two usage signals are sent to the
  same service, both after the response has been delivered, so neither affects rendering or
  sending.

  New platform requirements: `ext-sodium`, `ext-curl`, `ext-json`.

### Changed

- `CacheClearService::__construct()` takes a `$memoryLimit` argument before `$environment`.
  The service is autowired by argument name, so nothing inside the bundle is affected; code
  constructing it positionally needs updating.

- `composer.json` no longer carries a hard-coded `version`. The released version comes from the
  git tag, which is what Composer and Packagist read, and keeping both in step by hand was one
  more thing that could disagree.

- **Renamed to Centralized Notification Suite.** Composer package
  `vtinnovations/contao-notification-bundle` -> `vtinnovations/centralized-notification-suite`;
  namespace `VTInnovations\ContaoNotificationBundle` -> `VTInnovations\CentralizedNotificationSuite`;
  bundle class, API service (`ContaoNotification` -> `CentralizedNotificationSuite`) and DI
  extension renamed to match. The YAML config root key is now
  `centralized_notification_suite:`, container parameters and service tags use the same prefix,
  and the backend menu group reads "Centralized Notification Suite".

  **Database identifiers are deliberately unchanged**: the `tl_notification_*` tables and the
  `notification_*` backend module keys keep their names, so existing records and per-user-group
  permissions are untouched and no schema change is proposed. The only database write is the
  cron-row cleanup below.

  Upgrading an existing install: update the `use` statements and the config root key if you
  call the service or configure the bundle from your own code. `contao:migrate` removes the
  `tl_cron_job` rows orphaned by the rename; nothing else in the database changes.

### Fixed

- **Licence withdrawal now works at all, and cannot be undone locally.** The push endpoint
  accepted only records whose `validation_status` was `valid`; a signed `revoked` or `expired`
  state was refused as "not applicable" and answered 403. The issuer could therefore grant an
  entitlement but never take one back. Authenticity and entitlement are now separate questions:
  a withdrawal is read, applied and stored like any other authoritative state, and only then
  does it grant nothing.

  Three rules changed with it. A withdrawal may name a host that is no longer in
  `license_domains` — when a licence moves from A to B, A's withdrawal carries the new set,
  which is exactly where A has just been removed from, so requiring membership made A refuse
  the one packet that ends A's entitlement. Its dates and tier are no longer measured against
  the clock or the accepted-package list, because a mismatch there would have left the site
  licensed. Both relaxations apply to withdrawals only; a record that grants is checked exactly
  as strictly as before.

- **A withdrawal survives a restored backup.** The rollback check compared against whatever was
  currently granted, and a withdrawn installation grants nothing and reported version zero — so
  dropping yesterday's `record.json` back in place re-licensed the site, with every signature on
  it intact. The store now keeps a durable watermark of the highest version ever accepted, and
  the state it arrived in. It is consulted by the endpoint, by manual activation and by the gate,
  is never lowered, and deliberately survives "Remove Licence", which would otherwise have been
  the way to clear it.

- **A spent nonce can no longer be replayed under a fresh request id.** `nonce_digest` was
  recorded but never enforced; it now carries a unique index, so the database refuses the
  second use.

- **Revocation no longer depends on being reachable.** An installation that is offline,
  firewalled, or deliberately blocking inbound requests never receives a pushed withdrawal, and
  push alone therefore made enforcement best-effort. Records may now carry the signed lease
  fields `license_refresh_required_at` and `license_grace_until`: an hourly job re-checks with
  the issuer once the deadline passes, and past the grace cutoff the gate stops granting until
  something newer arrives. Records issued without those fields are unaffected.

- **The cache rebuild after an SMTP change no longer dies on a 128M CLI memory limit.** The
  rebuild runs as a subprocess under the CLI `php.ini`, not the web one, and Contao warms every
  installed bundle's XLIFF language files in a single process. On a site with a handful of
  extensions that needs more than the 128M a stock CLI ini allows, so the warmup was killed
  inside `XliffFileLoader`, the rebuild reported failure, and the credentials the administrator
  had just saved stayed inert until someone cleared the cache by hand -- even though the test
  e-mail had gone out.

  The subprocess is now started with `-d memory_limit=-1`, which applies to that short-lived
  process only and changes nothing about the site's PHP configuration. Configurable as
  `centralized_notification_suite.mailer.memory_limit`: set a fixed size such as `512M` on a host
  that kills processes by resident size, or an empty string to inherit the CLI default.

- **File pickers work in a backend where another extension has loaded jQuery.** Contao's backend
  is MooTools, and its widgets bind themselves with inline scripts that call the global `$` --
  the file picker's is `$("ft_logo").addEvent("click", ... Backend.openModalSelector ...)`. An
  extension that loads jQuery without calling `noConflict()` takes that global over, so the call
  throws, the link is never bound, and clicking it opens the file manager as a whole page instead
  of the modal. Apply and Cancel are created by `openModalSelector`, so they are simply absent
  and no file can be chosen -- the Logo field on Branding, Image on a block and Attachments on a
  message were all affected.

  This bundle now ships one small script that hands `$` back to MooTools. It is loaded on this
  product's own screens only, does nothing unless jQuery has actually taken the global, and
  leaves `window.jQuery` in place so code written as `jQuery(...)` keeps working. It is not
  loaded on the shared Settings screen, where other products render their own fields.

  The underlying fault is in whichever extension loads jQuery that way, and fixing it there
  remains the better repair -- this only covers our own screens.

- `contao:migrate` now removes the `tl_cron_job` rows orphaned by a bundle rename. Contao keys
  its cron bookkeeping by service class name, so renaming the namespace left rows pointing at
  classes that no longer exist -- inert, but they accumulate with every rename. The migration
  covers every namespace this bundle has shipped under and is guarded by tests (including a
  mutation check) against ever matching the *current* namespace, which would delete the live
  rows.

### Added

- **Help wizard on the webhook JSON payload field** (the `?` next to it), with ready-to-paste
  bodies for Slack Block Kit, Microsoft Teams Adaptive Card and Google Chat, the per-service
  gotchas (Slack's 150/3000-character limits, the Teams work-account requirement), and the
  full list of tokens usable in a payload. Writing one from the field description alone meant
  guessing a chat service's JSON schema, which comes back as a 400 the editor cannot read. The
  examples live in the language files, so they are translatable, and are verified by a test to
  still parse as JSON once tokens are substituted.

- **Contao 5.3 LTS support.** The requirement drops from `contao/core-bundle ^5.7` to `^5.3`
  and from PHP 8.3 to PHP 8.1, and `symfony/mailer`/`symfony/mime` widen to `^6.4 || ^7.0`,
  which is what the 5.3 line pins. Contao 5.7 introduced the `[label, preview, state]` record
  label; 5.3 to 5.6 call the same label callback but concatenate its return value straight
  into the row, so an array would have reached the page as the literal string `Array` plus a
  conversion warning on every block. On those cores the block cards now come from a child
  record callback — which is also the API their backend theme lays out as grid children — and
  the label callback returns a plain string, which is what the record picker and the undo
  preview expect. Contao 5.7 is untouched: it never gets a child record callback, because it
  gives one precedence over the record label and would otherwise take a deprecated path that
  also loses the drag handle.

- **Block-based message bodies** (`tl_notification_block`). A message can now be composed of
  blocks that are dragged to reorder in the backend, instead of hand-written HTML: heading,
  text, list, detail table, generated content, button, image, image-with-text, divider and a
  custom-HTML escape hatch. Each block declares its own fields and palette, so a third-party
  block needs no DCA file — the same pattern the gateways use.
- **Ready-made emails** (`notification:install-emails`): transactional, newsletter and
  announcement starters, each installed as a notification with a design layout and a message
  already filled with blocks, so an editor changes the wording and pictures rather than
  starting from an empty field. `--list` previews, `--force` replaces.
- **`tl_notification_message.body_mode`** selecting blocks or own HTML. Existing messages
  default to `html` and render exactly as before; switching back and forth never discards
  either side's content.
- Token help wizard on the block fields, and `##tokens##` usable in every block field
  including detail-table labels and values.
- Designs now emit a complete document with `<head>`, `charset`, `viewport` and
  `color-scheme`. Previously the output began with a `<style>` block ahead of the doctype,
  which put clients into quirks mode.

- **Send log** (`tl_notification_log`, backend module *Send log*) recording every attempt with its
  recipients, rendered body, status and error, with one-click resend and an hourly automatic
  retry of failed messages. Delivery status reflects what the mail transport actually did, not
  merely that the message was queued.
- **Email layouts** (`tl_notification_template`, backend module *Email layouts*): reusable branded
  frames, either header/footer or a complete pasted HTML document with `##message_body##`.
- **Automatic CSS inlining** of layout CSS and of `<style>` blocks inside pasted markup, with
  `@media` rules preserved as a style block.
- **Automatic plain-text generation** from the HTML body, including link targets.
- **Image embedding** (`cid:`) for images hosted on the site.
- **Preview** and **test send** for every message, from the backend, with sample or hand-entered
  token values. A test send never reaches the message's real recipients.
- **Type-aware token reference** in the help wizard of the subject and body fields.
- **Notification types** (`tl_notification.type`), so each trigger only offers the
  notifications it can actually fill.
- **Convenience tokens** for forms: `##all_fields##`, `##all_fields_filled##`,
  `##all_fields_html##`, `##all_fields_filled_html##`, `##uploads##`, `##uploads_html##`,
  `##label_<field>##`.
- **Universal tokens** available to every notification: `##admin_email##`, `##date##`,
  `##time##`, `##datim##`, `##host##`, `##url##`, `##page_id##`, `##page_title##`, `##page_url##`.
- **Webhook gateway** for Slack, Teams, Discord, Zapier, n8n, Make and any REST endpoint, with
  JSON-escaped token substitution and a mandatory timeout.
- **File gateway** writing messages to disk instead of sending them, for local development.
- **Member, comment and newsletter triggers** (registration, activation, personal data, password
  change, account closure, new comment, newsletter subscribe/unsubscribe).
- **Notification Center importer** (`notification:import-nc`) with token-name translation, a
  dry-run mode and a report of anything it could not map.
- `notification:send` and `notification:install-layouts` console commands.
- `PreSendEvent` (cancellable, message mutable) and `PostSendEvent` extension points.
- Per-message CC, BCC, Reply-To and priority; gateway-level default reply address.
- Automatic alias generation for notifications.
- Insert tag support (`{{...}}`) in subjects, bodies and address fields.
- German translations.
- Bundle configuration under `centralized_notification_suite.log`.

### Changed

- **Backend modules moved into their own `Notify` group**, out of `System`, and renamed to
  editor-facing labels (*Notifications*, *Email layouts*, *Senders*, *Send log*). Previously
  they sat under System with the same names Notification Center uses, making the two
  indistinguishable when both were installed.
- **Delivery failures no longer propagate.** Each message is sent inside its own try/catch, so a
  broken mail server can no longer turn a visitor's form submission into a 500 after their data
  was already stored, and one failing message no longer aborts the rest.
- **Token values are escaped in HTML bodies** and their line breaks converted to `<br>`. Tokens
  whose name ends in `_html` are inserted as markup. Previously a submitted `&` or `<b>` went
  into the HTML part raw.
- `GatewayInterface::send()` now receives a `RenderedMessage` instead of an array, and gateways
  declare their own backend fields and palette via `getConfigFields()` / `getPalette()` — the
  gateway type selector shows only the fields of the selected type.
- `CentralizedNotificationSuite::send()` returns a `SendResult` instead of an array of booleans, and takes
  a `$source` argument.
- The plain-text field uses plain-text highlighting instead of PHP syntax highlighting, and is
  no longer mandatory.
- Message gateway options list only published gateways, ordered by title.
- The message list marks messages whose sender is missing, unpublished or has no installed
  gateway type.

### Fixed

- **Contao 5.3 to 5.6: the preview button was invisible.** Core's `preview.svg` is stroked
  `#fff` on those versions, because core only ever used it on the dark preview toolbar, and
  there is no `preview--dark.svg` to fall back to; 5.7 restroked it `#222`. The button was
  present and working the whole time — only unseeable. It is now drawn inline with
  `currentColor`, so it follows the theme's text colour on every supported core in both light
  and dark mode, and needs no `assets:install`. The test-send button still uses core's
  `resend.svg`, which is opaque in every version.
- **The recipient field is no longer unconditionally mandatory.** Only some gateways deliver
  to it — the new `GatewayInterface::addressesRecipients()` says which — so a webhook or file
  message no longer has to invent an e-mail address to pass validation. Making it conditional
  on the gateway was not an option either: a new record has no gateway chosen yet, so the
  field would have been required before the answer was knowable. A message that does need an
  address and has none is now flagged in the list with the same warning marker already used
  for a missing or unpublished sender.

- **Contao 5.3 to 5.6: opening a notification's messages was a fatal error**
  (`Too few arguments to function MessageListener::formatLabel(), 3 passed and exactly 4
  expected`), which took down the whole backend module rather than one label. Those cores have
  a dedicated MODE_PARENT branch that passes only `($row, $label, $dc)` to a label callback,
  where 5.7 has a single generic branch passing `$args` as well. Everything after `$label` is
  now optional on every label callback in the bundle.
- The message label also returned the `$args` column array, which Contao 5.7 reduces to its
  first element — so the list showed only the language and dropped the subject, and on 5.3 it
  would have rendered the literal string `Array`. It now returns the fully formatted label,
  which both parent views render correctly.
- The test-send form scanned the raw `html` column for `##tokens##`, so tokens inside block
  records — and in `cc`/`bcc`, which were never scanned at all — were never asked for.
- `X-Simple-Notify-Ref` mail header renamed to `X-Notification-Ref` after the bundle rename.
- The rename migration failed to remove stale `tl_cron_job` rows: a PHP namespace contains
  backslashes, and a backslash is `LIKE`'s own escape character, so the pattern matched
  nothing.
- **Embedded images made the whole message fail to send.** `ImageEmbedder` generated a bare
  hash as the content ID, but Symfony's `DataPart::setContentId()` rejects any ID without an
  `@`, so a message with *Embed images* enabled and a resolvable local image threw inside the
  gateway and was recorded as failed — nothing was delivered, attachments included. Content IDs
  are now addr-specs (`sn<hash>@notification`) and the `src` references them verbatim, which is
  also what lets Symfony pair the `cid:` reference with its part instead of demoting the image
  to a plain attachment.
- **A cancelled message was logged as failed rather than skipped.** `PostSendEvent` only
  carried a boolean, so the send log inferred "failed" whenever a gateway type was present.
  Because the retry cron re-attempts failed entries, a `PreSendEvent` listener that
  deliberately cancelled a message (a staging guard, say) had that message re-sent every hour
  until it exhausted its attempts. The event now carries an explicit `status`.
- **A dropped attachment was invisible.** An unreadable attachment or an invalid address was
  written to the application log and nothing else, so the send log showed a clean "sent" for a
  mail that arrived without the file the recipient was expecting. Both are now recorded on the
  message as warnings and shown against the log entry, whatever its status.
- Invalid recipient addresses are skipped and logged instead of aborting the whole message.
- A message with neither a text nor an HTML part is reported as a configuration error rather
  than handed to the transport.
- Recipient, CC and BCC lists are trimmed and de-duplicated.
- The help wizard on the HTML field no longer opens empty.

### Migration notes

- `contao:migrate` adds the new tables and columns. A migration marks notifications already
  referenced by a form as type `form`, so they keep appearing in the form generator's picker.
- Custom gateways must implement `getConfigFields()`, `getPalette()` and `isAsynchronous()`, and
  accept a `RenderedMessage` in `send()`. Extending `AbstractGateway` supplies sensible defaults
  for the first three.
- Callers relying on `send()` returning `array<int, bool>` should use `SendResult::toArray()`.
- `PostSendEvent::$successful` (bool) is replaced by `$status` (a `SendResult::STATUS_*` value);
  use `isSuccessful()` for the old meaning, or `wasSkipped()` to tell a deliberate skip from a
  delivery failure.

## [1.0.0]

- Initial release: notifications, messages and gateways with token replacement, a form
  generator trigger and file attachments.
