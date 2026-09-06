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

namespace Sonata\AdminBundle\Twig\Extension;

use Sonata\AdminBundle\Twig\TokenParser\TemplateBoxTokenParser;
use Twig\Extension\AbstractExtension;

final class TemplateExtension extends AbstractExtension
{
    /**
     * @param bool $debug Is Symfony debug enabled?
     */
    public function __construct(private bool $debug)
    {
    }

    public function getTokenParsers(): array
    {
        return [
            new TemplateBoxTokenParser($this->debug),
        ];
    }

    public function getName(): string
    {
        return 'sonata_twig_template';
    }
}
