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
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
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
     * The rail is a preference, and `adminata-layout` writes it to the cookie the server reads back,
     * so the page comes up collapsed rather than expanding and snapping shut.
     */
    public function testTheCollapsedSidebarSurvivesAReload(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        static::assertSame('expanded', $this->sidebarState());

        $this->client->executeScript(
            'document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();'
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

        $this->client->executeScript('document.cookie = "adminata_theme=dark; path=/";');
        $this->client->reload();

        static::assertTrue($this->isDark(), 'The server did not stamp html.dark from the cookie.');
        static::assertSame('dark', $this->client->executeScript('return document.documentElement.dataset.theme;'));

        $this->assertConsoleIsEmpty('The dark theme wrote to the browser console.');

        $this->client->executeScript('document.cookie = "adminata_theme=; path=/; max-age=0";');
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

        static::assertGreaterThan(0, $crawler->filter('table.adminata-list tbody tr')->count());

        $this->assertConsoleIsEmpty('The product list wrote to the browser console.');
    }

    /**
     * Clicking a row opens the object, and clicking a control inside it does not.
     *
     * The demo's `default_admin_route` is the shipped `show`, so `/…/product/1/show` is where a
     * row goes; the batch checkbox in the first cell is the control that has to keep its click.
     */
    public function testClickingARowOpensTheObject(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        $box = $this->client->findElement(WebDriverBy::cssSelector('tbody input[name="idx[]"]'));
        $box->click();

        static::assertTrue($box->isSelected(), 'The batch checkbox lost its click to the row.');
        static::assertStringContainsString(
            '/admin/tests/app/product/list',
            $this->client->getCurrentURL(),
            'A click on the batch checkbox navigated away.'
        );

        $this->client->findElement(
            WebDriverBy::cssSelector('tr.adminata-list-row-link td.adminata-list-field-string')
        )->click();
        $this->client->waitFor('.adminata-show');

        static::assertStringContainsString('/admin/tests/app/product/1/show', $this->client->getCurrentURL());

        $this->assertConsoleIsEmpty('Opening a product from its row wrote to the browser console.');
    }

    /**
     * A group a visitor opened is still open on the next page: `adminata-menu` keeps the map in
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

        $this->client->executeScript('window.localStorage.removeItem("adminata_sidebar_open");');
    }

    /**
     * The row above the menu: the dashboard and sign-out are plain links, and the two buttons that
     * fold and unfold every group appear once `adminata-sidebar-toolbar` has found the menu. What
     * they do is remembered like a single group's state, and the collapsed rail hides the row
     * with the rest of the menu's text.
     */
    public function testTheSidebarToolbarFoldsAndUnfoldsEveryGroup(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        static::assertSame(['/admin/dashboard', '/logout'], $this->toolbarLinks());
        static::assertSame([false, false], $this->toolbarButtonsHidden(), 'The fold buttons stayed hidden with a menu on the page.');

        $groups = \count($this->groupStates());
        static::assertGreaterThan(1, $groups, 'The demo menu has too few groups to tell "all" from "one".');

        $this->client->executeScript('document.querySelector(\'[data-adminata-sidebar-toolbar-target="expand"]\').click();');
        static::assertSame(array_fill(0, $groups, 'true'), $this->groupStates());

        $this->client->executeScript('document.querySelector(\'[data-adminata-sidebar-toolbar-target="collapse"]\').click();');
        static::assertSame(array_fill(0, $groups, 'false'), $this->groupStates());

        $this->client->reload();
        static::assertSame(array_fill(0, $groups, 'false'), $this->groupStates(), 'Collapsing every group was forgotten.');

        $this->client->executeScript('document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();');
        static::assertSame('collapsed', $this->sidebarState());
        static::assertFalse(
            $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-toolbar'))->isDisplayed(),
            'The collapsed rail still shows the toolbar.',
        );

        $this->assertConsoleIsEmpty('The sidebar toolbar wrote to the browser console.');
        $this->client->executeScript('window.localStorage.removeItem("adminata_sidebar_open");');
    }

    /**
     * Sign-out above the menu asks before it goes: a click opens the layout's question dialog
     * instead of following the link; cancelling leaves the page signed in where it was, and
     * confirming follows the link — the firewall signs out and sends the browser to `/`, which
     * the demo does not route. Each test signs in again, so the next one starts signed in.
     */
    public function testTheSidebarSignOutAsksBeforeItGoes(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        $question = $this->client->findElement(WebDriverBy::id('adminata-question-dialog'));
        $signOut = $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-toolbar a[href="/logout"]'));

        static::assertFalse($question->isDisplayed());

        $signOut->click();

        static::assertTrue($question->isDisplayed(), 'Sign-out went ahead without asking.');
        static::assertSame('Do you really want to log out?', $question->findElement(WebDriverBy::cssSelector('[data-adminata-question-text]'))->getText());
        static::assertSame('Log out', trim($question->findElement(WebDriverBy::cssSelector('[data-adminata-question-confirm]'))->getText()));
        static::assertStringEndsWith('/admin/dashboard', $this->client->getCurrentURL());

        $question->findElement(WebDriverBy::cssSelector('[data-adminata-question-cancel]'))->click();

        static::assertFalse($question->isDisplayed());
        static::assertStringEndsWith('/admin/dashboard', $this->client->getCurrentURL(), 'Cancelling signed out.');

        $signOut->click();
        static::assertTrue($question->isDisplayed());
        $question->findElement(WebDriverBy::cssSelector('[data-adminata-question-confirm]'))->click();

        $this->client->wait(10)->until(static fn ($driver): bool => '/' === parse_url((string) $driver->getCurrentURL(), \PHP_URL_PATH));
        static::assertSame('/', parse_url($this->client->getCurrentURL(), \PHP_URL_PATH), 'Confirming did not sign out.');
    }

    /**
     * The collapsed rail has no room to unfold a group, so a click opens the group as a popup
     * beside the rail, headed by its name: no chevron on the rail, one popup at a time, closed by
     * Escape — which gives the button the focus back — and by a click anywhere else. None of it is
     * remembered: the accordion the visitor left is what comes back when the rail widens.
     */
    public function testTheCollapsedRailOpensAGroupAsAPopup(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));
        $this->client->executeScript('window.localStorage.setItem("adminata_sidebar_open", JSON.stringify({Taxonomy: true}));');
        $this->client->reload();

        static::assertSame(['false', 'true'], $this->groupStates());

        $this->client->executeScript('document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();');
        static::assertSame('collapsed', $this->sidebarState());
        static::assertSame(['false', 'false'], $this->groupStates(), 'The rail left a popup open.');

        $catalogue = $this->groupButton('Catalogue');
        static::assertFalse(
            $catalogue->findElement(WebDriverBy::cssSelector('.menu-item-arrow'))->isDisplayed(),
            'The rail still shows a chevron.',
        );

        $catalogue->click();

        static::assertTrue($this->popup('Catalogue')->isDisplayed());
        static::assertSame(['true', 'false'], $this->groupStates());
        static::assertSame('Catalogue', $this->popup('Catalogue')->findElement(WebDriverBy::cssSelector('.adm-menu-popup-title'))->getText());
        static::assertTrue($this->popup('Catalogue')->findElement(WebDriverBy::linkText('Products'))->isDisplayed());
        static::assertTrue(
            $this->client->executeScript(
                'return arguments[0].getBoundingClientRect().left >= document.querySelector(".main-sidebar").getBoundingClientRect().right;',
                [$this->popup('Catalogue')],
            ),
            'The popup is not beside the rail.',
        );

        $taxonomy = $this->groupButton('Taxonomy');
        $taxonomy->click();

        static::assertFalse($this->popup('Catalogue')->isDisplayed(), 'Two popups are open.');
        static::assertTrue($this->popup('Taxonomy')->isDisplayed());

        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);

        static::assertFalse($this->popup('Taxonomy')->isDisplayed(), 'Escape left the popup open.');
        static::assertTrue($this->client->executeScript('return document.activeElement === arguments[0];', [$taxonomy]));

        $catalogue->click();
        // The header, which the popup never covers: the page's title sits right under it.
        $this->client->findElement(WebDriverBy::cssSelector('header.main-header'))->click();

        static::assertFalse($this->popup('Catalogue')->isDisplayed(), 'A click elsewhere left the popup open.');
        static::assertSame(
            '{"Taxonomy":true}',
            $this->client->executeScript('return window.localStorage.getItem("adminata_sidebar_open");'),
            'A popup was remembered as the accordion.',
        );

        $this->client->executeScript('document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();');

        static::assertSame('expanded', $this->sidebarState());
        static::assertSame(['false', 'true'], $this->groupStates(), 'The accordion did not come back.');

        $this->assertConsoleIsEmpty('The rail popup wrote to the browser console.');
        $this->client->executeScript('window.localStorage.removeItem("adminata_sidebar_open");');
    }

    /**
     * The field above the menu narrows it as it is typed into: case aside, a group whose name
     * matches keeps its links, the groups holding a match open and nothing of it is remembered.
     * The status says when nothing matches, Escape gives the whole menu back, Enter follows the
     * first match, and the collapsed rail hides the field and ends the filter.
     */
    public function testTheSidebarFilterNarrowsTheMenu(): void
    {
        $this->client->request('GET', $this->url('/admin/dashboard'));

        $field = $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-filter input[type="search"]'));
        $status = $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-filter [data-adminata-menu-filter-target="empty"]'));

        static::assertTrue($field->isDisplayed(), 'The filter stayed hidden with a menu on the page.');
        static::assertSame(['false', 'false'], $this->groupStates());

        $field->sendKeys('VARIANT');

        static::assertSame(['Variants'], $this->shownMenuLinks());
        static::assertSame(['true', 'false'], $this->groupStates(), 'The group holding the match stayed closed.');
        static::assertFalse($status->isDisplayed());

        $field->sendKeys(WebDriverKeys::ESCAPE);

        static::assertSame('', $field->getAttribute('value'));
        static::assertSame(['Products', 'Variants', 'Categories', 'Tags', 'Settings'], $this->shownMenuLinks());
        static::assertSame(['false', 'false'], $this->groupStates(), 'The groups did not go back as they were.');
        static::assertNull(
            $this->client->executeScript('return window.localStorage.getItem("adminata_sidebar_open");'),
            'The filter was remembered as the accordion.',
        );

        $field->sendKeys('taxonomy');
        static::assertSame(['Categories', 'Tags'], $this->shownMenuLinks());

        $field->clear();
        $field->sendKeys('nothing like it');
        static::assertSame([], $this->shownMenuLinks());
        static::assertTrue($status->isDisplayed(), 'Nothing matches, and nothing says so.');

        $field->sendKeys(WebDriverKeys::ESCAPE);
        $field->sendKeys('categ');
        $field->sendKeys(WebDriverKeys::ENTER);
        $this->client->wait()->until(WebDriverExpectedCondition::urlContains('/admin/tests/app/category/list'));

        $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-filter input[type="search"]'))->sendKeys('tag');
        $this->client->executeScript('document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();');

        static::assertSame('collapsed', $this->sidebarState());
        static::assertFalse(
            $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-filter'))->isDisplayed(),
            'The collapsed rail still shows the filter.',
        );
        static::assertCount(5, $this->shownMenuLinks(), 'The rail kept the menu narrowed.');

        $this->client->executeScript('document.querySelector(\'[data-adminata-layout-target="collapseOnly"]\').click();');

        static::assertSame('', $this->client->findElement(WebDriverBy::cssSelector('.adm-sidebar-filter input[type="search"]'))->getAttribute('value'));

        $this->assertConsoleIsEmpty('The sidebar filter wrote to the browser console.');
        $this->client->executeScript('window.localStorage.removeItem("adminata_sidebar_open");');
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
     * and reset puts everything back. `adminata-filter`'s `prepareSubmit` needs real
     * `<select name="filter[…]">` elements to strip the empty ones, which is why the operator
     * selects stayed native (PLAN/06 §1).
     */
    public function testAFilterCanBeAddedSubmittedAndReset(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        static::assertFalse($this->filterPanelIsVisible(), 'The filter panel starts hidden.');

        $this->client->findElement(WebDriverBy::cssSelector('.adminata-actions [aria-expanded]'))->click();

        // The page's controls sit at the end of the header row, a list page's too, and the filter
        // list opens from there leftwards — inside the window rather than off its edge.
        $edges = $this->client->executeScript(
            'const edge = (selector) => document.querySelector(selector).getBoundingClientRect();'
            .'return {header: edge(".adm-page-header").right, filters: edge("[id^=\'filter-list-\']").right,'
            .' menu: edge("[id^=\'filter-list-\'] .adm-dropdown__menu").right, window: document.documentElement.clientWidth};'
        );
        static::assertIsArray($edges);
        static::assertEqualsWithDelta($edges['header'], $edges['filters'], 1, 'The filters do not sit at the end of the header.');
        static::assertLessThanOrEqual($edges['window'], $edges['menu'], 'The filter list spills out of the window.');

        $this->client->findElement(WebDriverBy::cssSelector('.adminata-toggle-filter[data-filter$="-sku"]'))->click();

        static::assertTrue($this->filterPanelIsVisible(), 'Adding a filter did not open the panel.');

        // Scoped to the group that was just opened: every filter has a `[value]` input, and the
        // ones belonging to hidden groups are not reachable. Waited for, because the group is
        // revealed by `adminata-filter` after the click.
        $this->client->waitForVisibility('[id$="-sku"] input[name$="[value]"]');
        $this->client->findElement(WebDriverBy::cssSelector('[id$="-sku"] input[name$="[value]"]'))->sendKeys('SKU-0007');
        $this->client->findElement(WebDriverBy::cssSelector('.adminata-filter-form button[type="submit"]'))->click();

        // Submitting navigates; the crawler Panther is holding belongs to the page that just went.
        $rows = $this->client->waitFor('table.adminata-list')->filter('table.adminata-list tbody tr');
        static::assertCount(1, $rows);
        static::assertStringContainsString('SKU-0007', $rows->text());

        $this->client->request('GET', $this->url('/admin/tests/app/product/list?filters=reset'));

        static::assertGreaterThan(1, $this->client->refreshCrawler()->filter('table.adminata-list tbody tr')->count());
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

        $this->client->findElement(WebDriverBy::cssSelector('.adminata-actions [aria-expanded]'))->click();
        $this->client->findElement(WebDriverBy::cssSelector('.adminata-toggle-filter[data-filter$="-products"]'))->click();

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

        $this->client->findElement(WebDriverBy::cssSelector('.adminata-filter-form button[type="submit"]'))->click();

        $rows = $this->client->waitFor('table.adminata-list')->filter('table.adminata-list tbody tr');
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

        // An element click scrolls to what it clicks; a pointer move inside an action chain is
        // given a coordinate and does not. With sixteen columns the fourth row sits below a short
        // viewport, and the move is then out of bounds.
        $this->client->executeScript('arguments[0].scrollIntoView({block: "center"});', [$boxes[3]]);

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
     * A list whose batch column was removed still renders and still starts `adminata-batch` without
     * complaining: the controller has to tolerate a missing `all` target and an empty row set.
     */
    public function testAListWithoutABatchColumnLoadsCleanly(): void
    {
        $crawler = $this->client->request('GET', $this->url('/admin/tests/app/tag/list'));

        static::assertCount(4, $crawler->filter('table.adminata-list tbody tr'));
        static::assertCount(0, $crawler->filter('input[name="idx[]"]'));

        $this->assertConsoleIsEmpty('The tag list wrote to the browser console.');
    }

    /**
     * The export menu opens from the keyboard, and its links carry the datagrid state the page was
     * rendered with — which is what makes "export what I am looking at" true.
     */
    public function testTheExportMenuOpensAndKeepsTheCurrentFilter(): void
    {
        $this->client->request('GET', $this->url(
            '/admin/tests/app/product/list?filter%5Bsku%5D%5Bvalue%5D=SKU-0007'
        ));

        // Found from the link outwards: the list page carries several dropdowns and only one of
        // them holds the export formats.
        $button = $this->client->findElement(WebDriverBy::xpath(
            "//a[contains(@href, 'format=csv')]/ancestor::div[contains(@class, 'adm-dropdown')][1]"
            ."//button[@aria-haspopup='menu']"
        ));
        $button->sendKeys(WebDriverKeys::ARROW_DOWN);

        $csv = $this->client->findElement(WebDriverBy::cssSelector('a[href*="format=csv"]'));

        static::assertTrue($csv->isDisplayed());

        $href = $csv->getAttribute('href');
        static::assertIsString($href);
        static::assertStringContainsString('SKU-0007', urldecode($href));

        $this->assertConsoleIsEmpty('The export menu wrote to the browser console.');
    }

    /**
     * The collection widget: `adminata-collection` clones the prototype, substitutes `__name__` into
     * every id and name, and the delete button takes its own row away (PLAN/06 §2).
     */
    public function testACollectionRowCanBeAddedAndRemoved(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/create'));

        // The collection has the second tab to itself.
        $this->client->findElements(WebDriverBy::cssSelector('[role="tab"]'))[1]->click();

        $rows = fn (): int => \count(
            $this->client->findElements(WebDriverBy::cssSelector('.adminata-collection-row'))
        );

        static::assertSame(0, $rows(), 'A new product starts with no variants.');

        $add = $this->client->findElement(WebDriverBy::cssSelector('.adminata-collection-add'));
        $add->click();
        $add->click();

        static::assertSame(2, $rows());

        // The second row's fields carry index 1 in both halves of the contract.
        $second = $this->client->findElements(WebDriverBy::cssSelector('.adminata-collection-row input[type="text"]'))[1];
        static::assertStringEndsWith('_variants_1_label', (string) $second->getAttribute('id'));
        static::assertStringContainsString('[variants][1][label]', (string) $second->getAttribute('name'));

        $this->client->findElements(WebDriverBy::cssSelector('.adminata-collection-delete'))[0]->click();

        static::assertSame(1, $rows(), 'Deleting a row left it on the page.');
        $this->assertConsoleIsEmpty('The collection wrote to the browser console.');
    }

    /**
     * Native date and time inputs, both ways (PLAN/06 §4). The picker library is gone, so what the
     * browser sends is what `BasePickerType` derives its format from — and a mismatch shows up as
     * a value that does not survive the round trip.
     */
    public function testNativeDateAndTimeInputsRoundTrip(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/create'));

        $fill = function (string $suffix, string $value): void {
            $field = $this->client->findElement(WebDriverBy::cssSelector(\sprintf('input[id$="_%s"]', $suffix)));
            $field->clear();
            // A native date or time input takes the parts in the order the *locale* shows them, so
            // the value is set through the DOM rather than typed.
            $this->client->executeScript(
                'arguments[0].value = arguments[1]; arguments[0].dispatchEvent(new Event("change", {bubbles: true}));',
                [$field, $value]
            );
        };

        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_name"]'))->sendKeys('Native dates');
        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_sku"]'))->sendKeys('SKU-9001');
        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_price"]'))->sendKeys('4200');
        $this->chooseInTheCombobox('category', 'Beverages');

        $fill('releasedAt', '2026-09-04T10:15');
        $fill('availableFrom', '2026-09-05');
        $fill('pickupAt', '07:30');

        $this->client->findElement(WebDriverBy::cssSelector('button[name="btn_create_and_edit"]'))->click();

        $edit = $this->client->waitFor('.adminata-form');

        static::assertCount(
            0,
            $edit->filter('.adminata-field-error'),
            'The form came back with errors: '.$edit->filter('.adminata-field-error-messages')->text('')
        );

        static::assertSame(
            ['2026-09-04T10:15', '2026-09-05', '07:30'],
            array_map(
                fn (string $suffix): string => (string) $this->client
                    ->findElement(WebDriverBy::cssSelector(\sprintf('input[id$="_%s"]', $suffix)))
                    ->getAttribute('value'),
                ['releasedAt', 'availableFrom', 'pickupAt']
            )
        );

        $this->assertConsoleIsEmpty('The date inputs wrote to the browser console.');

        // A browser test writes through the real server, outside the transaction the BrowserKit
        // tests are wrapped in, so it puts back what it created — the row counts of every other
        // test in this run depend on it.
        static::assertSame(1, preg_match('#/product/(\d+)/edit#', $this->client->getCurrentURL(), $matches));

        $this->client->request('GET', $this->url(\sprintf('/admin/tests/app/product/%s/delete', $matches[1])));
        $this->client->findElement(WebDriverBy::cssSelector('.adminata-delete form button[type="submit"]'))->click();

        $this->client->waitFor('table.adminata-list');
    }

    /**
     * The combobox in its form context, single and multiple (PLAN/06 §3): the request carries the
     * admin's `uniqid` and the field name instead of `_context=filter`, a multiple field keeps its
     * selection as chips over `name[]` hidden inputs, and Backspace on an empty box drops the last
     * one.
     */
    public function testTheAutocompleteFormFieldKeepsSingleAndMultipleSelections(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/create'));

        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_name"]'))->sendKeys('Autocompleted');
        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_sku"]'))->sendKeys('SKU-9002');
        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_price"]'))->sendKeys('1500');

        $this->chooseInTheCombobox('category', 'Household');

        $this->chooseInTheCombobox('tags', 'Organic');
        $this->chooseInTheCombobox('tags', 'Sale');
        $this->chooseInTheCombobox('tags', 'Bulk');

        $chips = fn (): array => array_values(array_map(
            static fn (WebDriverElement $chip): string => $chip->getText(),
            $this->client->findElements(WebDriverBy::cssSelector('[id$="_tags"] .adm-chip [data-label]'))
        ));

        static::assertSame(['Organic', 'Sale', 'Bulk'], $chips());

        // Backspace in an empty box drops the last chip, and only then.
        $tags = $this->client->findElement(WebDriverBy::cssSelector('input[id$="_tags_autocomplete_input"]'));
        $tags->sendKeys(WebDriverKeys::BACKSPACE);

        static::assertSame(['Organic', 'Sale'], $chips());

        $this->client->findElement(WebDriverBy::cssSelector('button[name="btn_create_and_edit"]'))->click();
        $edit = $this->client->waitFor('.adminata-form');

        static::assertCount(
            0,
            $edit->filter('.adminata-field-error'),
            'The form came back with errors: '.$edit->filter('.adminata-field-error-messages')->text('')
        );

        // What the server rendered back: the single field shows its label, the multiple one its
        // chips, and the hidden inputs are what carried them.
        static::assertSame(
            'Household',
            $this->client->findElement(
                WebDriverBy::cssSelector('input[id$="_category_autocomplete_input"]')
            )->getAttribute('value')
        );
        // A many-to-many has no order of its own, so what comes back is the collection's, not the
        // order the chips were added in.
        static::assertEqualsCanonicalizing(['Organic', 'Sale'], $chips());
        static::assertCount(
            2,
            $this->client->findElements(WebDriverBy::cssSelector('[id$="_tags_hidden_inputs_wrap"] input'))
        );

        $this->assertConsoleIsEmpty('The autocomplete form field wrote to the browser console.');

        static::assertSame(1, preg_match('#/product/(\d+)/edit#', $this->client->getCurrentURL(), $matches));

        $this->client->request('GET', $this->url(\sprintf('/admin/tests/app/product/%s/delete', $matches[1])));
        $this->client->findElement(WebDriverBy::cssSelector('.adminata-delete form button[type="submit"]'))->click();

        $this->client->waitFor('table.adminata-list');
    }

    /**
     * The per-page select carries whole URLs as its option values, and `adminata-per-page` navigates
     * to the one chosen.
     */
    public function testChangingThePerPageReloadsTheList(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/list'));

        static::assertCount(25, $this->client->getCrawler()->filter('table.adminata-list tbody tr'));

        $select = $this->client->findElement(WebDriverBy::cssSelector('select.per-page'));
        new WebDriverSelect($select)->selectByVisibleText('50');

        $rows = $this->client->waitFor('table.adminata-list')->filter('table.adminata-list tbody tr');

        static::assertCount(42, $rows, 'The list did not reload with the larger page size.');
        $this->assertConsoleIsEmpty('Changing the page size wrote to the browser console.');
    }

    /**
     * The edit chrome (PLAN/03 §C): groups are cards in a grid — the product's on the `masonry`
     * layout, packed by `adminata-masonry` — the action bar is sticky and gains `.stuck` once it
     * leaves the flow, and `adminata-confirm-exit` arms the browser's own "leave site?" prompt as
     * soon as a field changes — and disarms it on submit.
     */
    public function testTheEditChromeIsStickyAndGuardsAgainstLeaving(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/1/edit'));

        $groups = $this->client->findElements(WebDriverBy::cssSelector('.adm-card .adminata-collapsed-fields'));
        static::assertCount(5, $groups, 'The five form groups of the demo admin, on both tabs.');

        // The controller has packed the first tab: every group spans its height in unit rows and
        // is pinned to the column the grid put it in.
        $packed = $this->packing();
        static::assertSame('4px', $packed['rows']);
        static::assertCount(4, $packed['spans']);
        foreach ($packed['spans'] as $span) {
            static::assertMatchesRegularExpression('/^span [1-9]\d*$/', $span);
        }
        static::assertSame(['1', '2', '3'], \array_slice($packed['columns'], 0, 3), 'The first three groups head the three columns.');

        $actions = $this->client->findElement(WebDriverBy::cssSelector('.adminata-form-actions'));
        static::assertStringContainsString('adm-sticky', (string) $actions->getAttribute('class'));

        // The bar sits below the fold on a form this long, so `adminata-sticky` pins it from the
        // first intersection callback — no scrolling needed.
        $this->client->waitForAttributeToContain('.adminata-form-actions', 'class', 'stuck');

        // And it lets go once the page is scrolled down to where the bar actually belongs.
        $this->client->executeScript('window.scrollTo(0, document.body.scrollHeight);');
        $this->client->waitForAttributeToNotContain('.adminata-form-actions', 'class', 'stuck');

        // `beforeunload` only counts once something changed, and a submit takes the guard off.
        static::assertFalse($this->confirmExitIsArmed());

        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_name"]'))->sendKeys(' edited');

        static::assertTrue($this->confirmExitIsArmed(), 'Editing a field did not arm the exit guard.');

        $this->assertConsoleIsEmpty('The edit page wrote to the browser console.');
    }

    /**
     * The optimistic lock: `lock_protection` puts `_lock_version` in the form, and a stale one
     * comes back as a flash rather than an exception page.
     */
    /**
     * The tabs (`adminata-tabs`, PLAN/05 §4). A panel other than the selected tab's is hidden;
     * selecting a tab shows its panel, moves `aria-selected` and writes the tab into the address
     * (`adminata-edit#changeTab`), which is how the tab survives a save; a masonry tab that
     * started hidden is packed the moment it is shown; the arrow keys move between the tabs; and
     * a field the browser finds invalid in a hidden tab brings its tab forward first, so the
     * browser can focus it — without that, the submit is silently refused.
     */
    public function testTabsSwitchPersistPackAndSurfaceAnInvalidField(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/1/edit'));

        $tabs = $this->client->findElements(WebDriverBy::cssSelector('[role="tab"]'));
        static::assertCount(2, $tabs);
        static::assertSame([false, true], $this->hiddenPanels());

        $tabs[1]->click();
        static::assertSame([true, false], $this->hiddenPanels());
        static::assertSame('true', $tabs[1]->getAttribute('aria-selected'));
        static::assertSame('false', $tabs[0]->getAttribute('aria-selected'));
        static::assertStringEndsWith('_2', (string) parse_url($this->client->getCurrentURL(), \PHP_URL_QUERY));

        $tabs[0]->click();
        static::assertSame([false, true], $this->hiddenPanels());
        static::assertStringEndsWith('_1', (string) parse_url($this->client->getCurrentURL(), \PHP_URL_QUERY));

        // Opened on the second tab by the address, the masonry tab is hidden and unpacked …
        $this->client->request('GET', $this->url('/admin/tests/app/product/1/edit?_tab=tab_x_2'));
        static::assertSame([true, false], $this->hiddenPanels());
        static::assertSame(['', '', '', ''], $this->packing()['spans']);

        // … and packed as soon as it is shown: being shown is what resizes its cards.
        $this->client->findElements(WebDriverBy::cssSelector('[role="tab"]'))[0]->click();
        $this->client->waitFor('[data-adminata-masonry-target="item"][style*="span"]');
        $packed = $this->packing();
        foreach ($packed['spans'] as $span) {
            static::assertMatchesRegularExpression('/^span [1-9]\d*$/', $span);
        }
        static::assertSame(['1', '2', '3'], \array_slice($packed['columns'], 0, 3));

        // The keyboard: the arrow keys move between the tabs and select the one reached.
        $tabs = $this->client->findElements(WebDriverBy::cssSelector('[role="tab"]'));
        $tabs[0]->sendKeys(WebDriverKeys::ARROW_RIGHT);
        static::assertSame([true, false], $this->hiddenPanels());
        static::assertSame($tabs[1]->getAttribute('id'), $this->client->executeScript('return document.activeElement.id;'));
        $tabs[1]->sendKeys(WebDriverKeys::HOME);
        static::assertSame([false, true], $this->hiddenPanels());

        // A required field on the hidden tab, emptied and sent (the demo runs without HTML5
        // validation, so the server answers with the form and its errors): the page opens on
        // its first tab, and `adminata-edit` brings the tab holding the error forward, marked.
        $tabs[1]->click();
        $this->client->findElement(WebDriverBy::cssSelector('input[id$="_variants_0_label"]'))->clear();
        $tabs[0]->click();
        static::assertSame([false, true], $this->hiddenPanels());

        $this->client->findElement(WebDriverBy::cssSelector('button[name="btn_update_and_edit"]'))->click();
        $this->client->waitFor('.adminata-field-error');

        static::assertSame([true, false], $this->hiddenPanels(), 'The tab holding the invalid field was not brought forward.');
        static::assertSame(
            [true, false],
            $this->client->executeScript('return [...document.querySelectorAll("[role=tab] [data-adminata-edit-target=errorMark]")].map((icon) => icon.hidden);'),
            'Only the tab with the error carries the mark.'
        );
        static::assertStringContainsString('/product/1/edit', $this->client->getCurrentURL());
        $this->assertConsoleIsEmpty('The tabs wrote to the browser console.');
    }

    public function testAStaleLockVersionIsReportedAsAFlash(): void
    {
        $this->client->request('GET', $this->url('/admin/tests/app/product/2/edit'));

        $this->client->executeScript(
            'document.querySelector(\'input[id$="__lock_version"]\').value = "0";'
        );
        $this->client->findElement(WebDriverBy::cssSelector('button[name="btn_update_and_edit"]'))->click();

        $flash = $this->client->waitFor('.alert-danger');

        static::assertStringContainsString('Another user has modified item', $flash->text());
        $this->assertConsoleIsEmpty('The lock error wrote to the browser console.');
    }

    /**
     * Whether `adminata-confirm-exit` would stop a navigation: it registers a `beforeunload`
     * listener that only cancels once the form differs from the snapshot it took.
     */
    /**
     * @return list<bool> `hidden` of every tab panel, in order
     */
    private function hiddenPanels(): array
    {
        $hidden = $this->client->executeScript('return [...document.querySelectorAll("[role=tabpanel]")].map((panel) => panel.hidden);');
        static::assertIsArray($hidden);

        return array_values(array_map(boolval(...), $hidden));
    }

    /**
     * What `adminata-masonry` wrote on the first packed grid of the page.
     *
     * @return array{rows: string, spans: list<string>, columns: list<string>}
     */
    private function packing(): array
    {
        $packed = $this->client->executeScript(<<<'JS'
            const grid = document.querySelector('[data-controller~="adminata-masonry"]');
            const items = [...grid.querySelectorAll('[data-adminata-masonry-target="item"]')];
            return {
                rows: grid.style.gridAutoRows,
                spans: items.map((item) => item.style.gridRowEnd),
                columns: items.map((item) => item.style.gridColumnStart),
            };
            JS);
        static::assertIsArray($packed);
        static::assertIsString($packed['rows'] ?? null);
        static::assertIsArray($packed['spans'] ?? null);
        static::assertIsArray($packed['columns'] ?? null);

        return [
            'rows' => $packed['rows'],
            'spans' => array_values(array_map(strval(...), $packed['spans'])),
            'columns' => array_values(array_map(strval(...), $packed['columns'])),
        ];
    }

    private function confirmExitIsArmed(): bool
    {
        return true === $this->client->executeScript(
            'const event = new Event("beforeunload", {cancelable: true});'
            .' window.dispatchEvent(event);'
            .' return event.defaultPrevented;'
        );
    }

    /**
     * Types into one of the page's comboboxes and takes the first suggestion.
     */
    private function chooseInTheCombobox(string $field, string $term): void
    {
        $input = $this->client->findElement(
            WebDriverBy::cssSelector(\sprintf('input[id$="_%s_autocomplete_input"]', $field))
        );

        $input->clear();
        $input->sendKeys($term);

        $this->client->waitForVisibility(\sprintf('[id$="_%s_listbox"] [role="option"]', $field));
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_DOWN);
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ENTER);
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
        return $this->client->findElement(WebDriverBy::cssSelector('.adminata-filters-box'))->isDisplayed();
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

    /** The sidebar button of the group with this label. */
    private function groupButton(string $label): WebDriverElement
    {
        return $this->client->findElement(WebDriverBy::xpath(\sprintf('//aside//button[.//span[text()="%s"]]', $label)));
    }

    /** The panel of the group with this label: on the collapsed rail, its popup. */
    private function popup(string $label): WebDriverElement
    {
        return $this->client->findElement(WebDriverBy::xpath(
            \sprintf('//aside//button[.//span[text()="%s"]]/following-sibling::ul[1]', $label)
        ));
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

    /**
     * @return list<string> the name of every link of the sidebar menu the filter leaves, in order
     */
    private function shownMenuLinks(): array
    {
        $links = $this->client->executeScript(
            'return [...document.querySelectorAll(".adm-sidebar nav a[href]")]'
            .'.filter((link) => null === link.closest("li[hidden]")).map((link) => link.textContent.trim());'
        );
        static::assertIsArray($links);

        return array_values(array_map(strval(...), $links));
    }

    /**
     * @return list<string> `aria-expanded` of every group in the sidebar menu, in order
     */
    private function groupStates(): array
    {
        $states = $this->client->executeScript(
            'return [...document.querySelectorAll(\'.adm-sidebar [data-adminata-menu-target="toggle"]\')]'
            .'.map((toggle) => toggle.getAttribute("aria-expanded"));'
        );
        static::assertIsArray($states);

        return array_values(array_map(strval(...), $states));
    }

    /**
     * @return list<string> the `href` of every link in the sidebar toolbar, in order
     */
    private function toolbarLinks(): array
    {
        $links = $this->client->executeScript(
            'return [...document.querySelectorAll(".adm-sidebar-toolbar a")].map((link) => link.getAttribute("href"));'
        );
        static::assertIsArray($links);

        return array_values(array_map(strval(...), $links));
    }

    /**
     * @return list<bool> `hidden` of every button in the sidebar toolbar, in order
     */
    private function toolbarButtonsHidden(): array
    {
        $hidden = $this->client->executeScript(
            'return [...document.querySelectorAll(".adm-sidebar-toolbar button")].map((button) => button.hidden);'
        );
        static::assertIsArray($hidden);

        return array_values(array_map(boolval(...), $hidden));
    }
}
