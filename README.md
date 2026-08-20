# Simple Notify for Contao 5

Transactional notifications for Contao 5.7+: form mails, member mails, comment and newsletter
mails, Slack/webhook pings — configured in the backend, triggered from a single service call.

It covers the same ground as [Notification Center](https://github.com/terminal42/contao-notification_center),
with four things that bundle does not give you:

| | Simple Notify | Notification Center |
|---|---|---|
| **Did it actually arrive?** | Send log with real delivery status, error text, and one-click resend | Read `var/logs/` and hope |
| **Testing a notification** | Preview + test send with your own token values | Trigger the real event |
| **Pasted HTML designs** | `<style>` CSS is inlined automatically, so Outlook and Gmail render it | Inline it yourself, by hand |
| **Shared branding** | Reusable layouts with a `##message_body##` slot | Paste header/footer into every message |

Plus: automatic plain-text generation, `cid:` image embedding, per-message CC/BCC/Reply-To,
automatic retry of failed sends, a type-aware token reference in the backend, and a
one-command importer for existing Notification Center setups.

## Installation

```bash
composer require vtinnovations/simple-notify-bundle
vendor/bin/contao-console contao:migrate
vendor/bin/contao-console simple-notify:install-layouts   # optional starter layouts
```

The backend gets a **Notify** group with four modules: Notifications, Email layouts, Senders,
Send log.

## Getting started

1. **Senders** → create one, type *Email*, fill in the sender address.
2. **Notifications** → create one. Pick what triggers it — the type decides which tokens you
   get offered and which triggers will list it. The alias is generated from the title.
3. Open its messages, add one, choose the sender and language, write subject and body.
4. Hit **Preview** to see the finished mail, or **Send a test** to mail it to yourself.
5. Wire it up: a form's *Notifications* field, a member module's *Notifications* field, or
   `SimpleNotifyCenter::send()` in your own code.

## Tokens

`##token##` in any subject, body or address field. Contao insert tags (`{{env::url}}`,
`{{link_url::42}}`) work too, and `{if}` / `{else}` / `{endif}` blocks are supported by
Contao's simple token parser.

The **help button** on the subject and body fields lists every token available to that
notification's type. Available everywhere:

`##admin_email##` `##date##` `##time##` `##datim##` `##host##` `##url##` `##page_id##` `##page_title##` `##page_url##`

Form notifications additionally get every submitted field by its name, plus:

| Token | Contains |
|---|---|
| `##all_fields##` | Every field as `Label: value` lines |
| `##all_fields_filled##` | The same, skipping empty fields |
| `##all_fields_html##` | Every field as a styled table |
| `##all_fields_filled_html##` | The same table, without empty fields |
| `##uploads##` / `##uploads_html##` | Names of uploaded files |
| `##label_<field>##` | A field's label |

Member, comment and newsletter notifications get `##member_*##`, `##comment_*##` and
`##newsletter_*##` respectively — see the help button for the full list.

### Escaping

In an **HTML** body, token values are HTML-escaped and their line breaks become `<br>`, so a
visitor cannot inject markup or break your layout. Tokens whose name **ends in `_html`** are
inserted as markup instead — that is how `##all_fields_html##` works, and the convention your
own HTML-producing tokens should follow. In the plain-text body nothing is escaped.

## Email layouts

A layout is the branded frame your messages share. Two ways to write one:

- **Header and footer** around the body — good for a logo bar and a legal footer.
- **Complete HTML document** with `##message_body##` where the content goes — paste whatever
  your designer sent.

Either way, the CSS you put in the layout's *CSS* field (and any `<style>` block inside your
markup) is **written into `style` attributes before sending**. That is what makes a pasted
design survive Outlook's Word-based renderer and Gmail's stripping of `<style>`. `@media`
rules cannot be inlined, so they are kept as a `<style>` block for the clients that honour
them — your responsive rules keep working.

Per message you can also:

- **Generate the plain text from the HTML** — links become `label (url)`. Happens automatically
  when the plain-text field is empty, so an HTML-only message still ships a text alternative.
- **Embed images** — `<img src>` pointing at files on this site becomes a `cid:` attachment, so
  images show without the recipient allowing remote content.

