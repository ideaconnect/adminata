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

namespace IDCT\Adminata\Tests\Fixtures\Enum;

use IDCT\Adminata\IconEnum\IconEnumInterface;
use IDCT\Adminata\IconEnum\IconEnumTone;

/**
 * An enum that knows how it is drawn: the fixture behind the TYPE_ICON_ENUM tests.
 */
enum Traffic: string implements IconEnumInterface
{
    case Go = 'go';
    case Wait = 'wait';
    case Stop = 'stop';

    public function icon(): string
    {
        return match ($this) {
            self::Go => 'fas fa-check',
            self::Wait => 'far fa-clock',
            self::Stop => 'fas fa-ban',
        };
    }

    public function tone(): IconEnumTone
    {
        return match ($this) {
            self::Go => IconEnumTone::Success,
            self::Wait => IconEnumTone::Warning,
            self::Stop => IconEnumTone::Error,
        };
    }
}
