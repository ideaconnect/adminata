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

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * adminata 1.0 ships a subset of Sonata on purpose (PLAN/01 S1). The templates outside it are
 * inherited unchanged and render their Bootstrap markup unstyled, which is a deliberate state and
 * not a bug — but only as long as it is written down.
 *
 * This holds three things in agreement: `tests-adminata/Contract/deferred-templates.txt`, the
 * `{# adminata: not yet ported #}` marker in each file, and what is actually on disk. Porting a
 * template means rewriting it, removing its marker and removing it from the list; forgetting
 * either half fails here.
 */
final class DeferredTemplateTest extends ContractTestCase
{
    private const string MARKER = 'adminata: not yet ported';

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideADeferredTemplateExistsAndSaysSoCases(): iterable
    {
        foreach (self::deferred() as $path) {
            yield $path => [$path];
        }
    }

    #[DataProvider('provideADeferredTemplateExistsAndSaysSoCases')]
    public function testADeferredTemplateExistsAndSaysSo(string $path): void
    {
        $file = self::root().'/'.$path;

        static::assertFileExists($file, \sprintf(
            '%s is listed as deferred but does not exist. If it was ported, remove it from '
            .'tests-adminata/Contract/deferred-templates.txt.',
            $path
        ));

        static::assertStringContainsString(
            self::MARKER,
            (string) file_get_contents($file),
            \sprintf('%s is listed as deferred but carries no "%s" marker.', $path, self::MARKER)
        );
    }

    public function testNoOtherTemplateCarriesTheMarker(): void
    {
        $listed = array_flip(self::deferred());
        $unexpected = [];

        foreach (self::templates() as $path) {
            if (isset($listed[$path])) {
                continue;
            }

            if (str_contains((string) file_get_contents(self::root().'/'.$path), self::MARKER)) {
                $unexpected[] = $path;
            }
        }

        static::assertSame([], $unexpected, 'These templates are marked as deferred but are not listed.');
    }

    /**
     * The count is in PLAN/03 §E and in the executive summary's headline numbers; if it moves,
     * those documents move with it.
     */
    public function testTheListHasTheNumberOfTemplatesThePlanStates(): void
    {
        static::assertCount(37, self::deferred());
    }

    /**
     * @return list<string>
     */
    private static function deferred(): array
    {
        $lines = file(self::root().'/tests-adminata/Contract/deferred-templates.txt', \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        static::assertNotFalse($lines);

        return array_values(array_filter(
            array_map(trim(...), $lines),
            static fn (string $line): bool => '' !== $line && !str_starts_with($line, '#')
        ));
    }
}
