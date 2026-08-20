<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Gateway;

use Contao\StringUtil;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use VTInnovations\SimpleNotifyBundle\Message\RenderedMessage;

/**
 * Posts a notification to an HTTP endpoint.
 *
 * One gateway covers Slack, Teams, Discord, Zapier, n8n, Make and any REST API, because
 * what differs between them is the payload shape -- and that is configuration, not code.
 * The payload is a template with ##tokens##, each value JSON-escaped on substitution so a
 * quote or newline in a submitted form field cannot produce a malformed body.
 */
class WebhookGateway extends AbstractGateway
{
    public const NAME = 'webhook';

    /**
     * Slack, Mattermost and Discord all accept a bare {"text": ...}, which makes the most
     * common integration work with no payload configuration at all.
     */
    private const DEFAULT_PAYLOAD = '{"text": "##subject##\n\n##text##"}';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            'webhook_url' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 1024, 'tl_class' => 'long clr'],
                'sql' => "varchar(1024) NOT NULL default ''",
            ],
            'webhook_method' => [
                'exclude' => true,
                'inputType' => 'select',
                'options' => ['POST', 'PUT', 'PATCH'],
                'eval' => ['tl_class' => 'w50'],
                'sql' => "varchar(8) NOT NULL default 'POST'",
            ],
            'webhook_timeout' => [
                'exclude' => true,
                'inputType' => 'text',
                'eval' => ['rgxp' => 'natural', 'maxlength' => 3, 'tl_class' => 'w50'],
                'sql' => "smallint(5) unsigned NOT NULL default 10",
            ],
            'webhook_headers' => [
                'exclude' => true,
                'inputType' => 'keyValueWizard',
                'eval' => ['tl_class' => 'clr'],
                'sql' => "text NULL",
            ],
            'webhook_payload' => [
                'exclude' => true,
                'inputType' => 'textarea',
                'eval' => ['preserveTags' => true, 'decodeEntities' => true, 'class' => 'monospace', 'rte' => 'ace|json', 'tl_class' => 'clr long'],
                'sql' => "text NULL",
            ],
        ];
    }

    public function getPalette(): string
    {
        return '{webhook_legend},webhook_url,webhook_method,webhook_timeout,webhook_headers,webhook_payload';
    }

    public function send(RenderedMessage $message, array $gatewayConfig): bool
    {
        $url = trim((string) ($gatewayConfig['webhook_url'] ?? ''));

        if ('' === $url) {
            throw new \RuntimeException('The webhook gateway has no URL configured.');
        }

        $template = trim((string) ($gatewayConfig['webhook_payload'] ?? ''));
        $body = $this->buildPayload('' !== $template ? $template : self::DEFAULT_PAYLOAD, $message);

        // A timeout is not optional: without one an unresponsive endpoint would hold a
        // visitor's form submission open until PHP gives up.
        $timeout = max(1, (int) ($gatewayConfig['webhook_timeout'] ?: 10));

        try {
            $response = $this->httpClient->request(
                strtoupper((string) ($gatewayConfig['webhook_method'] ?: 'POST')),
                $url,
                [
                    'headers' => ['Content-Type' => 'application/json'] + $this->parseHeaders($gatewayConfig['webhook_headers'] ?? null),
                    'body' => $body,
                    'timeout' => $timeout,
                    'max_duration' => $timeout,
                ],
            );

            $status = $response->getStatusCode();
        } catch (HttpExceptionInterface $e) {
            throw new \RuntimeException(\sprintf('The webhook request to "%s" failed: %s', $url, $e->getMessage()), 0, $e);
        }

        if ($status >= 400) {
            // Include a little of the body: an endpoint that rejects a payload almost always
            // says why, and that message is what makes the log entry actionable.
            throw new \RuntimeException(\sprintf(
                'The webhook endpoint "%s" answered %d: %s',
                $url,
                $status,
                mb_substr($this->readBody($response), 0, 300),
            ));
        }

        return true;
    }

    /**
     * Substitutes ##tokens## into the payload template with JSON-safe values.
     */
    private function buildPayload(string $template, RenderedMessage $message): string
    {
        $values = [
            ...$message->tokens,
            'subject' => $message->subject,
            'text' => $message->text,
            'html' => (string) $message->html,
            'recipients' => $message->recipients,
            'alias' => $message->alias,
            'reference' => $message->reference,
        ];

        return (string) preg_replace_callback(
            '/##([^#=!<>\s][^=!<>\s]*?)##/',
            static function (array $matches) use ($values): string {
                if (!\array_key_exists($matches[1], $values)) {
                    return '';
                }

                // json_encode a string, then drop its quotes: the result is correctly
                // escaped for the inside of a JSON string literal.
                $encoded = json_encode((string) $values[$matches[1]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return false !== $encoded ? substr($encoded, 1, -1) : '';
            },
            $template,
        );
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string|null $serialised): array
    {
        if (null === $serialised || '' === $serialised) {
            return [];
        }

        $headers = [];

        foreach (StringUtil::deserialize($serialised, true) as $row) {
            if (!\is_array($row) || '' === trim((string) ($row['key'] ?? ''))) {
                continue;
            }

            $headers[trim((string) $row['key'])] = (string) ($row['value'] ?? '');
        }

        return $headers;
    }

    private function readBody(ResponseInterface $response): string
    {
        try {
            return $response->getContent(false);
        } catch (\Throwable) {
            return '(no response body)';
        }
    }
}
