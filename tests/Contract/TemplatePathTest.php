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

namespace Adminata\Tests\Contract;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every template path adminata promises resolves (PLAN/02 §4).
 *
 * Three sources feed this. The template registry's defaults, which an application overrides by
 * key. Every `@Sonata…/….html.twig` string in the forked packages' PHP, which is what the builders
 * and field descriptions hand to Twig. And the paths `idct/sonata-admin-mongodb-bundle` hard-codes
 * — its `ListBuilder` names `@SonataAdmin/CRUD/list__action.html.twig` and
 * `list__action_%s.html.twig` directly, so renaming one of those breaks the fork silently.
 *
 * Resolution is by file path rather than through a Twig loader on purpose: the mapping from
 * `@SonataAdmin` to `packages/admin-bundle/src/Resources/views` is exactly what a bundle's name
 * and location produce, and checking it here needs no kernel.
 */
final class TemplatePathTest extends ContractTestCase
{
    /**
     * Twig namespace => the package directory whose `src/Resources/views` it points at.
     *
     * Four of the five share one directory. Adminata's own defaults all say `@SonataAdmin`;
     * `SonataBlockExtension`, `SonataFormExtension` and `SonataTwigExtension` each prepend a
     * `twig.paths` entry aliasing their namespace to that same directory, for templates outside
     * adminata that still address the pre-merge spellings.
     *
     * @var array<string, string>
     */
    private const array NAMESPACES = [
        'SonataAdmin' => 'admin-bundle',
        'SonataBlock' => 'admin-bundle',
        'SonataDoctrineORMAdmin' => 'doctrine-orm-admin-bundle',
        'SonataForm' => 'admin-bundle',
        'SonataTwig' => 'admin-bundle',
    ];

    /**
     * The namespaces that are aliases onto `admin-bundle`'s view directory rather than a bundle's
     * own name. A shipped file may not address any of them: see
     * `testNothingShippedAddressesAnAliasNamespace()`.
     *
     * @var list<string>
     */
    private const array ALIAS_NAMESPACES = ['SonataBlock', 'SonataForm', 'SonataTwig'];

    /**
     * What the MongoDB fork asks for by name. Its `ListBuilder` builds the second from a field
     * type, so the ones its own tests exercise are listed explicitly.
     *
     * @var list<string>
     */
    private const array MONGODB_FORK_PATHS = [
        '@SonataAdmin/CRUD/list__action.html.twig',
        '@SonataAdmin/CRUD/list__action_delete.html.twig',
        '@SonataAdmin/CRUD/list__action_edit.html.twig',
        '@SonataAdmin/CRUD/list__action_show.html.twig',
        '@SonataAdmin/Form/form_admin_fields.html.twig',
        '@SonataAdmin/Form/filter_admin_fields.html.twig',
        '@SonataAdmin/CRUD/Association/edit_many_to_one.html.twig',
        '@SonataAdmin/CRUD/Association/edit_many_to_many.html.twig',
        '@SonataAdmin/CRUD/Association/edit_one_to_many.html.twig',
    ];

