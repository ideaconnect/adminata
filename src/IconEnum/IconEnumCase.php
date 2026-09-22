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

namespace IDCT\Adminata\IconEnum;

/**
 * One case of an icon-enum column, resolved: what the templates draw.
 *
 * `key` is what the case is known by in the field's `cases` option — the case name, or
 * the scalar itself for a column that holds a string or an integer rather than an enum.
 */
final readonly class IconEnumCase
{
    public function __construct(
        public string $key,
        public string $icon,
        public IconEnumTone $tone,
        public string $label,
    ) {
    }
}
