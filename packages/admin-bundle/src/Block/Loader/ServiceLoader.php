<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Block\Loader;

use Sonata\AdminBundle\Block\BlockLoaderInterface;
use Sonata\AdminBundle\Model\Block;
use Sonata\AdminBundle\Model\BlockInterface;

final class ServiceLoader implements BlockLoaderInterface
{
    /**
     * @param string[] $types
     */
    public function __construct(private array $types)
    {
    }

    /**
     * Check if a given block type exists.
     *
     * @param string $type Block type to check for
     */
    public function exists(string $type): bool
    {
        return \in_array($type, $this->types, true);
    }

    public function load($configuration): BlockInterface
    {
        if (!\is_string($configuration) && !\is_array($configuration)) {
            throw new \TypeError(\sprintf(
                'Argument 1 passed to %s must be of type string or array, %s given',
                __METHOD__,
                \gettype($configuration)
            ));
        }

        if (\is_string($configuration)) {
            $configuration = [
                'type' => $configuration,
            ];
        }

        if (!\in_array($configuration['type'], $this->types, true)) {
            throw new \RuntimeException(\sprintf(
                'The block type "%s" does not exist',
                $configuration['type']
            ));
        }

        $block = new Block();
        // Without the dot `uniqid(…, true)` puts in: the id reaches the page as
        // `id="cms-block-…"`, and a dot makes it a selector nobody can write without escaping it.
        $block->setId(str_replace('.', '', uniqid('', true)));
        $block->setType($configuration['type']);
        $block->setEnabled(true);
        $block->setCreatedAt(new \DateTime());
        $block->setUpdatedAt(new \DateTime());
        $block->setSettings($configuration['settings'] ?? []);

        return $block;
    }

    public function support($configuration): bool
    {
        if (!\is_string($configuration) && !\is_array($configuration)) {
            throw new \TypeError(\sprintf(
                'Argument 1 passed to %s must be of type string or array, %s given',
                __METHOD__,
                \gettype($configuration)
            ));
        }

        if (!\is_array($configuration)) {
            return false;
        }

        if (!isset($configuration['type'])) {
            return false;
        }

        return true;
    }
}
