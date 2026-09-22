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

namespace IDCT\Adminata\Twig\Extension;

use IDCT\Adminata\Twig\IconEnumRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class IconEnumExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('adminata_icon_enum', [IconEnumRuntime::class, 'resolve']),
            new TwigFunction('adminata_icon_enum_legend', [IconEnumRuntime::class, 'legend']),
        ];
    }
}
