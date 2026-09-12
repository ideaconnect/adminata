#!/usr/bin/env php
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

/*
 * Generates known.txt: every name adminata, the ORM layer and the MongoDB layer own, read out of
 * their sources *before* the rename — the exact names the engine's application mode rewrites
 * (PLAN/v2/02 §1). Run it against the Sonata-named trees; the file is committed, so an application
 * never needs the old trees to be present.
 *
 *   build-lists.php <adminata-root> [<orm-root>] [<odm-root>] > known.txt
 *
 * Families and where they are read from:
 *   class       `class|interface|trait|enum …Sonata…` declarations under src/
 *   id          sonata.… tokens under src/ (service ids, parameters, tags, events, block types,
 *               translation ids); a token written with a trailing dot is a prefix
 *   underscore  sonata_… tokens under src/ and assets/ (config roots, routes, block names, form
 *               type prefixes, options, cookies, Twig functions, translation ids)
 *   hyphen      sonata-… tokens under src/Resources/views, assets/ and the dumped fixtures
 *               (markup hooks, data attributes, dispatched events); a trailing hyphen makes a
 *               prefix, and every Stimulus identifier listed in assets/js/registry.js is one
 *   camel       sonata… camel-case tokens under src/ and assets/js
 *
 * The other Sonata bundles' names are excluded through the same allow-list the engine uses.
 */

use Adminata\Rename\Engine;

require __DIR__.'/Engine.php';

/** @var array{skip: list<string>, keep: list<string>, retired: list<string>} $allow */
$allow = require __DIR__.'/allow.php';

/**
 * @param list<string> $directories
 *
 * @return iterable<string>
 */
function contents(array $directories): iterable
{
    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || in_array($file->getExtension(), ['png', 'gif', 'jpg', 'woff2', 'ico'], true)) {
                continue;
            }

            // The Tailwind emission fixture invents names to prove semantics; none of them is ours.
            if (str_contains($file->getPathname(), '/__fixture__/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            if (false !== $content && !str_contains($content, "\0")) {
                yield $content;
            }
        }
    }
}

/**
 * @param list<string> $phrases
 */
function hide(string $text, array $phrases): string
{
    foreach ($phrases as $phrase) {
        $text = (string) preg_replace('~'.$phrase.'~', ' ', $text);
    }

    return $text;
}

$roots = array_slice($_SERVER['argv'] ?? [], 1);

if ([] === $roots) {
    fwrite(\STDERR, "usage: build-lists.php <adminata-root> [<orm-root>] [<odm-root>] > known.txt\n");

    exit(64);
}

$hidden = [...$allow['retired'], ...$allow['keep']];
/** @var array<string, array<string, string>> $found family => token => kind */
$found = ['class' => [], 'id' => [], 'underscore' => [], 'hyphen' => [], 'camel' => []];

foreach ($roots as $root) {
    $src = [$root.'/src'];
    $srcAndAssets = [$root.'/src', $root.'/assets'];
    $markup = [$root.'/src/Resources/views', $root.'/assets', $root.'/tests-adminata/fixtures'];

    foreach (contents($src) as $text) {
        $text = hide($text, $hidden);
        preg_match_all('~\b(?:class|interface|trait|enum)\s+([A-Za-z0-9]*Sonata[A-Za-z0-9]*)~', $text, $matches);

        foreach ($matches[1] as $token) {
            $found['class'][$token] = 'exact';
        }

        preg_match_all('~(?<![A-Za-z0-9_.])sonata\.[a-z0-9_.]+~', $text, $matches);

        foreach ($matches[0] as $token) {
            $found['id'][$token] = str_ends_with($token, '.') ? 'prefix' : 'exact';
        }

        preg_match_all('~(?<![A-Za-z0-9_])sonata[A-Z][A-Za-z0-9]*~', $text, $matches);

        foreach ($matches[0] as $token) {
            $found['camel'][$token] = 'exact';
        }
    }

    foreach (contents($srcAndAssets) as $text) {
        $text = hide($text, $hidden);
        preg_match_all('~(?<![A-Za-z0-9])sonata_[a-z0-9_]+~', $text, $matches);

        foreach ($matches[0] as $token) {
            $found['underscore'][$token] = str_ends_with($token, '_') ? 'prefix' : 'exact';
        }
    }

    foreach (contents([$root.'/assets/js']) as $text) {
        preg_match_all('~(?<![A-Za-z0-9_])sonata[A-Z][A-Za-z0-9]*~', $text, $matches);

        foreach ($matches[0] as $token) {
            $found['camel'][$token] = 'exact';
        }
    }

    foreach (contents($markup) as $text) {
        $text = hide($text, $hidden);
        preg_match_all('~(?<![A-Za-z0-9])sonata-[a-z0-9-]+~', $text, $matches);

        foreach ($matches[0] as $token) {
            $found['hyphen'][$token] = str_ends_with($token, '-') ? 'prefix' : 'exact';
        }
    }

    $registry = $root.'/assets/js/registry.js';

    if (is_file($registry)) {
        preg_match_all("~^\\s*'(sonata-[a-z-]+)':~m", (string) file_get_contents($registry), $matches);

        foreach ($matches[1] as $identifier) {
            $found['hyphen'][$identifier] = 'prefix';
        }
    }
}

echo "# Generated by upstream/rename/build-lists.php from the Sonata-named trees: the names adminata,\n";
echo "# the ORM layer and the MongoDB layer own, one per line: <family>\t<token>\t<exact|prefix>.\n";
echo "# The engine's application mode rewrites exactly these and reports every other sonata… token.\n";

foreach ($found as $family => $tokens) {
    ksort($tokens);

    foreach ($tokens as $token => $kind) {
        printf("%s\t%s\t%s\n", $family, $token, $kind);
    }
}
