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

use Adminata\Tests\App\Entity\Category;
use Adminata\Tests\App\Entity\Product;
use Adminata\Tests\App\Enum\ProductStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * The demo application answers on every page 1.0 ships.
 *
 * This is the cheap half of the browser testing of PLAN/08: BrowserKit, no driver, no assets, so
 * it runs everywhere and fails fast when a template stops compiling. The Playwright, axe and
 * html-validate runs of P1-10 cover what a real browser sees.
 */
final class DemoSmokeTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function provideThePageAnswersCases(): iterable
    {
        yield 'dashboard' => ['/admin/dashboard'];
        yield 'product list' => ['/admin/tests/app/product/list'];
        yield 'product create' => ['/admin/tests/app/product/create'];
        yield 'category list' => ['/admin/tests/app/category/list'];
        yield 'category create' => ['/admin/tests/app/category/create'];
        yield 'tag list' => ['/admin/tests/app/tag/list'];
        yield 'variant list' => ['/admin/tests/app/productvariant/list'];
        yield 'product show' => ['/admin/tests/app/product/1/show'];
        yield 'search' => ['/admin/search?q=Product'];
    }

    #[DataProvider('provideThePageAnswersCases')]
    public function testThePageAnswers(string $path): void
    {
        $client = self::browser();
        $client->request('GET', $path);

        static::assertResponseIsSuccessful();
    }

    public function testTheDashboardListsBothAdminsUnderTheirGroups(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/dashboard');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('Catalogue', $crawler->html());
        static::assertStringContainsString('Taxonomy', $crawler->html());
    }

    /**
     * The icons of PLAN/08 §2 are raw `<i>` markup. An escaped icon is a real regression: it is
     * what recomaty-panel writes, and M2's sidebar rewrite has to keep rendering it unescaped.
     */
    public function testTheGroupIconsAreRenderedAsMarkup(): void
    {
        $client = self::browser();
        $client->request('GET', '/admin/dashboard');

        $html = (string) $client->getResponse()->getContent();

        static::assertStringContainsString('<i class="fa-solid fa-box"></i>', $html);
        static::assertStringNotContainsString('&lt;i class="fa-solid fa-box"&gt;', $html);
    }

    public function testTheSidebarCarriesTheInjectedSectionHeaders(): void
    {
        $client = self::browser();
        $client->request('GET', '/admin/dashboard');

        $html = (string) $client->getResponse()->getContent();

        static::assertStringContainsString('sidebar-section-header', $html);
        static::assertStringContainsString('Shop', $html);
    }

    public function testTheListPaginatesAndFilters(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/list');

        static::assertResponseIsSuccessful();
        static::assertGreaterThan(0, $crawler->filter('table.adminata-list tbody tr')->count());

        $client->request('GET', '/admin/tests/app/product/list?filter[sku][value]=SKU-0007');

        static::assertResponseIsSuccessful();
        static::assertStringContainsString('SKU-0007', (string) $client->getResponse()->getContent());
    }

    public function testAnObjectCanBeShownAndEdited(): void
    {
        $client = self::browser();
        $id = self::firstProductId($client);

        $client->request('GET', \sprintf('/admin/tests/app/product/%d/show', $id));
        static::assertResponseIsSuccessful();

        $client->request('GET', \sprintf('/admin/tests/app/product/%d/edit', $id));
        static::assertResponseIsSuccessful();
    }

    public function testACategoryCanBeCreatedThroughTheForm(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/category/create');

        static::assertResponseIsSuccessful();

        $form = $crawler->selectButton('btn_create_and_edit')->form();

        $client->submit($form, self::fields($form, [
            '[name]' => 'Created by the smoke test',
            '[description]' => 'Written through the real form.',
            '[active]' => '1',
        ]));

        static::assertResponseRedirects();

        $client->followRedirect();
        static::assertResponseIsSuccessful();

        $repository = self::entityManager($client)->getRepository(Category::class);
        static::assertNotNull($repository->findOneBy(['name' => 'Created by the smoke test']));

        // The flash the redirect carries: `alert alert-success` is the contract (PLAN/02 §8),
        // `adm-alert-success` the recipe that styles it.
        $html = (string) $client->getResponse()->getContent();

        static::assertStringContainsString('alert alert-success', $html);
        static::assertStringContainsString('adm-alert-success', $html);
        static::assertStringContainsString('data-controller="adminata-dismiss"', $html);
    }

    /**
     * An `X-Requested-With` request gets a fragment, not a page: no `<html>`, no shell, and the
     * list markup an application's own script parses out of it (PLAN/03 §A row 2).
     */
    public function testAnXhrListIsAFragment(): void
    {
        $client = self::browser();
        $client->request('GET', '/admin/tests/app/product/list', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        static::assertResponseIsSuccessful();

        $html = (string) $client->getResponse()->getContent();

        static::assertStringNotContainsString('<html', $html);
        static::assertStringNotContainsString('main-sidebar', $html);
        static::assertStringContainsString('adminata-list', $html);
    }

    /**
     * A page that empties `logo` and `adminata_nav` gets no header bar at all (PLAN/01 T9). The
     * demo's login page is written the way recomaty-panel writes its own.
     */
    public function testTheLoginPageHasNoHeaderBar(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('header.main-header'));
        static::assertCount(0, $crawler->filter('aside.main-sidebar'));
        static::assertCount(1, $crawler->filter('form input[name="_username"]'));
    }

    /**
     * The batch flow, both halves: the list posts to `/batch`, which answers with the confirmation
     * page, and that page posts back with `confirmation=ok` and the payload it was given.
     */
    public function testABatchActionAsksForConfirmationAndThenRuns(): void
    {
        $client = self::browser();
        $manager = self::entityManager($client);
        $products = $manager->getRepository(Product::class)->findBy([], ['id' => 'ASC'], 2);
        $ids = array_map(static fn (Product $product): ?int => $product->getId(), $products);

        $crawler = $client->request('GET', '/admin/tests/app/product/list');
        $token = $crawler->filter('input[name="_adminata_csrf_token"]')->attr('value');

        $crawler = $client->request('POST', '/admin/tests/app/product/batch', [
            'action' => 'delete',
            'idx' => array_map(strval(...), $ids),
            '_adminata_csrf_token' => $token,
        ]);

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.adminata-delete'));

        $form = $crawler->filter('.adminata-delete form')->form();
        static::assertSame('ok', $form->getValues()['confirmation'] ?? null);

        $client->submit($form);
        static::assertResponseRedirects();
        $client->followRedirect();

        $manager->clear();
        foreach ($ids as $id) {
            static::assertNull($manager->getRepository(Product::class)->find($id));
        }
    }

    /**
     * Every list cell type the application uses reaches the page (appendix C §2), and both shapes
     * of custom cell template: one extending `base_list_field`, one writing its own `<td>`.
     */
    public function testTheListRendersEveryCellType(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/list');

        foreach ([
            'string', 'integer', 'boolean', 'enum', 'date', 'time', 'datetime',
            'array', 'html', 'textarea', 'many_to_one', 'many_to_many', 'actions',
        ] as $type) {
            static::assertGreaterThan(
                0,
                $crawler->filter('td.adminata-list-field-'.$type)->count(),
                \sprintf('No cell of type "%s" on the product list.', $type)
            );
        }

        // The template that extends the envelope keeps it; the one that writes its own `<td>`
        // still carries the classes and the `objectId` an application's script reads.
        static::assertGreaterThan(0, $crawler->filter('td.adminata-list-field-integer .adm-badge')->count());
        static::assertGreaterThan(0, $crawler->filter('td.demo-specification[objectId] dl dt')->count());

        // `header_class` and `row_align`, and the sortable column with a `sort_field_mapping`.
        static::assertCount(1, $crawler->filter('th.text-right'));
        static::assertGreaterThan(0, $crawler->filter('td[style="text-align:right"]')->count());
        static::assertCount(
            1,
            $crawler->filter('th.adminata-list-field-header-many_to_one a[href*="_sort_by%5D=category"]')
        );

        // `sort_field_mapping` orders by the association's `name`, not by its identifier: the
        // categories are Beverages(1), Snacks(2), Household(3), Discontinued(4), so descending
        // gives Snacks by name and Discontinued by id.
        $crawler = $client->request(
            'GET',
            '/admin/tests/app/product/list?filter%5B_sort_by%5D=category&filter%5B_sort_order%5D=DESC'
        );

        static::assertStringContainsString(
            'Snacks',
            $crawler->filter('table.adminata-list tbody tr')->first()->filter('td.adminata-list-field-many_to_one')->text()
        );
    }

    /**
     * The `templates.list` override adds to `list_after_table` and changes nothing else.
     */
    public function testTheTemplatesListOverrideRendersAfterTheTable(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/list');

        $summary = $crawler->filter('#product-list-summary');

        static::assertCount(1, $summary);
        static::assertStringContainsString('42 products', $summary->text());
    }

    /**
     * A ux-autocomplete select is emitted with its `data-controller` untouched; the theme only
     * appends its own class (PLAN/06 §1, PLAN/05 R4).
     */
    public function testAUxAutocompleteSelectIsLeftAlone(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/list');

        $select = $crawler->filter('select[data-controller="symfony--ux-autocomplete--autocomplete"]');

        static::assertCount(1, $select);
        static::assertSame('adm-select', $select->attr('class'));
    }

    /**
     * The custom row action: a route the admin added, reached from a template of the demo's own.
     */
    public function testTheCustomRowActionArchivesOneProduct(): void
    {
        $client = self::browser();
        $id = self::firstProductId($client);

        $crawler = $client->request('GET', '/admin/tests/app/product/list');
        static::assertGreaterThan(0, $crawler->filter('a.archive_link')->count());

        $client->request('GET', \sprintf('/admin/tests/app/product/%d/archive', $id));
        static::assertResponseRedirects();

        $manager = self::entityManager($client);
        $manager->clear();
        $product = $manager->getRepository(Product::class)->find($id);

        static::assertInstanceOf(Product::class, $product);
        static::assertTrue($product->isArchived());
    }

    /**
     * The custom batch action, through the same confirmation page as delete.
     */
    public function testTheCustomBatchActionArchivesTheSelection(): void
    {
        $client = self::browser();
        $manager = self::entityManager($client);
        $products = $manager->getRepository(Product::class)->findBy(['archived' => false], ['id' => 'ASC'], 3);
        $ids = array_map(static fn (Product $product): ?int => $product->getId(), $products);

        $crawler = $client->request('GET', '/admin/tests/app/product/list');
        $token = $crawler->filter('input[name="_adminata_csrf_token"]')->attr('value');

        $crawler = $client->request('POST', '/admin/tests/app/product/batch', [
            'action' => 'archive',
            'idx' => array_map(strval(...), $ids),
            '_adminata_csrf_token' => $token,
        ]);

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.adminata-delete'));

        $client->submit($crawler->filter('.adminata-delete form')->form());
        static::assertResponseRedirects();

        $manager->clear();
        foreach ($ids as $id) {
            $product = $manager->getRepository(Product::class)->find($id);

            static::assertInstanceOf(Product::class, $product);
            static::assertTrue($product->isArchived(), \sprintf('Product %d was not archived.', (int) $id));
        }
    }

    /**
     * `configureExportFields` decides the columns, and only those.
     */
    public function testTheExportCarriesTheConfiguredColumns(): void
    {
        $client = self::browser();
        $client->request('GET', '/admin/tests/app/product/export?format=csv');

        $response = $client->getResponse();
        static::assertTrue($response->isSuccessful());

        $csv = $client->getInternalResponse()->getContent();
        $lines = explode("\n", trim($csv));

        static::assertSame('Id,Name,Sku,Price,Status,Stock,Category', $lines[0]);
        static::assertCount(43, $lines, 'Every product should be exported, plus the header.');
    }

    /**
     * `persist_filters`: a submitted filter is put back on the next plain visit to the list.
     */
    public function testASubmittedFilterIsRememberedForTheNextVisit(): void
    {
        $client = self::browser();

        $client->request('GET', '/admin/tests/app/product/list?filter%5Bsku%5D%5Bvalue%5D=SKU-0007');
        static::assertCount(1, $client->getCrawler()->filter('table.adminata-list tbody tr'));

        $crawler = $client->request('GET', '/admin/tests/app/product/list');

        static::assertCount(
            1,
            $crawler->filter('table.adminata-list tbody tr'),
            'The filter was not restored from the session.'
        );

        // And `filters=reset` clears it again, which is what the reset button links to.
        $crawler = $client->request('GET', '/admin/tests/app/product/list?filters=reset');

        static::assertGreaterThan(1, $crawler->filter('table.adminata-list tbody tr')->count());
    }

    /**
     * The child list an application fetches into an accordion: filtered by its parent, requested
     * with `X-Requested-With`, and still parseable as `table.adminata-list`.
     */
    public function testAChildListIsFetchedAsAFragment(): void
    {
        $client = self::browser();
        $id = self::firstProductId($client);

        $crawler = $client->request(
            'GET',
            \sprintf('/admin/tests/app/productvariant/list?filter%%5Bproduct%%5D%%5Bvalue%%5D=%d', $id),
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );

        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('<html', (string) $client->getResponse()->getContent());
        static::assertCount(2, $crawler->filter('table.adminata-list tbody tr'));
    }

    /**
     * `->remove(ListMapper::NAME_BATCH)`: a list with no checkbox column, and a footer that does
     * not offer an action for a selection that cannot exist.
     */
    public function testAListCanDropItsBatchColumn(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/tag/list');

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('td.adminata-list-field-batch'));
        static::assertCount(0, $crawler->filter('input[name="idx[]"]'));
        static::assertCount(4, $crawler->filter('table.adminata-list tbody tr'));
    }

    /**
     * The widgets the product form does not have (appendix C §2 "Forms"): every one of them comes
     * out with adminata's recipe *appended* to whatever the application asked for.
     */
    public function testTheFormWidgetsCarryTheirRecipesAndNothingElseChanges(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/category/create');

        static::assertResponseIsSuccessful();

        foreach ([
            'input[type="email"]' => 'adm-input',
            'input[type="number"]' => 'adm-input',
            'input[type="password"]' => 'adm-input',
            'input[type="file"]' => 'adm-file',
            'input[type="checkbox"]' => 'adm-checkbox',
            'select' => 'adm-select',
            'textarea' => 'adm-textarea',
        ] as $selector => $recipe) {
            $field = $crawler->filter($selector)->first();

            static::assertGreaterThan(0, $field->count(), \sprintf('No %s on the category form.', $selector));
            static::assertStringContainsString($recipe, (string) $field->attr('class'));
        }

        // A file field makes the form multipart, and a password field an administrator fills in is
        // someone else's — so the browser must not offer their own.
        static::assertCount(1, $crawler->filter('form[enctype="multipart/form-data"]'));
        static::assertSame(
            'new-password',
            $crawler->filter('input[type="password"]')->attr('autocomplete')
        );

        // `help_html` is the one place the theme may not escape the help text.
        static::assertCount(1, $crawler->filter('.adminata-field-help strong'));

        // And an attribute the application set is untouched.
        static::assertCount(1, $crawler->filter('select[data-controller="app--visibility"]'));
    }

    /**
     * The group `class` reaches the page verbatim, which is what an application's two-column form
     * depends on.
     */
    public function testAFormGroupKeepsTheClassTheAdminGaveIt(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/category/create');

        static::assertCount(1, $crawler->filter('.col-span-12.xl\\:col-span-8 .adm-card'));
        static::assertCount(1, $crawler->filter('.col-span-12.xl\\:col-span-4 .adm-card'));
    }

    /**
     * A tab on the `masonry` layout renders its groups as items of one `adminata-masonry` grid —
     * equal columns the controller packs by height; a tab on the default layout keeps the
     * twelve-column grid, one cell per group.
     */
    public function testAMasonryTabRendersItsGroupsAsItemsOfOnePackedGrid(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/1/edit');

        $grid = $crawler->filter('form [data-controller~="adminata-masonry"]');
        static::assertCount(1, $grid);
        static::assertStringContainsString('xl:grid-cols-3', (string) $grid->attr('class'));

        $items = $grid->filter('[data-adminata-masonry-target="item"]');
        static::assertCount(4, $items);
        static::assertSame(
            ['Details', 'Pricing', 'Taxonomy', 'Publication'],
            $items->filter('.adm-card-title')->each(static fn (Crawler $title): string => trim($title->text()))
        );

        $plain = $crawler->filter('form .grid.grid-cols-12');
        static::assertCount(1, $plain);
        static::assertSame(['Variants'], $plain->filter('.adm-card-title')->each(static fn (Crawler $title): string => trim($title->text())));
    }

    /**
     * A tabbed form is the WAI-ARIA tabs pattern: a tablist of tab links, each naming its panel,
     * the first selected and the others hidden, every panel labelled by its tab — and the groups
     * inside a panel keep the `<h2>` a group has anywhere else, because a tab is a control, not a
     * heading. `adminata-edit` rides on the same links for the error icon and the `_tab` store.
     */
    public function testATabbedFormRendersTheAriaTabsPattern(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/1/edit');

        $tabs = $crawler->filter('form [data-controller~="adminata-tabs"]');
        static::assertCount(1, $tabs);

        // Links, not buttons: an application's read-only lock is a disabled <fieldset> around
        // the tab content, and a disabled button would strand every panel but the first.
        $buttons = $tabs->filter('[role="tablist"] > a[role="tab"]');
        static::assertSame(['Product', 'Variants'], $buttons->each(static fn (Crawler $tab): string => trim($tab->text())));
        static::assertSame(['true', 'false'], $buttons->each(static fn (Crawler $tab): string => (string) $tab->attr('aria-selected')));
        static::assertSame(['0', '-1'], $buttons->each(static fn (Crawler $tab): string => (string) $tab->attr('tabindex')));
        static::assertSame('#'.$buttons->first()->attr('aria-controls'), $buttons->first()->attr('href'));
        static::assertStringContainsString('adminata-edit#changeTab', (string) $buttons->first()->attr('data-action'));
        static::assertCount(2, $tabs->filter('a[role="tab"][data-adminata-edit-target="tab"] [data-adminata-edit-target="errorMark"][hidden]'));

        $panels = $tabs->filter('[role="tabpanel"]');
        static::assertCount(2, $panels);
        static::assertSame(
            $buttons->each(static fn (Crawler $tab): string => (string) $tab->attr('aria-controls')),
            $panels->each(static fn (Crawler $panel): string => (string) $panel->attr('id'))
        );
        static::assertSame(
            $buttons->each(static fn (Crawler $tab): string => (string) $tab->attr('id')),
            $panels->each(static fn (Crawler $panel): string => (string) $panel->attr('aria-labelledby'))
        );
        static::assertNull($panels->first()->attr('hidden'));
        static::assertNotNull($panels->last()->attr('hidden'));
        static::assertCount(0, $crawler->filter('form h3.adm-card-title'));
        static::assertCount(5, $crawler->filter('form h2.adm-card-title'));
        static::assertCount(1, $crawler->filter('form input[name="_tab"][data-adminata-edit-target="tabStore"]'));
    }

    /**
     * `?_tab=` selects a tab by its index, whatever uniqid the id carries: the redirect after a
     * save sends the index under a uniqid the next request does not have.
     */
    public function testTheAddressSelectsATabByItsIndex(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/1/edit?_tab=tab_sSOMETHINGELSE_2');

        $buttons = $crawler->filter('form [role="tablist"] > a[role="tab"]');
        static::assertSame(['false', 'true'], $buttons->each(static fn (Crawler $tab): string => (string) $tab->attr('aria-selected')));

        $panels = $crawler->filter('form [role="tabpanel"]');
        static::assertNotNull($panels->first()->attr('hidden'));
        static::assertNull($panels->last()->attr('hidden'));
    }

    /**
     * The show page takes the same tabs and the same layouts, on the same options.
     */
    public function testAMasonryShowTabRendersItsGroupsAsItemsOfOnePackedGrid(): void
    {
        $client = self::browser();
        $crawler = $client->request('GET', '/admin/tests/app/product/1/show');

        $tabs = $crawler->filter('.adminata-view [data-controller~="adminata-tabs"]');
        static::assertCount(1, $tabs);
        static::assertSame(
            ['Product', 'Content'],
            $tabs->filter('[role="tablist"] > a[role="tab"]')->each(static fn (Crawler $tab): string => trim($tab->text()))
        );

        $grid = $crawler->filter('.adminata-view [data-controller~="adminata-masonry"]');
        static::assertCount(1, $grid);

        $items = $grid->filter('[data-adminata-masonry-target="item"]');
        static::assertSame(
            ['Product', 'Availability'],
            $items->filter('.adm-card-title')->each(static fn (Crawler $title): string => trim($title->text()))
        );
        // Every field is still rendered, in its group, on one tab or the other.
        static::assertCount(12, $grid->filter('tr.adminata-view-container'));
        static::assertCount(15, $crawler->filter('.adminata-view tr.adminata-view-container'));
    }

    /**
     * The enum column reaches the list as an enum, not as its backing string: a list field that
     * calls `->value` on a string is a crash the type map is supposed to prevent.
     */
    public function testTheEnumColumnRoundTrips(): void
    {
        $client = self::browser();
        $product = self::entityManager($client)->getRepository(Product::class)->findOneBy(['sku' => 'SKU-0001']);

        static::assertInstanceOf(Product::class, $product);
        static::assertContains($product->getStatus(), ProductStatus::cases());
    }

    /**
     * Sonata prefixes every field with the admin's uniqid, which changes on every request, so
     * the test matches on the suffix instead of hard-coding a name it cannot know.
     *
     * @param array<string, string> $values suffix (`[name]`) => value
     *
     * @return array<string, string>
     */
    private static function fields(Form $form, array $values): array
    {
        $filled = [];

        foreach ($values as $suffix => $value) {
            $matches = array_values(array_filter(
                array_keys($form->getValues()),
                static fn (string $field): bool => str_ends_with($field, $suffix)
            ));

            static::assertCount(1, $matches, \sprintf('Expected exactly one form field ending in "%s".', $suffix));

            $filled[$matches[0]] = $value;
        }

        return $filled;
    }

    private static function browser(): KernelBrowser
    {
        $client = self::createClient();
        $client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);

        return $client;
    }

    private static function entityManager(KernelBrowser $client): EntityManagerInterface
    {
        $registry = $client->getContainer()->get('doctrine');
        static::assertInstanceOf(ManagerRegistry::class, $registry);

        $manager = $registry->getManager();
        static::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private static function firstProductId(KernelBrowser $client): int
    {
        $product = self::entityManager($client)->getRepository(Product::class)->findOneBy(['sku' => 'SKU-0001']);

        static::assertInstanceOf(Product::class, $product);

        $id = $product->getId();
        static::assertNotNull($id);

        return $id;
    }
}
