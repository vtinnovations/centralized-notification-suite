# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- **Send log** (`tl_simple_log`, backend module *Send log*) recording every attempt with its
  recipients, rendered body, status and error, with one-click resend and an hourly automatic
  retry of failed messages. Delivery status reflects what the mail transport actually did, not
  merely that the message was queued.
- **Email layouts** (`tl_simple_template`, backend module *Email layouts*): reusable branded
  frames, either header/footer or a complete pasted HTML document with `##message_body##`.
- **Automatic CSS inlining** of layout CSS and of `<style>` blocks inside pasted markup, with
  `@media` rules preserved as a style block.
- **Automatic plain-text generation** from the HTML body, including link targets.
- **Image embedding** (`cid:`) for images hosted on the site.
- **Preview** and **test send** for every message, from the backend, with sample or hand-entered
  token values. A test send never reaches the message's real recipients.
- **Type-aware token reference** in the help wizard of the subject and body fields.
- **Notification types** (`tl_simple_notification.type`), so each trigger only offers the
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
- **Notification Center importer** (`simple-notify:import-nc`) with token-name translation, a
  dry-run mode and a report of anything it could not map.
- `simple-notify:send` and `simple-notify:install-layouts` console commands.
- `PreSendEvent` (cancellable, message mutable) and `PostSendEvent` extension points.
- Per-message CC, BCC, Reply-To and priority; gateway-level default reply address.
- Automatic alias generation for notifications.
- Insert tag support (`{{...}}`) in subjects, bodies and address fields.
- German translations.
- Bundle configuration under `vt_innovations_simple_notify.log`.

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
- `SimpleNotifyCenter::send()` returns a `SendResult` instead of an array of booleans, and takes
  a `$source` argument.
- The plain-text field uses plain-text highlighting instead of PHP syntax highlighting, and is
  no longer mandatory.
- Message gateway options list only published gateways, ordered by title.
- The message list marks messages whose sender is missing, unpublished or has no installed
  gateway type.

### Fixed

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

## [1.0.0]

- Initial release: notifications, messages and gateways with token replacement, a form
  generator trigger and file attachments.
