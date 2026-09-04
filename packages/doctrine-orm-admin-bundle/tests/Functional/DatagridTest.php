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

namespace Sonata\DoctrineORMAdminBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * These scenarios click through the inherited Bootstrap interface. adminata replaced the
 * stylesheet and the JavaScript that made it interactive in P1-02, and rewrites the
 * templates themselves in milestones M3 and M4; the group is dropped again there.
 */
#[Group('legacy-ui')]
final class DatagridTest extends BasePantherTestCase
{
    public function testFilter(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/category/list');

        $this->client->clickLink('Filters');
        $this->client->clickLink('Name');

        $this->client->submitForm('Filter', [
            'filter[name][value]' => 'Dystopian',
        ]);

        self::assertSelectorTextContains('.sonata-link-identifier', 'Dystopian');
    }
}
