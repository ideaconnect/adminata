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

/**
 * adminata names every Font Awesome icon by its Font Awesome 7 name (`fa-circle-plus`), never by
 * an alias the font keeps from an older version (`fa-plus-circle`) — owner directive of
 * 2026-10-08, the same rule recomaty-panel follows.
 *
 * An alias still draws the glyph, so no screen and no other test shows a slip; this is what does.
 * The aliases are read from the stylesheet adminata ships, where each icon is one rule whose
 * FIRST selector is the canonical name and the rest are its aliases
 * (`.fa-circle-plus,.fa-plus-circle{--fa:"\f055"}`). The stylesheet keeps them, so an
 * application's own older names still resolve.
 *
 * Scanned: everything under `src/` but the built output (`src/Resources/public`, whose
 * `fontawesome.css` is the alias list itself), the UI sources in `assets/`, the demo application
 * and the documentation. Not scanned: the inherited suite under `tests/`, whose icon names are
 * arbitrary input to the icon parsers, and the plans and changelogs, which are history.
 */
final class IconNameTest extends ContractTestCase
{
    /** Directories scanned, relative to the repository root. */
    private const array SCANNED = ['src', 'assets', 'tests-adminata/App', 'docs'];

    /** Never scanned, relative to the repository root. */
    private const array SKIPPED = ['src/Resources/public', 'tests-adminata/App/var', 'tests-adminata/App/public'];

    /** Extensions of the files that can name an icon. */
    private const array EXTENSIONS = ['php', 'twig', 'xliff', 'js', 'mjs', 'css', 'yaml', 'yml', 'rst'];

    public function testNoIconIsNamedByAnAlias(): void
    {
        $aliases = self::aliases();
        // Proves the stylesheet was read the way this test expects, so the scan cannot pass empty.
        static::assertSame('circle-plus', $aliases['plus-circle'] ?? null, 'The shipped stylesheet no longer lists the aliases after the canonical name.');

        $root = self::root();
        $found = [];

        foreach (self::files() as $path) {
            $lines = file($root.'/'.$path);
            static::assertIsArray($lines, \sprintf('Could not read "%s".', $path));

            foreach ($lines as $number => $line) {
                preg_match_all('~(?<![\w-])fa-([a-z0-9]+(?:-[a-z0-9]+)*)~', $line, $matches);

                foreach ($matches[1] as $name) {
                    if (isset($aliases[$name])) {
                        $found[] = \sprintf('%s:%d: fa-%s → fa-%s', $path, $number + 1, $name, $aliases[$name]);
                    }
                }
            }
        }

        static::assertSame([], $found, 'Name the icon by its Font Awesome 7 name.');
    }

    /**
     * Every alias in the shipped Font Awesome, mapped to its canonical name.
     *
     * @return array<string, string>
     */
    private static function aliases(): array
    {
        $path = self::root().'/src/Resources/public/fontawesome.css';
        static::assertFileExists($path, 'adminata ships no Font Awesome stylesheet to check against.');

        preg_match_all('~((?:\.fa-[a-z0-9-]+,)*\.fa-[a-z0-9-]+)\{--fa:~', (string) file_get_contents($path), $rules);

        $aliases = [];

        foreach ($rules[1] as $selectors) {
            $names = explode(',.fa-', substr($selectors, \strlen('.fa-')));
            $canonical = array_shift($names);

            foreach ($names as $alias) {
                $aliases[$alias] = $canonical;
            }
        }

        return $aliases;
    }

    /**
     * The scanned files, as sorted repository-relative paths.
     *
     * @return list<string>
     */
    private static function files(): array
    {
        $root = self::root();
        $found = [];

        foreach (self::SCANNED as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root.'/'.$directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    throw new \RuntimeException(\sprintf('Could not read the contents of "%s".', $directory));
                }

                $path = substr($file->getPathname(), \strlen($root) + 1);

                if (!$file->isFile() || !\in_array($file->getExtension(), self::EXTENSIONS, true) || self::isSkipped($path)) {
                    continue;
                }

                $found[] = $path;
            }
        }

        sort($found);

        return $found;
    }

    private static function isSkipped(string $path): bool
    {
        return array_any(self::SKIPPED, static fn (string $skipped): bool => str_starts_with($path, $skipped.'/'));
    }
}
