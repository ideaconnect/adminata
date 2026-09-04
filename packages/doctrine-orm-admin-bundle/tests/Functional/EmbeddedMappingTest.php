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
final class EmbeddedMappingTest extends BasePantherTestCase
{
    public function testFilterByEmbedded(): void
    {
        $this->client->request(Request::METHOD_GET, '/admin/tests/app/author/list');

        $this->client->clickLink('Filters');
        $this->client->clickLink('Address Street');

        $this->client->submitForm('Filter', [
            'filter[address__street][value]' => 'Mancha',
        ]);

        self::assertSelectorTextContains('.sonata-link-identifier', 'Miguel de Cervantes');
    }

    public function testCreateEntityWithEmbedded(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/admin/tests/app/author/create');

        $attributeId = $crawler->filter('.author_id')->attr('name');
        static::assertNotNull($attributeId);
        $attributeName = $crawler->filter('.author_name')->attr('name');
        static::assertNotNull($attributeName);
        $attributeAddressStreet = $crawler->filter('.author_address')->attr('name');
        static::assertNotNull($attributeAddressStreet);

        $form = $crawler->selectButton('Create')->form();
        $form[$attributeId] = 'new_id';
        $form[$attributeName] = 'A wonderful author';
        $form[$attributeAddressStreet] = 'A wonderful street to live';

        $this->client->submit($form);

        self::assertSelectorTextContains('.alert-success', '"A wonderful author" has been successfully created.');

        $this->client->clickLink('Delete');
        $this->client->submitForm('Yes, delete');

        self::assertSelectorTextContains('.alert-success', 'tem "A wonderful author" has been deleted successfully.');
    }
}
