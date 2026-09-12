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

namespace IDCT\Adminata\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockDemoControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        restore_exception_handler();

        parent::tearDown();
    }

    public function testRenderBlocks(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/blocks');

        static::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $blocks = $crawler->filter('.cms-block');

        static::assertCount(2, $blocks);
        static::assertSame('Foo', trim($blocks->eq(0)->text()));
        static::assertSame('Insert your custom content here', trim($blocks->eq(1)->text()));
    }

    /**
     * The text block's default template is `@Adminata/Block/block_core_text.html.twig`, so the
     * application's `templates/bundles/AdminataBundle/Block/block_core_text.html.twig` takes its
     * place. That override extends the shipped file through `@!Adminata`, so the content it
     * wraps proves the whole chain: override, then bundle template, then `block_base`.
     */
    public function testTheApplicationOverridesTheTextBlockTemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/blocks');

        static::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $blocks = $crawler->filter('.cms-block');

        static::assertCount(0, $blocks->eq(0)->filter('.app-text-override'), 'Only the text block is overridden.');

        $override = $blocks->eq(1)->filter('.app-text-override');

        static::assertCount(1, $override, 'The text block does not render through the application override.');
        static::assertSame('Insert your custom content here', trim($override->text()));
    }
}
