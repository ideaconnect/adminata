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
 * An enum that knows how it is drawn.
 *
 * A column of type `FieldDescriptionInterface::TYPE_ICON_ENUM` shows each case as a square
 * carrying a glyph in a tone, never as text: the case's name goes on the square's `title`,
 * into an `sr-only` span, and — on a list — into the legend under the table. What the name
 * IS follows the same rules as `TYPE_ENUM`: the enum's `trans()` when it implements
 * Symfony's `TranslatableInterface`, otherwise the case name (or the backing value with
 * `use_value`), through `enum_translation_domain` when one is set.
 *
 * Implementing this interface is one of two ways to say which glyph a case takes; the
 * other is the field's `cases` option, which also overrides any single case of an enum
 * that does implement it. Extends `\UnitEnum`, so only an enum can implement it.
 */
interface IconEnumInterface extends \UnitEnum
{
    /**
     * The glyph, as the complete class list of the `<i>` that draws it: `fas fa-check`,
     * `far fa-clock`. Nothing is added to it.
     */
    public function icon(): string;

    public function tone(): IconEnumTone;
}
