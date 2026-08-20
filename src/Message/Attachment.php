<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Message;

/**
 * A file to attach. The bytes are read eagerly via getContents() at send time rather than
 * handed to the transport as a path, because Contao queues mail through Messenger and
 * form uploads live in PHP's temp directory, which is emptied when the request ends.
 *
 * $cid is set for images inlined into the HTML body (see HtmlRenderer): the attachment is
 * then referenced as <img src="cid:..."> instead of being listed as a separate download.
 */
class Attachment
{
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly string|null $type = null,
        public readonly string|null $cid = null,
    ) {
    }

    public function isReadable(): bool
    {
        return is_file($this->path) && is_readable($this->path);
    }

    public function isInline(): bool
    {
        return null !== $this->cid;
    }

    public function getContents(): string|false
    {
        return file_get_contents($this->path);
    }

    /**
     * @param array{path: string, name?: string|null, type?: string|null, cid?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['path'],
            $data['name'] ?? basename($data['path']),
            $data['type'] ?? null,
            $data['cid'] ?? null,
        );
    }
}
