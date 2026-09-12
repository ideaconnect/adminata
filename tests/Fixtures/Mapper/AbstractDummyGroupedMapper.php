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

namespace IDCT\Adminata\Tests\Fixtures\Mapper;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Mapper\BaseGroupedMapper;

/**
 * @phpstan-extends BaseGroupedMapper<object>
 */
abstract class AbstractDummyGroupedMapper extends BaseGroupedMapper
{
    /**
     * @param AdminInterface<object> $admin
     */
    public function __construct(
        private AdminInterface $admin,
    ) {
    }

    public function add(string $fieldName, ?string $name = null): self
    {
        $this->addFieldToCurrentGroup($fieldName, $name);

        return $this;
    }

    /**
     * @return AdminInterface<object>
     */
    public function getAdmin(): AdminInterface
    {
        return $this->admin;
    }

    protected function getName(): string
    {
        return 'dummy';
    }
}
