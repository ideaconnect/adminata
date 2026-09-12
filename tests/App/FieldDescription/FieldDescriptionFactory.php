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

namespace IDCT\Adminata\Tests\App\FieldDescription;

use IDCT\Adminata\FieldDescription\FieldDescriptionFactoryInterface;
use IDCT\Adminata\FieldDescription\FieldDescriptionInterface;

final class FieldDescriptionFactory implements FieldDescriptionFactoryInterface
{
    public function create(string $class, string $name, array $options = []): FieldDescriptionInterface
    {
        return new FieldDescription($name, $options);
    }
}
