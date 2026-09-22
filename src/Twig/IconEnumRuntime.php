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

namespace IDCT\Adminata\Twig;

use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;
use IDCT\Adminata\IconEnum\IconEnumCase;
use IDCT\Adminata\IconEnum\IconEnumResolver;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * The icon-enum templates' way to the resolver: what a value draws, and a column's legend.
 */
final readonly class IconEnumRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private IconEnumResolver $resolver,
    ) {
    }

    public function resolve(mixed $value, FieldDescriptionInterface $fieldDescription): ?IconEnumCase
    {
        return $this->resolver->resolve($value, $fieldDescription);
    }

    /**
     * @return list<IconEnumCase>
     */
    public function legend(FieldDescriptionInterface $fieldDescription): array
    {
        return $this->resolver->legend($fieldDescription);
    }
}
