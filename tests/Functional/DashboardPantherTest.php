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

        $this->assertConsoleIsEmpty('The product list wrote to the browser console.');
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

    /**
     * The dialog: opened by a button, closed by Escape and by the backdrop, with the focus trapped
     * inside while it is open. All of that is the browser's, which is the point of using
     * `<dialog>` (PLAN/01 J7); this is what proves the controller does not get in its way.
     */
    public function testTheDialogOpensTrapsFocusAndCloses(): void
    {
        $this->client->request('GET', $this->url('/admin/demo/dialog'));

        $dialog = $this->client->findElement(WebDriverBy::id('demo-dialog'));

        static::assertFalse($dialog->isDisplayed());

        $this->client->findElement(WebDriverBy::id('open-dialog'))->click();

        static::assertTrue($dialog->isDisplayed());
        static::assertTrue($this->focusIsInsideTheDialog(), 'Focus stayed outside the dialog.');

        // Tabbing round the dialog never reaches the page behind it. Firefox parks focus on the
        // document between the last focusable and the first, so `<body>` is a step in the cycle;
        // what matters is that no element of the page underneath ever takes it.
        for ($i = 0; $i < 6; ++$i) {
            $this->client->getKeyboard()->sendKeys(WebDriverKeys::TAB);

            static::assertContains(
                $this->focusedElement(),
                ['BODY', 'inside'],
                'Focus escaped the dialog on tab '.($i + 1).'.'
            );
        }

        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);

        static::assertFalse($dialog->isDisplayed());
        $this->assertConsoleIsEmpty('The dialog wrote to the browser console.');
    }

    /**
     * The filter panel: a filter is added from the dropdown, the panel appears, the value submits,
     * and reset puts everything back. `sonata-filter`'s `prepareSubmit` needs real
     * `<select name="filter[…]">` elements to strip the empty ones, which is why the operator
     * selects stayed native (PLAN/06 §1).
     */
    public function testAFilterCanBeAddedSubmittedAndReset(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        static::assertFalse($this->filterPanelIsVisible(), 'The filter panel starts hidden.');

        $this->client->findElement(WebDriverBy::cssSelector('.sonata-actions [aria-expanded]'))->click();
        $this->client->findElement(WebDriverBy::cssSelector('.sonata-toggle-filter[data-filter$="-sku"]'))->click();

        static::assertTrue($this->filterPanelIsVisible(), 'Adding a filter did not open the panel.');

        // Scoped to the group that was just opened: every filter has a `[value]` input, and the
        // ones belonging to hidden groups are not reachable. Waited for, because the group is
        // revealed by `sonata-filter` after the click.
        $this->client->waitForVisibility('[id$="-sku"] input[name$="[value]"]');
        $this->client->findElement(WebDriverBy::cssSelector('[id$="-sku"] input[name$="[value]"]'))->sendKeys('SKU-0007');
        $this->client->findElement(WebDriverBy::cssSelector('.sonata-filter-form button[type="submit"]'))->click();

        // Submitting navigates; the crawler Panther is holding belongs to the page that just went.
        $rows = $this->client->waitFor('table.sonata-ba-list')->filter('table.sonata-ba-list tbody tr');
        static::assertCount(1, $rows);
        static::assertStringContainsString('SKU-0007', $rows->text());

        $this->client->request('GET', $this->url('/admin/tests/app/product/list?filters=reset'));

        static::assertGreaterThan(1, $this->client->refreshCrawler()->filter('table.sonata-ba-list tbody tr')->count());
        $this->assertConsoleIsEmpty('Filtering wrote to the browser console.');
    }

    private function filterPanelIsVisible(): bool
    {
        return $this->client->findElement(WebDriverBy::cssSelector('.sonata-filters-box'))->isDisplayed();
    }

    private function focusIsInsideTheDialog(): bool
    {
        return 'inside' === $this->focusedElement();
    }

    /** `inside` when the dialog holds the focus, otherwise the focused element's id or tag name. */
    private function focusedElement(): string
    {
        return (string) $this->client->executeScript(
            'const active = document.activeElement;'
            .'return document.getElementById("demo-dialog").contains(active)'
            .' ? "inside" : (active.id || active.tagName);'
        );
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
