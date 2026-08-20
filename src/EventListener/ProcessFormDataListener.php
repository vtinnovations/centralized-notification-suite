<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use VTInnovations\SimpleNotifyBundle\Exception\SimpleNotifyException;
use VTInnovations\SimpleNotifyBundle\Message\Attachment;
use VTInnovations\SimpleNotifyBundle\Message\AttachmentResolver;
use VTInnovations\SimpleNotifyBundle\Model\NotificationModel;
use VTInnovations\SimpleNotifyBundle\SimpleNotifyCenter;
use VTInnovations\SimpleNotifyBundle\Token\FormSummaryBuilder;

/**
 * Triggers the notifications selected on a form (tl_form.simple_notify_notifications)
 * whenever that form is submitted, exposing every submitted field as a ##field_name##
 * token and attaching any files uploaded through the form.
 */
#[AsHook('processFormData')]
class ProcessFormDataListener
{
    public function __construct(
        private readonly SimpleNotifyCenter $notifyCenter,
        private readonly AttachmentResolver $attachmentResolver,
        private readonly FormSummaryBuilder $summaryBuilder,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>  $submitted
     * @param array<string, mixed>  $formData
     * @param array<string, mixed>  $files
     * @param array<string, string> $labels
     */
    public function __invoke(array $submitted, array $formData, array $files, array $labels, Form $form): void
    {
        $notificationIds = StringUtil::deserialize($formData['simple_notify_notifications'] ?? null, true);

        if (!$notificationIds) {
            return;
        }

        $attachments = $this->attachmentResolver->resolveFormUploads($files);
        $tokens = $this->buildTokens($submitted, $formData, $labels, $attachments);
        $language = $this->requestStack->getCurrentRequest()?->getLocale();

        foreach (NotificationModel::findMultipleByIds($notificationIds) ?? [] as $notification) {
            try {
                $this->notifyCenter->send(
                    (string) $notification->alias,
                    $tokens,
                    $language,
                    $attachments,
                    SimpleNotifyCenter::SOURCE_FORM,
                );
            } catch (SimpleNotifyException $e) {
                // A notification selected on the form was deleted afterwards. The visitor's
                // submission is already stored, so log it and carry on rather than 500.
                $this->logger->error(
                    \sprintf('Form "%s" references an unusable notification: %s', $formData['title'] ?? '', $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }
    }

    /**
     * @param array<string, mixed>  $submitted
     * @param array<string, mixed>  $formData
     * @param array<string, string> $labels
     * @param list<Attachment>      $attachments
     *
     * @return array<string, string>
     */
    private function buildTokens(array $submitted, array $formData, array $labels, array $attachments): array
    {
        $values = [];

        foreach ($submitted as $name => $value) {
            $values[$name] = $this->flatten($value);
        }

        $tokens = [
            'form_id' => (string) ($formData['id'] ?? ''),
            'form_title' => (string) ($formData['title'] ?? ''),
        ];

        foreach ($values as $name => $value) {
            $tokens[$name] = $value;
            // The field's human-readable label, for building your own summary tables
            $tokens['label_'.$name] = $labels[$name] ?? $name;
        }

        // ##all_fields_html## and friends: a summary built from the actual submission, so
        // it cannot go stale when the form gains a field.
        return [
            ...$tokens,
            ...$this->summaryBuilder->build(
                $values,
                $labels,
                array_map(static fn (Attachment $a): string => $a->name, $attachments),
            ),
        ];
    }

    private function flatten(mixed $value): string
    {
        if (\is_array($value)) {
            return implode(', ', array_map(fn (mixed $v): string => $this->flatten($v), $value));
        }

        if (\is_bool($value)) {
            return $value ? '1' : '';
        }

        return (string) $value;
    }
}
