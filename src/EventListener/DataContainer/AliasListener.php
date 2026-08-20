<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * Generates tl_simple_notification.alias from the title when the field is left empty, the
 * same way Contao's own alias fields behave. The alias is what code passes to
 * SimpleNotifyCenter::send(), so it stays editable -- it just no longer has to be typed.
 */
class AliasListener
{
    public function __construct(
        private readonly Slug $slug,
        private readonly Connection $connection,
    ) {
    }

    #[AsCallback(table: 'tl_simple_notification', target: 'fields.alias.save')]
    public function __invoke(mixed $value, DataContainer $dc): string
    {
        $value = (string) $value;

        if ('' !== $value) {
            $this->assertUnique($value, (int) $dc->id);

            return $value;
        }

        $title = (string) ($dc->activeRecord->title ?? '');

        if ('' === $title) {
            throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['aliasFromEmptyTitle'] ?? 'Cannot generate an alias without a title.');
        }

        $generated = $this->slug->generate(
            $title,
            [],
            fn (string $alias): bool => $this->exists($alias, (int) $dc->id),
        );

        $this->assertUnique($generated, (int) $dc->id);

        return $generated;
    }

    private function assertUnique(string $alias, int $id): void
    {
        if ($this->exists($alias, $id)) {
            throw new \RuntimeException(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'] ?? 'The alias "%s" is already in use.', $alias));
        }
    }

    private function exists(string $alias, int $id): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT id FROM tl_simple_notification WHERE alias = :alias AND id != :id',
            ['alias' => $alias, 'id' => $id],
        );
    }
}
