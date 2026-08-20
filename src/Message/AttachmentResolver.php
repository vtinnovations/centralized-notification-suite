<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

use Contao\FilesModel;
use Contao\StringUtil;

class AttachmentResolver
{
    /**
     * Resolves a fileTree widget value (serialised list of file UUIDs) into attachments,
     * silently dropping entries whose file no longer exists on disk.
     *
     * @return list<Attachment>
     */
    public function resolveUuids(string|null $serialisedUuids): array
    {
        $uuids = StringUtil::deserialize($serialisedUuids, true);

        if (!$uuids) {
            return [];
        }

        $files = FilesModel::findMultipleByUuids($uuids);

        if (!$files) {
            return [];
        }

        $resolved = [];

        foreach ($files as $file) {
            $path = $file->getAbsolutePath();

            if (!is_file($path)) {
                continue;
            }

            $resolved[] = new Attachment($path, basename($path), mime_content_type($path) ?: null);
        }

        return $resolved;
    }

    /**
     * Collects the files uploaded through a form submission. Contao's FormUpload widget
     * puts the original client filename in "name" and the location on disk in "tmp_name"
     * -- the latter is PHP's temp upload path unless the field has "Store file" enabled,
     * which is why Attachment reads the bytes before the request ends.
     *
     * @param array<string, mixed> $files The $files argument of the processFormData hook
     *
     * @return list<Attachment>
     */
    public function resolveFormUploads(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            if (!\is_array($file)) {
                continue;
            }

            // A field with "multipleFiles" yields a list of uploads, a single one the upload itself
            foreach (isset($file['tmp_name']) ? [$file] : $file as $upload) {
                if (!\is_array($upload) || empty($upload['tmp_name']) || !is_file($upload['tmp_name'])) {
                    continue;
                }

                $attachments[] = new Attachment(
                    $upload['tmp_name'],
                    $upload['name'] ?? basename($upload['tmp_name']),
                    $upload['type'] ?? null,
                );
            }
        }

        return $attachments;
    }
}
