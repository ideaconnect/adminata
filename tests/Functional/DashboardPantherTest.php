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

use Adminata\Tests\App\EventListener\BrowserConsoleRecorderListener;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
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

    /**
     * `ThemeRuntime` resolves the cookie before a byte is sent, so a visitor who chose dark never
     * sees the light theme flash first (PLAN/01 C2).
     */
    public function testTheThemeCookieIsHonouredBeforeTheFirstPaint(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        static::assertFalse($this->isDark(), 'The demo defaults to the system theme.');

        $this->client->executeScript('document.cookie = "sonata_theme=dark; path=/";');
        $this->client->reload();

        static::assertTrue($this->isDark(), 'The server did not stamp html.dark from the cookie.');
        static::assertSame('dark', $this->client->executeScript('return document.documentElement.dataset.theme;'));

        $this->assertConsoleIsEmpty('The dark theme wrote to the browser console.');

        $this->client->executeScript('document.cookie = "sonata_theme=; path=/; max-age=0";');
    }

    /**
     * adminata ships exactly one inline script: the three lines that resolve the `system` theme
     * before the first paint (PLAN/01 J4).
     */
    public function testTheLayoutCarriesOneInlineScript(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        // Minus the console recorder, which only the test environment injects
        // (BrowserConsoleRecorderListener) and which is not part of what adminata ships.
        static::assertSame(
            1,
            $this->client->executeScript(\sprintf(
                'return document.querySelectorAll("script:not([src]):not([%s])").length;',
                BrowserConsoleRecorderListener::MARKER
            ))
        );
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

    /**
     * A group a visitor opened is still open on the next page: `sonata-menu` keeps the map in
     * `localStorage` and applies it over what the server rendered.
     */
    public function testAnOpenedMenuGroupSurvivesNavigation(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        static::assertSame('false', $this->groupState('Catalogue'));

        $this->client->executeScript(
            'document.evaluate(\'//button[.//span[text()="Catalogue"]]\', document, null, 9, null)'
            .'.singleNodeValue.click();'
        );

        static::assertSame('true', $this->groupState('Catalogue'));

        $this->client->request('GET', $this->url('/admin/tests/app/category/list'));

        static::assertSame('true', $this->groupState('Catalogue'), 'The open group was forgotten.');

        $this->client->executeScript('window.localStorage.removeItem("sonata_sidebar_open");');
    }

    /**
     * The add menu is a disclosure a keyboard can reach and leave: the down arrow opens it on its
     * first item, Escape closes it and gives the button its focus back.
     */
    public function testTheAddMenuIsUsableFromTheKeyboard(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        $button = $this->client->findElement(WebDriverBy::cssSelector('.adm-dropdown [aria-haspopup="menu"]'));
        $panel = $this->client->findElement(WebDriverBy::cssSelector('.dropdown-add'));

        static::assertFalse($panel->isDisplayed());

        $button->sendKeys(WebDriverKeys::ARROW_DOWN);

        static::assertTrue($panel->isDisplayed());
        static::assertSame('true', $button->getAttribute('aria-expanded'));
        static::assertSame(
            'menuitem',
            $this->client->executeScript('return document.activeElement.getAttribute("role");')
        );

        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);

        static::assertFalse($panel->isDisplayed());
        static::assertSame(
            'true',
            $this->client->executeScript('return String(document.activeElement === arguments[0]);', [$button])
        );

        $this->assertConsoleIsEmpty('The add menu wrote to the browser console.');
    }

    private function groupState(string $label): string
    {
        return (string) $this->client->executeScript(\sprintf(
            'return document.evaluate(\'//button[.//span[text()="%s"]]\', document, null, 9, null)'
            .'.singleNodeValue?.getAttribute("aria-expanded");',
            $label
        ));
    }

    private function isDark(): bool
    {
        return true === $this->client->executeScript('return document.documentElement.classList.contains("dark");');
    }

    private function sidebarState(): string
    {
        return (string) $this->client->executeScript('return document.body.dataset.sidebar;');
    }
}
