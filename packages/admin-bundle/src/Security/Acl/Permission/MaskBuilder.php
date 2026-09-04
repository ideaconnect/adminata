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

namespace Sonata\AdminBundle\Security\Acl\Permission;

use Symfony\Component\Security\Acl\Permission\MaskBuilder as BaseMaskBuilder;

/**
 * {@inheritdoc}
 * - LIST: the SID is allowed to view a list of the domain objects / fields.
 * - EXPORT: the SID is allowed to export the list of the domain objects / fields.
 * - HISTORY: the SID is allowed to see the history of edition of a domain objects / fields.
 */
final class MaskBuilder extends BaseMaskBuilder
{
    public const int MASK_LIST = 4096;       // 1 << 12
    public const int MASK_EXPORT = 8192;     // 1 << 13
    public const int MASK_HISTORY = 16384;   // 1 << 14

    public const string CODE_LIST = 'L';
    public const string CODE_EXPORT = 'E';
    public const string CODE_HISTORY = 'H';
}
