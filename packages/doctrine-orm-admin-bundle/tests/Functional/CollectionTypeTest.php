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
use Symfony\Component\Panther\DomCrawler\Crawler;

/**
 * These scenarios click through the inherited Bootstrap interface. adminata replaced the
 * stylesheet and the JavaScript that made it interactive in P1-02, and rewrites the
 * templates themselves in milestones M3 and M4; the group is dropped again there.
 */
#[Group('legacy-ui')]
final class CollectionTypeTest extends BasePantherTestCase
{
    public function testRemoveCollectionItemWithoutValidation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/tests/app/mother/1/edit?uniqid=mother');

        $form = $crawler->selectButton('Update')->form();
        $form['mother[children][0][name]'] = '';

        // The `+ ins` element this used to click was injected by iCheck, which adminata does not
        // ship: the delete checkbox is a plain input now.
        $crawler->filter('#mother_children_0__delete')->each(static function (Crawler $checkbox): void {
            $checkbox->click();
        });

        $this->client->submit($form);

        self::assertSelectorTextContains('.alert-success', 'Item "1" has been successfully updated.');
    }

    public function testTriggerCollectionValidation(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/tests/app/mother/1/edit?uniqid=mother');

        $form = $crawler->selectButton('Update')->form();
        $form['mother[children][0][name]'] = '';

        $this->client->submit($form);

        self::assertSelectorTextContains('.alert-danger', 'An error has occurred during update of item "1".');
    }
}
