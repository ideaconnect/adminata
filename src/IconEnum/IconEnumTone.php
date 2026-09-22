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
 * The tone of an icon-enum case: which badge recipe its square takes.
 *
 * The same five tones the badges and the alerts have, plus the neutral grey of a bare
 * `adm-badge` — a case that is neither good nor bad, "not applicable", "unknown".
 */
enum IconEnumTone: string
{
    case Neutral = 'neutral';
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
    case Brand = 'brand';

    /**
     * The badge classes of the square: `adm-badge` alone for the neutral tone, with the
     * `adm-badge-<tone>` modifier for every other.
     */
    public function badgeClass(): string
    {
        return self::Neutral === $this ? 'adm-badge' : 'adm-badge adm-badge-'.$this->value;
    }
}
