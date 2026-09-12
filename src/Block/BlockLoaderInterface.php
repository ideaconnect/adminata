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

namespace IDCT\Adminata\Block;

use IDCT\Adminata\Exception\BlockNotFoundException;
use IDCT\Adminata\Model\BlockInterface;

interface BlockLoaderInterface
{
    /**
     * @param string|array<string, mixed> $configuration
     *
     * @throws BlockNotFoundException if no block with that name is found
     */
    public function load($configuration): BlockInterface;

    /**
     * @param string|array<string, mixed> $configuration
     */
    public function support($configuration): bool;

    public function exists(string $type): bool;
}
