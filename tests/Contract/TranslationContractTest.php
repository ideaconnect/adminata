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
 * Every catalogue carries every key.
 *
 * Symfony falls back to the key itself when a translation is missing, and an application whose
 * `framework.translator.fallbacks` does not name English — `pl`, in the panel adminata was built
 * for — gets that key on the page. adminata added fourteen keys of its own, and every one of them
 * was English-only: a Polish administrator read `skip_to_content` in the skip link and a screen
 * reader announced `pager_navigation` for the pager.
 *
 * A key adminata cannot translate ships as the English text with `state="needs-translation"`, which
 * is what XLIFF has that attribute for: the page reads, and a translator can find the work.
 */
final class TranslationContractTest extends ContractTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEveryCatalogueCarriesEveryKeyCases(): iterable
    {
        foreach (self::directories(self::root().'/packages/*/src/Resources/translations') as $directory) {
            foreach (self::cataloguesIn($directory) as $domain) {
                yield substr($domain, \strlen(self::root()) + 1) => [$domain];
            }
        }
    }

    #[DataProvider('provideEveryCatalogueCarriesEveryKeyCases')]
    public function testEveryCatalogueCarriesEveryKey(string $domain): void
    {
        $reference = self::keysOf($domain.'.en.xliff');

        static::assertNotEmpty($reference, \sprintf('%s.en.xliff defines no keys.', $domain));

        $found = glob($domain.'.*.xliff');
        static::assertIsArray($found);

        // Every catalogue is reported at once: fixing these one failure at a time, with a full
        // suite run between each, is how a gap in the thirtieth locale stays hidden for an hour.
        $gaps = [];

        foreach ($found as $catalogue) {
            if (str_ends_with($catalogue, '.en.xliff')) {
                continue;
            }

            $missing = array_values(array_diff($reference, self::keysOf($catalogue)));

            if ([] !== $missing) {
                $gaps[] = \sprintf('%s is missing: %s', basename($catalogue), implode(', ', $missing));
            }
        }

        static::assertSame([], $gaps, \sprintf(
            "%d catalogue(s) are missing keys the English one defines:\n  %s\n\n"
            .'Add each one. If you cannot translate it, ship the English text with '
            .'`<target state="needs-translation">` so the page reads a sentence rather than an identifier.',
            \count($gaps),
            implode("\n  ", $gaps)
        ));
    }

    /**
     * The domains a translations directory holds, as absolute paths without the locale suffix.
     *
     * @return list<string>
     */
    private static function cataloguesIn(string $directory): array
    {
        $found = glob($directory.'/*.en.xliff');

        if (false === $found) {
            throw new \RuntimeException(\sprintf('Could not list the catalogues in "%s".', $directory));
        }

        return array_map(static fn (string $file): string => substr($file, 0, -\strlen('.en.xliff')), $found);
    }

    /**
     * @return list<string>
     */
    private static function keysOf(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        preg_match_all('/<trans-unit id="([^"]+)"/', (string) file_get_contents($file), $matches);

        return $matches[1];
    }
}