## Send log

Every attempt is recorded: recipients, rendered subject, both body parts, status, error.

Status means what it says. For email, `Queued` is where a message sits after Contao hands it to
its Messenger queue; it becomes **Delivered** or **Failed** only once a worker has actually
talked to the mail server. Getting that right is the point — "sent" the moment `send()` returns
is not an answer to "did the customer get it?".

Failed entries can be **resent** (the stored body is replayed byte-for-byte) and are retried
automatically once an hour.

```yaml
# config/config.yaml
vt_innovations_simple_notify:
    log:
        enabled: true
        store_body: true        # needed for resend; turn off to keep no personal data
        retention_days: 90      # 0 keeps entries forever
        max_attempts: 3
        retry_failed: true
```

## Senders (gateways)

- **Email** — Symfony Mailer, optionally through a named transport.
- **Webhook / JSON** — any HTTP endpoint. The default payload is `{"text": "..."}`, which Slack,
  Mattermost and Discord accept as-is; Zapier, n8n, Make and plain REST APIs take a custom
  payload. Token values are JSON-escaped, so a quote in a form field cannot corrupt the body.
- **File** — writes the message to `var/simple-notify-mail` instead of sending. Build and check
  notifications locally with no mail server and no chance of reaching a real recipient.

### Writing your own

Implement `GatewayInterface` (or extend `AbstractGateway`) and it is picked up automatically —
including its backend fields, which the gateway declares itself:

```php
class SmsGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'sms';
    }

    public function getConfigFields(): array
    {
        return [
            'sms_api_key' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
                'sql' => "varchar(255) NOT NULL default ''",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{sms_legend},sms_api_key';
    }

    public function send(RenderedMessage $message, array $gatewayConfig): bool
    {
        // Throw on failure -- it is caught, logged and shown in the send log
    }
}
```

`contao:migrate` creates the columns; no DCA file needed.

## Triggering from code

```php
use VTInnovations\SimpleNotifyBundle\SimpleNotifyCenter;

public function __construct(private readonly SimpleNotifyCenter $notify) {}

$result = $this->notify->send('order-confirmation', [
    'customer_name' => $order->name,
    'order_total'   => $order->formattedTotal,
], 'de');

$result->isSuccessful();   // every message accepted by its gateway
$result->hasFailures();
$result->getErrors();      // message id => reason
```

**Delivery failures never throw.** A notification is usually triggered after something has
already been committed — an order stored, an account created — so letting an SMTP timeout bubble
up would show the visitor an error for an action that in fact succeeded. Failures are caught per
message, recorded, and the remaining messages are still attempted. An unknown alias *does*
throw (`SimpleNotifyException`), because that is a bug in your code.

From the command line:

```bash
vendor/bin/contao-console simple-notify:send order-confirmation -t customer_name=Jane --dry-run
```

## Hooking into the send

```php
#[AsEventListener]
public function __invoke(PreSendEvent $event): void
{
    if ('production' !== $this->env) {
        $event->cancel('Not a production environment');   // logged as "skipped"
    }

    $event->message->bcc = 'archive@example.com';         // content is mutable
}
```

`PostSendEvent` carries the outcome and the throwable, if any.

## Migrating from Notification Center

```bash
vendor/bin/contao-console simple-notify:import-nc --dry-run
vendor/bin/contao-console simple-notify:import-nc
```

Reads the `tl_nc_*` tables and recreates notifications, messages and senders. Notification
Center itself is untouched, so both can run side by side until you are satisfied.

Token names are translated as they are imported — `##form_email##` → `##email##`,
`##raw_data##` → `##all_fields##`, `##env_host##` → `##host##`, and so on. Anything the importer
does not recognise is left exactly as it is and listed at the end for you to check, which is why
you should always start with `--dry-run`.

Gateway types with no equivalent installed here (SMS, push) are reported rather than
half-imported; install or write a matching gateway first, then re-run. Re-running is safe:
already-imported notifications are skipped, and `--force` replaces them instead of duplicating.

## Requirements

PHP 8.3+, Contao 5.7+.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## License

MIT.
