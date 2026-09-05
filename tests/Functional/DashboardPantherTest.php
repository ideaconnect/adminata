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
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
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

    /**
     * The hand-written combobox (PLAN/06 §3), driven only from the keyboard: it says how much more
     * to type, it searches once the term is long enough, the arrow keys move `aria-activedescendant`
     * and Enter writes the identifier into the hidden input the form actually submits.
     */
    public function testTheAutocompleteFilterIsUsableFromTheKeyboard(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/category/list'));

        $this->client->findElement(WebDriverBy::cssSelector('.sonata-actions [aria-expanded]'))->click();
        $this->client->findElement(WebDriverBy::cssSelector('.sonata-toggle-filter[data-filter$="-products"]'))->click();

        $input = $this->client->waitForVisibility('#filter_products_value_autocomplete_input')
            ->filter('#filter_products_value_autocomplete_input');
        $listbox = $this->client->findElement(WebDriverBy::id('filter_products_value_listbox'));

        static::assertFalse($listbox->isDisplayed(), 'The listbox starts closed.');

        // On the element, not the keyboard: the click that added the filter left focus on the
        // dropdown item, and `sendKeys` on an element is what puts it in the box.
        $this->client->findElement(WebDriverBy::id('filter_products_value_autocomplete_input'))->sendKeys('P');

        static::assertSame(
            'Type 1 or more characters to search',
            $this->client->findElement(WebDriverBy::cssSelector('.adm-combobox__status'))->getText()
        );
        static::assertFalse($listbox->isDisplayed(), 'A term below the minimum length opened the listbox.');

        $this->client->getKeyboard()->sendKeys('roduct 0');
        $this->client->waitForVisibility('#filter_products_value_listbox');

        // Five options and a sixth to load the rest: `items_per_page` is 5 on this filter.
        $options = $this->client->findElements(WebDriverBy::cssSelector('#filter_products_value_listbox [role="option"]'));
        static::assertCount(6, $options);
        static::assertSame('Load more results', end($options)->getText());

        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_DOWN);
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_DOWN);

        $active = $input->attr('aria-activedescendant');
        static::assertNotNull($active);
        static::assertSame('true', $this->client->findElement(WebDriverBy::id($active))->getAttribute('aria-selected'));
        static::assertSame('Product 02', $this->client->findElement(WebDriverBy::id($active))->getText());

        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);

        static::assertFalse($listbox->isDisplayed(), 'Choosing an option left the listbox open.');
        static::assertSame(
            'Product 02',
            $this->client->findElement(WebDriverBy::id('filter_products_value_autocomplete_input'))
                ->getAttribute('value')
        );
        static::assertSame(
            '2',
            $this->client->findElement(
                WebDriverBy::cssSelector('#filter_products_value_hidden_inputs_wrap input')
            )->getAttribute('value')
        );

        $this->client->findElement(WebDriverBy::cssSelector('.sonata-filter-form button[type="submit"]'))->click();

        $rows = $this->client->waitFor('table.sonata-ba-list')->filter('table.sonata-ba-list tbody tr');
        static::assertCount(1, $rows, 'The autocomplete filter did not narrow the list.');

        $this->assertConsoleIsEmpty('The autocomplete wrote to the browser console.');
    }

    /**
     * Shift-clicking a second row selects everything between it and the last one clicked — in both
     * directions. Upstream's upward half read `indexedDB > currentIndex`, the browser's IndexedDB
     * global rather than the loop's index, so it never ran.
     */
    public function testShiftSelectsARangeOfRows(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        $boxes = $this->client->findElements(WebDriverBy::cssSelector('tbody input[name="idx[]"]'));
        static::assertGreaterThan(4, \count($boxes));

        $boxes[1]->click();

        // One action chain, not a key press around a separate click: an element click is its own
        // WebDriver command and does not pick up modifier state set outside it. `action()` is on
        // `RemoteWebDriver`, which is what Panther always has; `WebDriver` does not declare it.
        $driver = $this->client->getWebDriver();
        static::assertInstanceOf(RemoteWebDriver::class, $driver);

        $driver->action()
            ->keyDown(null, WebDriverKeys::SHIFT)
            ->click($boxes[3])
            ->keyUp(null, WebDriverKeys::SHIFT)
            ->perform();

        static::assertSame([false, true, true, true, false], $this->rowSelection(5));

        // And the header follows: some rows selected, not all.
        static::assertTrue(
            $this->client->executeScript('return document.getElementById("list_batch_checkbox").indeterminate;')
        );

        $this->assertConsoleIsEmpty('Selecting rows wrote to the browser console.');
    }

    /**
     * The per-page select carries whole URLs as its option values, and `sonata-per-page` navigates
     * to the one chosen.
     */
    public function testChangingThePerPageReloadsTheList(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        static::assertCount(25, $this->client->getCrawler()->filter('table.sonata-ba-list tbody tr'));

        $select = $this->client->findElement(WebDriverBy::cssSelector('select.per-page'));
        new WebDriverSelect($select)->selectByVisibleText('50');

        $rows = $this->client->waitFor('table.sonata-ba-list')->filter('table.sonata-ba-list tbody tr');

        static::assertCount(42, $rows, 'The list did not reload with the larger page size.');
        $this->assertConsoleIsEmpty('Changing the page size wrote to the browser console.');
    }

    /**
     * @return list<bool>
     */
    private function rowSelection(int $count): array
    {
        $checked = $this->client->executeScript(
            'return Array.from(document.querySelectorAll(\'tbody input[name="idx[]"]\'))'
            .'.slice(0, arguments[0]).map((box) => box.checked);',
            [$count]
        );

        static::assertIsArray($checked);

        return array_map(static fn (mixed $value): bool => true === $value, array_values($checked));
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
