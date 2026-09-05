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

use Symfony\Component\HttpFoundation\Request;

final class CompositePrimaryKeysTest extends BasePantherTestCase
{
    // The list footer is `.adm-list-footer` since M3 rewrote `CRUD/base_list.html.twig`;
    // `.box-footer` was AdminLTE's and is not one of the hooks PLAN/02 §8 keeps.

    public function testListCompositePrimaryKeys(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/car/list');

        self::assertSelectorTextContains('.adm-list-footer', '1 / 1  -  3 results');
    }

    public function testListRelationsCompositePrimaryKeys(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/item/list');

        self::assertSelectorTextContains('.adm-list-footer', '1 / 1  -  3 results');
    }
}
