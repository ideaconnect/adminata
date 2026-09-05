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
 * Every Twig block name the admin bundle shipped at 4.43.0 still exists (PLAN/02 §5, appendix A).
 *
 * A block name is the extension point an application overrides. Renaming or dropping one breaks
 * templates adminata never sees, silently and at render time, which is why the list is frozen and
 * why removals are a major release. Additions are free.
 *
 * The names come from appendix A, generated from upstream, rather than from a list maintained here:
 * the appendix is what the plan promises and this asserts the promise.
 */
final class BlockNameTest extends ContractTestCase
{
    /**
     * The three names 1.0 removes on purpose.
     *
     * `admin_lte_skin_class` and `bootlint` belong to AdminLTE and to a Bootstrap linter, neither
     * of which adminata ships. `sonata_type_model_autocomplete_select2_options_js` configured
     * select2, which left with jQuery (PLAN/03 §C, PLAN/01 J5, J6).
     *
     * @var list<string>
     */
    private const array REMOVED = [
        'admin_lte_skin_class',
        'bootlint',
        'sonata_type_model_autocomplete_select2_options_js',
    ];

    public function testEveryBlockNameOfTheAppendixSurvives(): void
    {
        $promised = self::promisedBlockNames();

        static::assertCount(138, $promised, 'Appendix A lists 138 unique block names.');

        $missing = array_values(array_diff($promised, self::declaredBlockNames(), self::REMOVED));

        static::assertSame([], $missing, 'These block names of appendix A are gone from the templates.');
    }

    /**
     * The removals are deliberate, so each one has to be actually gone — a name left in place is a
     * template that was not finished.
     */
    public function testTheRemovedBlockNamesAreGone(): void
    {
        $declared = self::declaredBlockNames();

        foreach (self::REMOVED as $name) {
            static::assertNotContains(
                $name,
                $declared,
                \sprintf('"%s" is declared removed but a template still defines it.', $name)
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function promisedBlockNames(): array
    {
        $appendix = file_get_contents(self::root().'/PLAN/appendix-A-twig-blocks.md');

        if (false === $appendix) {
            throw new \RuntimeException('Could not read PLAN/appendix-A-twig-blocks.md.');
        }

        $names = [];

        preg_match_all('/^- `[^`]+`: (.*)$/m', $appendix, $lines);

        foreach ($lines[1] as $line) {
            $words = preg_split('/\s+/', trim($line), -1, \PREG_SPLIT_NO_EMPTY);

            if (false === $words) {
                throw new \RuntimeException(\sprintf('Could not read the block names of "%s".', $line));
            }

            foreach ($words as $name) {
                $names[$name] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * @return list<string>
     */
    private static function declaredBlockNames(): array
    {
        $names = [];

        foreach (self::templates() as $path) {
            if (!str_starts_with($path, 'packages/admin-bundle/')) {
                continue;
            }

            preg_match_all(
                '/{%-?\s*block\s+([A-Za-z0-9_]+)/',
                (string) file_get_contents(self::root().'/'.$path),
                $matches
            );

            foreach ($matches[1] as $name) {
                $names[$name] = true;
            }
        }

        return array_keys($names);
    }
}
