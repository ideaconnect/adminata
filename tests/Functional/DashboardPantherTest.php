<?php

declare(strict_types=1);

/*
 * This file is part of the adminata package.
 *
 * (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
 *
 * Forked from the Sonata Project
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Adminata\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;

/**
 * The first browser test: the demo's dashboard renders and nothing complains.
 *
 * M2 to M4 add the behaviour PLAN/08 §3 lists — sidebar collapse, the dark toggle, filters, batch
 * selection, the collection controller — as the templates that carry them are rewritten.
 */
#[Group('browser')]
final class DashboardPantherTest extends BasePantherTestCase
{
    public function testTheDashboardLoadsWithAnEmptyConsole(): void
    {
        $crawler = $this->client->request('GET', $this->url('/admin/dashboard'));

        // `getTitle()`, not the crawler: WebDriver reports the text of an element the page does
        // not display — `<title>` among them — as empty.
        static::assertStringContainsString('Dashboard', $this->client->getTitle());
        static::assertCount(1, $crawler->filter('header.main-header'));
        static::assertCount(1, $crawler->filter('aside.main-sidebar'));

        $this->assertConsoleIsEmpty('The dashboard wrote to the browser console.');
    }

    /**
     * The rail is a preference, and `sonata-layout` writes it to the cookie the server reads back,
     * so the page comes up collapsed rather than expanding and snapping shut.
     */
    public function testTheCollapsedSidebarSurvivesAReload(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        static::assertSame('expanded', $this->sidebarState());

        $this->client->executeScript(
            'document.querySelector(\'[data-sonata-layout-target="collapseOnly"]\').click();'
        );

        static::assertSame('collapsed', $this->sidebarState());

        $this->client->reload();

        static::assertSame('collapsed', $this->sidebarState(), 'The cookie did not survive the reload.');
        $this->assertConsoleIsEmpty('Collapsing the sidebar wrote to the browser console.');
    }

    public function testTheListLoads(): void
    {
        $crawler = $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        static::assertGreaterThan(0, $crawler->filter('table.sonata-ba-list tbody tr')->count());

        // Not `assertConsoleIsEmpty()` yet, and deliberately exact. The inherited
        // `CRUD/list.html.twig` still carries Sonata's inline jQuery, which throws because
        // adminata ships none (owner directive 2) — this is the browser saying out loud what the
        // deferred templates cost. M3 rewrites that template; the day it does, this assertion
        // fails, and the fix is to replace it with `assertConsoleIsEmpty()`.
        static::assertSame(
            ['uncaught: ReferenceError: jQuery is not defined'],
            $this->consoleMessages(),
            'The console errors of the inherited product list changed.'
        );
    }

    private function sidebarState(): string
    {
        return (string) $this->client->executeScript('return document.body.dataset.sidebar;');
    }
}
