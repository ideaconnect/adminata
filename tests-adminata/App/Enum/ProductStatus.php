<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\App\Enum;

use IDCT\Adminata\IconEnum\IconEnumInterface;
use IDCT\Adminata\IconEnum\IconEnumTone;

/**
 * Implements {@see IconEnumInterface}: the product list and show page draw the status as a
 * glyph (`TYPE_ICON_ENUM`), with the legend under the list naming the three.
 */
enum ProductStatus: string implements IconEnumInterface
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'fas fa-pen',
            self::Published => 'fas fa-check',
            self::Archived => 'fas fa-box-archive',
        };
    }

    public function tone(): IconEnumTone
    {
        return match ($this) {
            self::Draft => IconEnumTone::Warning,
            self::Published => IconEnumTone::Success,
            self::Archived => IconEnumTone::Neutral,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
