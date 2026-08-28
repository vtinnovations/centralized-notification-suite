# Centralized Notification Suite for Contao 5

*[Deutsche Version](README.md) (default language)*

Transactional notifications for Contao 5.3 LTS and 5.7+: form, member, comment and newsletter
mails as well as Slack or webhook messages — configured in the backend and triggered from a
single service call.

## Contents

- [Overview](#overview)
- [Status](#status)
- [Supported versions](#supported-versions)
- [System requirements](#system-requirements)
- [Installation](#installation)
- [Licensing](#licensing)
- [Backend modules](#backend-modules)
- [Getting started](#getting-started)
- [Tokens](#tokens)
- [Message content](#message-content)
- [Email layouts and branding](#email-layouts-and-branding)
- [Senders (gateways)](#senders-gateways)
- [Send log](#send-log)
- [Triggering from your own code](#triggering-from-your-own-code)
- [Permissions](#permissions)
- [Feature status](#feature-status)
- [Security model](#security-model)
- [Operational safety](#operational-safety)
- [Runtime directories](#runtime-directories)
- [External communication](#external-communication)
- [Logging](#logging)
- [Deployment](#deployment)
- [Clearing the cache](#clearing-the-cache)
- [Tests](#tests)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [Licence and copyright](#licence-and-copyright)

## Overview

The Centralized Notification Suite brings all of a Contao installation's notification sending
together in one place. A *notification* describes an occasion, its *messages* describe the
actual mailings per language, and a *sender* describes the route used for delivery.

Compared with similar solutions the suite adds four things:

| | Centralized Notification Suite | Typical alternatives |
|---|---|---|
| **Did it actually arrive?** | Send log with real delivery status, error text and one-click resend | A look in `var/logs/` |
| **Testing a notification** | Preview and test send with your own token values | Trigger the real event |
| **Pasted HTML designs** | CSS is written into `style` attributes automatically, so Outlook and Gmail render correctly | Inline it by hand |
| **Shared branding** | Reusable layouts with a placeholder for the message content | Copy header and footer into every message |

In addition: automatic plain-text alternative, embedded images, per-message CC/BCC/Reply-To,
automatic retry of failed sends, a type-aware token reference in the backend, and an import
command for existing Notification Center installations.

## Status

Ready for production use. Every feature described in this document is implemented and covered
by the bundled test suite. The suite requires an activated licence; see
[Licensing](#licensing).

## Supported versions

| Component | Supported |
|---|---|
| Contao | 5.3 LTS through 5.7 |
| PHP | 8.1 or newer |
| Symfony Mailer / Mime | 6.4 or 7.x (supplied by Contao) |

On Contao 5.3 to 5.6 the preview cards of the content blocks are produced through an older
Contao interface, because those cores do not yet have a record-label contract for parent
views. Appearance, drag-and-drop ordering and every other function behave identically; the
appropriate interface is selected from the running Contao version automatically.

## System requirements

- PHP 8.1 or newer with the `curl`, `json` and `sodium` extensions
- Contao 5.3 or newer with a working database
- A reachable mail transport (SMTP or any other transport supported by Symfony Mailer)
- Outbound HTTPS for licence operations and for webhook senders
- Write access to the installation's `var/` directory

## Installation

```bash
composer require vtinnovations/centralized-notification-suite
vendor/bin/contao-console contao:migrate
```

Ready-made layouts and example emails can optionally be installed:

```bash
vendor/bin/contao-console notification:install-layouts
vendor/bin/contao-console notification:install-emails
```

Then activate the licence in the backend (see below). Without a valid licence the suite sends
nothing.

### Filesystem permissions

The web server user needs write access to `var/`. The suite stores its private data there, and
— when the file sender is used — the messages it writes out. These directories are outside the
public web root and must not be served by the web server.

## Licensing

The suite is distributed as a free product with a perpetual licence. "Free" refers to price
only: a licence issued by V-T.ONE must be activated before any message is sent.

### Management

Licence management is located under **Contao → Settings** in the section
**Centralized Notification Suite Licence management**. It offers:

- **Enter and save the licence key** — activates the installation.
- **Update licence** — reconciles the stored state with the licence service.
- **Remove licence** — returns the installation to the unlicensed state immediately.

Status, package, licensed host, covered hosts, version and term are displayed. The key itself
is not rendered back into the interface after saving.

### States

| State | Effect |
|---|---|
| No licence activated | Nothing is sent; Contao behaves as it would without the package |
| Active perpetual licence | Full functionality |
| Licence not verifiable, or not valid for this website | Same as "no licence"; existing content is left unchanged |

A licence applies to the hostnames it was issued for. The authoritative source is the domains
configured on the website's root pages. Different hostnames are different identities —
`example.com` and `www.example.com` do not cover one another automatically.

If verification fails, only sending is disabled. Notifications, messages, layouts, branding and
the send log are retained in full.

## Backend modules

The suite adds a **Centralized Notification Suite** group to the backend navigation with six
modules, in this order:

| Module | Purpose |
|---|---|
| **Notifications** | Manage notifications and the messages they send |
| **Email layouts** | Manage the reusable HTML layouts that frame your messages |
| **Branding** | Logo, brand colour and company details used by every design |
| **Senders** | Manage the senders (gateways) that deliver notification messages |
| **Send log** | See what was sent, why something failed, and send it again |
| **SMTP Configuration** | SMTP credentials the site sends through |

## Getting started

1. **Senders** → create one of type *Email* and fill in the sender address.
2. **Notifications** → create one. The type you choose decides which tokens are offered and
   where the notification can be selected. The alias is generated from the title.
3. Open the notification's messages, add one, choose the sender and language, and write the
   subject and body.
4. Use **Preview** to see the finished result, or **Test send** to mail a sample to yourself.
5. Wire it up: through a form's *Notifications* field, the same field on a member module, or
   from your own code.

## Tokens

`##token##` is available in the subject, the body and the address fields. Contao insert tags
(`{{env::url}}`, `{{link_url::42}}`) work as well, as do `{if}` / `{else}` / `{endif}`.

The **help button** on the content fields lists every token available for that notification's
type. Available everywhere:

`##admin_email##` `##date##` `##time##` `##datim##` `##host##` `##url##` `##page_id##`
`##page_title##` `##page_url##`

Form notifications additionally receive every submitted field under its own name, plus:

| Token | Contains |
|---|---|
| `##all_fields##` | Every field as `Label: value` |
| `##all_fields_filled##` | The same, without empty fields |
| `##all_fields_html##` | Every field as a formatted table |
| `##all_fields_filled_html##` | The same table, without empty fields |
| `##uploads##` / `##uploads_html##` | Names of uploaded files |
| `##label_<field>##` | A field's label |

Member, comment and newsletter notifications receive `##member_*##`, `##comment_*##` and
`##newsletter_*##` respectively.

### Escaping

In an **HTML** body, token values are escaped and their line breaks become `<br>`. Form input
can therefore neither inject markup nor break the layout. Tokens whose name ends in `_html` are
deliberately inserted as markup — that is how `##all_fields_html##` works. Nothing is escaped
in the plain-text body.

## Message content

A message body is built either from **blocks** or from **your own HTML**; the *Body* setting on
the message decides. Existing messages remain unchanged on own-HTML.

In block mode the message gains a child list of **Blocks**. Blocks are reordered by
drag-and-drop and each shows a preview card. Available block types:

| Type | Group |
|---|---|
| Heading, paragraph, list, table, token | Text |
| Image, teaser | Media |
| Button | Action |
| Divider | Layout |
| Custom HTML | Advanced |

### Ready-made emails

`notification:install-emails` creates three fully composed example emails (transactional,
newsletter, announcement) that can be edited as a starting point.

## Email layouts and branding

A layout is the styled frame that several messages share. Three modes:

- **Design** — one of 20 bundled designs, which takes the logo, brand colour and company
  details from the **Branding** module.
- **Header and footer** around the content.
- **Complete HTML document** with a placeholder for the content.

A layout's CSS is written into `style` attributes before sending. That is exactly what makes a
pasted design look correct in Outlook and Gmail. `@media` rules cannot be inlined and are kept
as a `<style>` block so responsive rules keep working.

Per message you can additionally:

- **Generate the plain text from the HTML** — links become `label (url)`. Happens automatically
  when the text field is empty.
- **Embed images** — images stored on this website are embedded as attachments and display
  without loading remote content.

## Senders (gateways)

- **Email** — through Symfony Mailer, optionally via a named transport.
- **Webhook / JSON** — any HTTPS endpoint. Without a custom payload a simple text format is
  sent, which Slack, Mattermost and Discord accept. For Microsoft Teams, Google Chat and other
  services a help wizard provides ready-made templates. Token values are inserted JSON-safely.
- **File** — writes the message to `var/notification-mail` instead of sending it. This makes it
  possible to check notifications without a mail server and without any risk to real
  recipients.

Your own senders can be added by implementing `GatewayInterface` or extending
`AbstractGateway`. The sender declares its own backend fields; `contao:migrate` creates the
corresponding columns.

## Send log

Every attempt is recorded: recipients, rendered subject, both body parts, status and error.

The status means what it says. For email, **Queued** means the message has been handed to
Contao's Messenger queue; it becomes **Sent** or **Failed** only once a worker has actually
talked to the mail server.

Failed entries can be resent and are retried automatically once an hour.

```yaml
# config/config.yaml
centralized_notification_suite:
    log:
        enabled: true
        store_body: true        # needed for resending; turn off to store no personal data
        retention_days: 90      # 0 keeps entries forever
        max_attempts: 3
        retry_failed: true
```

## Triggering from your own code

```php
use VTInnovations\CentralizedNotificationSuite\CentralizedNotificationSuite;

public function __construct(private readonly CentralizedNotificationSuite $notify) {}

$result = $this->notify->send('order-confirmation', [
    'customer_name' => $order->name,
    'order_total'   => $order->formattedTotal,
], 'de');

$result->isSuccessful();
$result->hasFailures();
$result->getErrors();
```

**Delivery failures do not throw.** A notification is usually triggered after something has
already been stored. If an SMTP timeout were passed through, the visitor would see an error for
an operation that in fact succeeded. Failures are caught per message and logged; the remaining
messages are still sent. An unknown alias does throw, because that is a programming error.

From the command line:

```bash
vendor/bin/contao-console notification:send order-confirmation -t customer_name=Jane --dry-run
```

The `PreSendEvent` and `PostSendEvent` events allow a send to be cancelled, modified or
evaluated.

### Migrating from Notification Center

```bash
vendor/bin/contao-console notification:import-nc --dry-run
vendor/bin/contao-console notification:import-nc
```

Reads the existing tables and recreates notifications, messages and senders. The existing
installation is left untouched and both can run side by side. Token names are translated;
anything unknown is preserved and listed at the end — which is why you should always start with
`--dry-run`.

## Permissions

- The six backend modules follow Contao's normal permission handling and can be granted per
  user group.
- Licence management lives in **Settings** and is therefore available only to users who may
  access that module. Every licence-changing action verifies this permission again on the
  server.
- Preview and test send are tied to the relevant backend module.

## Feature status

| Feature | Status |
|---|---|
| Notifications, messages, multilingual sending | Available |
| Form, member, comment and newsletter triggers | Available |
| Triggering from code and from the command line | Available |
| Tokens including form fields and the help wizard | Available |
| Block-based message content | Available |
| 20 designs, branding, custom layouts | Available |
| CSS inlining and embedded images | Available |
| Automatic plain-text alternative | Available |
| Attachments from form uploads and the file manager | Available |
| Preview and test send | Available |
| Send log, resend, automatic retry | Available |
| Email, webhook/JSON and file senders | Available |
| SMTP configuration in the backend | Available |
| Import from Notification Center | Available |
| Sending without an activated licence | Not available |
| Frontend output produced by the package | Not applicable |

## Security model

- **Access control** — every backend module is subject to Contao's permission handling.
  Licence-changing actions verify the permission again on the server and run through Contao's
  own forms, so the request token applies.
- **Server-side enforcement** — entitlement is checked at several independent points along the
  sending path, not in the interface alone.
- **Escaping** — token values are escaped in HTML bodies. The exception is marked explicitly by
  the `_html` naming convention.
- **Authenticity and integrity** — licence data is authenticated and checked against
  modification before it is used. A file altered after the fact is rejected.
- **Private storage** — licence data is kept outside the public web root with restrictive file
  permissions.
- **Trusted connections** — outbound licence communication uses HTTPS only, to a service fixed
  in the program code, with certificate verification and without redirects.
- **Safe failure** — if a check fails, the protected function is disabled. There is no fallback
  path that grants additional rights on error.
- **Secrets** — full keys and authentication material appear neither in browser output nor in
  ordinary logs. This is verified automatically by tests.

These statements describe implemented controls. No protection mechanism is impossible to
circumvent, and the suite makes no such claim.

## Operational safety

- **Guaranteed:** an outage of the licence service does not remove the licence from an already
  activated installation. A failed write leaves the previous state unchanged. A state that was
  only half written is never used.
- **Guaranteed:** delivery failures are caught per message; the remaining messages are still
  sent.
- **Environment-dependent:** whether an email is actually delivered depends on the mail server
  and on the Messenger queue being processed. Without a running worker, messages stay queued.
- **Best effort:** the hourly retry re-attempts failed messages until the configured number of
  attempts is reached.

## Runtime directories

| Directory | Contents |
|---|---|
| `var/` | The suite's private data, including the licence state |
| `var/notification-mail` | Messages written out by the file sender |

Both are outside the public web root and belong in your backups, not in version control.

## External communication

Without any explicit configuration, the suite makes the following outbound connections:

| Occasion | Destination |
|---|---|
| Activating, updating or verifying a licence | The fixed V-T.ONE licence service, over HTTPS only |
| Usage signal | The same service; sent after the response has been delivered, and affecting neither rendering nor sending |
| Webhook sender | Only the address configured in that sender |
| Email sending | The configured mail server |

The suite also accepts one inbound request: the licence service can deliver an updated licence
state to `/rest/api/v1/centralized-notification-suite-license-updater`. The endpoint accepts
authenticated requests only and must remain reachable for updates to arrive.

## Logging

Operational messages go to the Contao system log. What is recorded is the operation, the result
and — when sending — the message concerned.

Full licence keys, authentication material, transmitted data packets and their check values are
not logged. The send log stores the subject and body only when `store_body` is enabled; on
installations that must not retain personal data this option should be switched off.

## Deployment

```bash
composer install --no-dev --optimize-autoloader
vendor/bin/contao-console contao:migrate --no-interaction
vendor/bin/contao-console cache:clear --env=prod
vendor/bin/contao-console cache:warmup --env=prod
```

Points to note:

- `var/` must be writable by the web server user.
- A licence is bound to hostnames. Test, staging and production systems each need a licence
  valid for their own hostname.
- Outbound HTTPS and the endpoint named above must not be blocked.
- For email sending, a Messenger worker must run or Contao's web worker must be active.

## Clearing the cache

```bash
vendor/bin/contao-console cache:clear --env=prod
vendor/bin/contao-console cache:clear --env=dev
```

After changes to the SMTP configuration in the backend, the suite refreshes the cache itself.

## Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
composer release:check
```

The test suite runs without a Contao bootstrap and without network access.
`composer release:check` verifies before packaging that the shipped package is capable of
checking licence data at all, and fails the build otherwise.

## Troubleshooting

| Observation | Cause and remedy |
|---|---|
| "Nothing was sent: this installation is not activated." | No valid licence. Activate it under Settings. |
| The licence cannot be activated | Check that a domain is configured on the root page and that outbound HTTPS is possible. The key must have been issued for this hostname. |
| Emails stay "Queued" | No Messenger worker is running. Process the queue or set up a worker. |
| A form sends nothing | The notification must be selected in the form, and the message must be published and present for the language. |
| A design renders incorrectly in Outlook | Check that the layout is published and assigned to the message. |
| A webhook reports an error | The target system's error text is shown in the send log. |

## Known limitations

- Sending requires an activated licence. Without one, nothing is delivered.
- A licence applies only to the hostnames it was issued for; related spellings are not covered
  automatically.
- Versioning covers the message itself, not its content blocks. Restoring an earlier message
  version does not restore its blocks.
- The file sender is intended for development and verification and does not deliver.
- For the webhook sender the suite supplies templates; the format is determined by the target
  service.

## Licence and copyright

Software licence: proprietary (`proprietary`, see `composer.json`). All rights reserved.
Distribution, modification and redistribution are permitted only under the agreement concluded
with V&T Innovations.

Copyright: V&T Innovations Team, [https://www.v-t.one](https://www.v-t.one).

Operating the suite additionally requires an activated product licence; see
[Licensing](#licensing).

Support and issue reports:
[github.com/vtinnovations/centralized-notification-suite](https://github.com/vtinnovations/centralized-notification-suite/issues)

---

*[Deutsche Version](README.md) (default language)*