    /**
     * Templates named in PHP that adminata does not ship. The first is an upstream dangling
     * default — `templates.outer_list_rows_tree` points at a file `sonata-project/admin-bundle`
     * 4.43.0 does not contain, which is why the tree list mode is broken there; adminata defers
     * that mode entirely (PLAN/03 §E). The rest belong to other Sonata packages an application may
     * install alongside.
     *
     * @var list<string>
     */
    private const array NOT_SHIPPED = [
        '@SonataAdmin/CRUD/list_outer_rows_tree.html.twig',
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideATemplateNamedInPhpResolvesCases(): iterable
    {
        foreach (self::referencedTemplates() as $template) {
            yield $template => [$template];
        }
    }

    #[DataProvider('provideATemplateNamedInPhpResolvesCases')]
    public function testATemplateNamedInPhpResolves(string $template): void
    {
        static::assertFileExists(self::resolve($template), \sprintf('%s is named in PHP but does not exist.', $template));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAPathTheMongoDbForkHardCodesResolvesCases(): iterable
    {
        foreach (self::MONGODB_FORK_PATHS as $template) {
            yield $template => [$template];
        }
    }

    #[DataProvider('provideAPathTheMongoDbForkHardCodesResolvesCases')]
    public function testAPathTheMongoDbForkHardCodesResolves(string $template): void
    {
        static::assertFileExists(self::resolve($template), \sprintf(
            '%s is hard-coded in idct/sonata-admin-mongodb-bundle; renaming it breaks that package.',
            $template
        ));
    }

    public function testEveryNamespaceHasItsViewDirectory(): void
    {
        foreach (self::NAMESPACES as $namespace => $package) {
            static::assertDirectoryExists(
                \sprintf('%s/packages/%s/src/Resources/views', self::root(), $package),
                \sprintf('The @%s namespace has no view directory.', $namespace)
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNothingShippedAddressesAnAliasNamespaceCases(): iterable
    {
        foreach (self::ALIAS_NAMESPACES as $namespace) {
            yield '@'.$namespace => [$namespace];
        }
    }

    /**
     * Adminata addresses every template it ships as `@SonataAdmin/…`, which is what makes it
     * overridable under `templates/bundles/SonataAdminBundle/` like any other bundle template.
     * `@SonataBlock`, `@SonataForm` and `@SonataTwig` are plain `twig.paths` aliases onto the same
     * directory, with no override directory of their own, kept for templates outside adminata that
     * still address the pre-merge spellings; a shipped file that used one would bypass an
     * application's override in silence.
     */
    #[DataProvider('provideNothingShippedAddressesAnAliasNamespaceCases')]
    public function testNothingShippedAddressesAnAliasNamespace(string $namespace): void
    {
        $offenders = [];

        foreach (self::shippedSources() as $file) {
            if (str_contains((string) file_get_contents($file), '@'.$namespace.'/')) {
                $offenders[] = substr($file, \strlen(self::root()) + 1);
            }
        }

        static::assertSame([], $offenders, \sprintf(
            'Shipped code addresses @%s/ instead of @SonataAdmin/.',
            $namespace
        ));
    }

    /**
     * A rewritten template keeps its file name (PLAN/02 §4), so the count only moves when a
     * template is genuinely added or removed.
     *
     * Counted per view directory rather than per namespace, because four of the five namespaces
     * share one and it must not be counted four times.
     */
    public function testTheNumberOfTemplatesIsWhatThePlanCounts(): void
    {
        $found = 0;

        foreach (array_unique(self::NAMESPACES) as $package) {
            $directory = \sprintf('%s/packages/%s/src/Resources/views', self::root(), $package);
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                static::assertInstanceOf(\SplFileInfo::class, $file);

                if ('twig' === $file->getExtension()) {
                    ++$found;
                }
            }
        }

        // 148 at import (PLAN/00), plus `Core/list_mode_buttons.html.twig`, which PLAN/03 §F asks
        // for — `standard_layout` and `ajax_layout` had the same switcher twice, which is how they
        // drifted apart — plus `Form/Type/sonata_type_model_list.html.twig`, the unported
        // `ModelListType` widget P4-03 lifted out of the form theme.
        static::assertSame(150, $found, 'The forked packages ship 150 templates.');
    }

    /**
     * Every PHP file and Twig template the packages ship.
     *
     * @return iterable<string>
     */
    private static function shippedSources(): iterable
    {
        $sources = glob(self::root().'/packages/*/src', \GLOB_ONLYDIR);

        if (false === $sources) {
            return;
        }

        foreach ($sources as $src) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if (\in_array($file->getExtension(), ['php', 'twig'], true)) {
                    yield $file->getPathname();
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function referencedTemplates(): array
    {
        $found = [];

        foreach (self::directories(self::root().'/packages/*/src') as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                Assert::assertInstanceOf(\SplFileInfo::class, $file);

                if ('php' !== $file->getExtension()) {
                    continue;
                }

                preg_match_all(
                    '#@(Sonata[A-Za-z]+)/([A-Za-z0-9_/.-]+\.html\.twig)#',
                    (string) file_get_contents($file->getPathname()),
                    $matches
                );

                foreach ($matches[0] as $index => $template) {
                    // Only the five namespaces adminata owns; `@SonataIntl`, `@SonataPage`,
                    // `@SonataSeo` and `@SonataUser` are optional integrations with packages
                    // adminata does not ship.
                    if (!isset(self::NAMESPACES[$matches[1][$index]])) {
                        continue;
                    }

                    if (\in_array($template, self::NOT_SHIPPED, true)) {
                        continue;
                    }

                    $found[$template] = true;
                }
            }
        }

        $templates = array_keys($found);
        sort($templates);

        return $templates;
    }

    private static function resolve(string $template): string
    {
        if (1 !== preg_match('#^@(Sonata[A-Za-z]+)/(.+)$#', $template, $matches)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a @SonataXxx/path.html.twig reference.', $template));
        }

        static::assertArrayHasKey($matches[1], self::NAMESPACES, \sprintf('Unknown Twig namespace @%s.', $matches[1]));

        return \sprintf('%s/packages/%s/src/Resources/views/%s', self::root(), self::NAMESPACES[$matches[1]], $matches[2]);
    }
}
