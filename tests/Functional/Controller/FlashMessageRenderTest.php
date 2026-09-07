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

namespace Sonata\AdminBundle\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;

final class FlashMessageRenderTest extends WebTestCase
{
    protected function tearDown(): void
    {
        restore_exception_handler();

        parent::tearDown();
    }

    /**
     * `sonata_flash_error` is one of the raw flash types the danger group collects, so this also
     * covers the renaming the flash manager does before the template asks for a group.
     */
    public function testRenderFlashes(): void
    {
        $client = static::createClient();

        $this->addFlashes($client, [
            'success' => ['The product was saved.'],
            'sonata_flash_error' => ['The product could not be saved.'],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/flash');

        static::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $alerts = $crawler->filter('.adm-alert');

        static::assertCount(2, $alerts);

        static::assertSame('The product was saved.', trim($alerts->eq(0)->filter('.adm-alert__body')->text()));
        static::assertStringContainsString('adm-alert-success', $alerts->eq(0)->attr('class') ?? '');
        static::assertStringContainsString('alert-success', $alerts->eq(0)->attr('class') ?? '');
        static::assertSame('status', $alerts->eq(0)->attr('role'));

        static::assertSame('The product could not be saved.', trim($alerts->eq(1)->filter('.adm-alert__body')->text()));
        static::assertStringContainsString('adm-alert-error', $alerts->eq(1)->attr('class') ?? '');
        static::assertStringContainsString('alert-danger', $alerts->eq(1)->attr('class') ?? '');
        static::assertSame('alert', $alerts->eq(1)->attr('role'));
    }

    /**
     * Past `collapse` messages of one type the template folds the group into a single alert and
     * hides the extras behind the read-more toggle.
     */
    public function testMessagesOfOneTypeCollapseIntoOneAlert(): void
    {
        $client = static::createClient();

        $this->addFlashes($client, [
            'success' => ['The product was saved.', 'The variant was saved.'],
        ]);

        $crawler = $client->request(Request::METHOD_GET, '/flash');

        static::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $alerts = $crawler->filter('.adm-alert');

        static::assertCount(1, $alerts);

        $body = $alerts->filter('.adm-alert__body');

        static::assertStringContainsString('The product was saved.', $body->text());
        static::assertSame('The variant was saved.', trim($body->filter('.read-more-target')->text()));
        static::assertSame('2', trim($alerts->filter('.adm-badge')->text()));
    }

    /**
     * Writes the flash bag straight into a saved session and hands its cookie to the browser, so
     * the page under test is the first request that sees the messages.
     *
     * @param array<string, list<string>> $flashes raw flash type => messages
     */
    private function addFlashes(KernelBrowser $client, array $flashes): void
    {
        $sessionFactory = static::getContainer()->get('session.factory');
        static::assertInstanceOf(SessionFactoryInterface::class, $sessionFactory);

        $session = $sessionFactory->createSession();
        static::assertInstanceOf(Session::class, $session);

        foreach ($flashes as $type => $messages) {
            foreach ($messages as $message) {
                $session->getFlashBag()->add($type, $message);
            }
        }

        $session->save();

        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
