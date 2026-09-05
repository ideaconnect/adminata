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
        static::assertGreaterThan(0, $crawler->filter('table.sonata-ba-list tbody tr')->count());

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
        static::assertStringContainsString('data-controller="sonata-dismiss"', $html);
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
