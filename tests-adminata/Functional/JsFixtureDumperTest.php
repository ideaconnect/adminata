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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The JavaScript suites test against the markup the templates actually render (PLAN/05 §9).
 *
 * A Stimulus controller is a contract with a template: identifiers, targets, actions and values
 * live in the markup, and a rewrite that renames one of them breaks the controller silently —
 * nothing in PHP looks at `data-adminata-batch-target`. So the demo's pages are dumped here and the
 * Vitest suites mount their controllers against those files rather than against markup written
 * beside the test, which drifts.
 *
 * The dump is deterministic once two things are normalised: the admin `uniqid`, which is random
 * per request and appears in every field id, and the CSRF token.
 *
 * Re-dump with `make js-fixtures` after a template changes, and commit the result.
 */
final class JsFixtureDumperTest extends WebTestCase
{
    /**
     * One page per group of controllers, chosen so that every controller with a 1.0 page appears
     * in at least one of them.
     *
     * @var array<string, string>
     */
    private const array PAGES = [
        // adminata-layout, -menu, -theme, -dropdown, -sticky (navbar)
        'dashboard' => '/admin/dashboard',
        // adminata-batch, -filter, -filter-list, -per-page, -readmore, -dropdown (export)
        'product-list' => '/admin/tests/app/product/list',
        // adminata-collection, -edit, -confirm-exit, -sticky (action bar), -autocomplete (form)
        'product-create' => '/admin/tests/app/product/create',
        // adminata-collection with rows in it, which a create page has none of
        'product-edit' => '/admin/tests/app/product/1/edit',
        // adminata-autocomplete in its filter context
        'category-list' => '/admin/tests/app/category/list?filter%5Bproducts%5D%5Bvalue%5D=1',
        // adminata-modal
        'dialog' => '/admin/demo/dialog',
        // adminata-reveal
        'reveal' => '/admin/demo/reveal',
    ];

    private string $timezone = 'UTC';

    protected function setUp(): void
    {
        parent::setUp();

        // A `<time datetime>` carries the offset of the ambient default timezone, and the suites of
        // the seven packages set one while testing timezone handling. Whatever ran before this must
        // not decide what the fixture says.
        $this->timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timezone);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideTheFixtureIsWhatTheDemoRendersCases(): iterable
    {
        foreach (self::PAGES as $name => $path) {
            yield $name => [$name, $path];
        }
    }

    #[DataProvider('provideTheFixtureIsWhatTheDemoRendersCases')]
    public function testTheFixtureIsWhatTheDemoRenders(string $name, string $path): void
    {
        $client = self::createClient();
        $client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => 'admin']);
        $client->request('GET', $path);

        static::assertResponseIsSuccessful();

        $rendered = self::normalise((string) $client->getResponse()->getContent());
        $file = \dirname(__DIR__).'/fixtures/js/'.$name.'.html';

        if ('1' === getenv('ADMINATA_UPDATE_JS_FIXTURES')) {
            file_put_contents($file, $rendered);
        }

        static::assertFileExists($file, \sprintf('Dump it with `make js-fixtures`: %s', $file));
        static::assertSame(
            (string) file_get_contents($file),
            $rendered,
            \sprintf('The %s fixture is stale. Re-dump it with `make js-fixtures` and commit it.', $name)
        );
    }

    /**
     * What changes between two renders of the same page: the admin's `uniqid`, which prefixes
     * every field id; the identifier a block gives its wrapper; the two CSRF tokens; and the
     * cache-busting stamp of the built assets.
     *
     * The `uniqid` pattern cannot use `\b` on its right — an id like `sabc123_name` continues into
     * a word character — so it asserts the next character is not another hex digit instead.
     */
    private static function normalise(string $html): string
    {
        return (string) preg_replace(
            [
                '/(?<![0-9a-z])s[0-9a-f]{13}(?![0-9a-f])/',
                '/(?<=cms-block-)[0-9a-f]{20,}/',
                '/(name="_adminata_csrf_token" value=")[^"]*(")/',
                '/(data-controller="csrf-protection" value=")[^"]*(")/',
                // `BrowserConsoleRecorderListener` belongs to the browser tests, not to what an
                // application renders, and a fixture that carried it would say otherwise.
                '#<script data-adminata-console-recorder>.*?</script>#s',
                // The cache-busting stamp is the built file's mtime, so it moves with every
                // `make assets-build` and says nothing about the markup.
                '/(?<=\?v=)\d+/',
            ],
            ['sfixture00000', 'fixture', '$1fixture-csrf-token$2', '$1fixture-form-token$2', '', 'fixture'],
            $html
        );
    }
}
