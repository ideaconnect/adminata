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
 * Asserts that the places that record which upstream version each forked tree sits at agree: the
 * `replace` block of composer.json, the table in UPSTREAM.md, and the tag column of
 * upstream/remotes.txt. A sync that updates one and forgets the others fails here.
 *
 * upstream/remotes.txt has a row per upstream tree that is still part of this repository. One of
 * them, `admin-bundle`, is the repository itself — src/ and tests/ — and composer.json `replace`s
 * it. The rest are listed in upstream/merged.txt: their sources were merged into the admin bundle,
 * so they have no tree, no `replace` entry and — because adminata now provides those APIs under a
 * different namespace — a `conflict` entry instead. Their tags are still recorded, in remotes.txt
 * and UPSTREAM.md, because that is the upstream release the merged sources sit at.
 */

$root = dirname(__DIR__);
$errors = [];

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
assert(is_array($composer));
$replace = $composer['replace'] ?? [];
assert(is_array($replace));
$conflict = $composer['conflict'] ?? [];
assert(is_array($conflict));

$upstream = (string) file_get_contents($root.'/UPSTREAM.md');
$remotes = (string) file_get_contents($root.'/upstream/remotes.txt');
$merged = (string) file_get_contents($root.'/upstream/merged.txt');

/**
 * Splits a whitespace-separated data file into its rows, skipping blanks and comments.
 *
 * @return list<list<string>>
 */
$columns = static function (string $contents): array {
    $rows = [];

    foreach (explode("\n", $contents) as $line) {
        $line = trim($line);

        if ('' === $line || str_starts_with($line, '#')) {
            continue;
        }

        $rows[] = preg_split('/\s+/', $line) ?: [];
    }

    return $rows;
};

/** @var array<string, string> $tagsFromRemotes upstream tree => imported tag */
$tagsFromRemotes = [];

foreach ($columns($remotes) as $row) {
    if (3 !== count($row)) {
        $errors[] = sprintf('upstream/remotes.txt: cannot parse "%s"', implode(' ', $row));

        continue;
    }

    $tagsFromRemotes[$row[0]] = $row[2];
}

/** @var array<string, string> $mergedInto upstream tree => the tree it was merged into */
$mergedInto = [];

foreach ($columns($merged) as $row) {
    if (2 !== count($row)) {
        $errors[] = sprintf('upstream/merged.txt: cannot parse "%s"', implode(' ', $row));

        continue;
    }

    $mergedInto[$row[0]] = $row[1];
}

/*
 * The tag each upstream package is recorded at in UPSTREAM.md's version table, read by column name
 * rather than by position so that prose changes to the other columns cannot silently break this.
 * A merged tree keeps its row there — only its "Where it lives" cell no longer names a tree.
 */
/** @var array<string, string> $tagsFromUpstream composer package name => tag */
$tagsFromUpstream = [];
$packageColumn = null;
$tagColumn = null;

foreach (explode("\n", $upstream) as $line) {
    $line = trim($line);

    if (!str_starts_with($line, '|')) {
        $packageColumn = null;
        $tagColumn = null;

        continue;
    }

    $cells = array_map(trim(...), explode('|', trim($line, '|')));

    if (null === $packageColumn || null === $tagColumn) {
        $package = array_search('Upstream package', $cells, true);
        $tag = array_search('Tag', $cells, true);

        if (is_int($package) && is_int($tag)) {
            $packageColumn = $package;
            $tagColumn = $tag;
        }

        continue;
    }

    if ($cells === array_filter($cells, static fn (string $cell): bool => 1 === preg_match('/^:?-+:?$/', $cell))) {
        continue;
    }

    $name = trim($cells[$packageColumn] ?? '', '`');

    if ('' !== $name) {
        $tagsFromUpstream[$name] = trim($cells[$tagColumn] ?? '', '`');
    }
}

if ([] === $tagsFromUpstream) {
    $errors[] = 'UPSTREAM.md: no version table with "Upstream package" and "Tag" columns.';
}

if ([] === $replace) {
    $errors[] = 'composer.json has no "replace" block.';
}

// The one tree that is not merged is the repository itself, so "does it still exist" is a
// question about src/ at the root rather than about a directory named after the package.
$treeExists = static fn (string $tree): bool => 'admin-bundle' === $tree && is_dir($root.'/src');

foreach ($tagsFromRemotes as $directory => $tag) {
    $package = 'sonata-project/'.$directory;
    $isMerged = isset($mergedInto[$directory]);

    if (($tagsFromUpstream[$package] ?? null) !== $tag) {
        $errors[] = sprintf(
            '%s: upstream/remotes.txt records %s but UPSTREAM.md lists %s.',
            $package,
            $tag,
            $tagsFromUpstream[$package] ?? '(nothing)'
        );
    }

    if ($isMerged) {
        $target = $mergedInto[$directory];

        if (isset($replace[$package])) {
            $errors[] = sprintf(
                '%s: merged into %s, so composer.json must not replace it.',
                $package,
                $target
            );
        }

        if (!isset($conflict[$package])) {
            $errors[] = sprintf(
                '%s: merged into %s, so composer.json must conflict with it — adminata '
                    .'provides that API under its own namespace and the two stacks cannot coexist.',
                $package,
                $target
            );
        }

        if (!$treeExists($target)) {
            $errors[] = sprintf('%s: was merged into %s, which does not exist.', $package, $target);
        }

        continue;
    }

    if (!isset($replace[$package])) {
        $errors[] = sprintf(
            '%s is in upstream/remotes.txt but composer.json does not replace %s. '
                .'A tree that is no longer replaced belongs in upstream/merged.txt, or, if it moved '
                .'to a repository of its own, in neither file.',
            $directory,
            $package
        );

        continue;
    }

    $version = $replace[$package];
    assert(is_string($version));

    if ($version !== $tag) {
        $errors[] = sprintf(
            '%s: composer.json replaces %s but upstream/remotes.txt records %s.',
            $package,
            $version,
            $tag
        );
    }

    $pattern = sprintf(
        '/\|\s*`src\/` \+ `tests\/`\s*\|\s*`%s`\s*\|/',
        preg_quote($package, '/')
    );

    if (1 !== preg_match($pattern, $upstream)) {
        $errors[] = sprintf('%s: UPSTREAM.md does not list it against `src/` + `tests/`.', $package);
    }

    if (!$treeExists($directory)) {
        $errors[] = sprintf('%s: its tree does not exist.', $package);
    }
}

foreach ($replace as $package => $version) {
    assert(is_string($package));

    $directory = substr($package, strrpos($package, '/') + 1);

    if (!isset($tagsFromRemotes[$directory])) {
        $errors[] = sprintf('%s: no row in upstream/remotes.txt for %s.', $package, $directory);
    }
}

foreach ($mergedInto as $directory => $target) {
    if (!isset($tagsFromRemotes[$directory])) {
        $errors[] = sprintf(
            'upstream/merged.txt lists %s but upstream/remotes.txt has no row for it; the remote and '
                .'the imported history are what make its merged sources traceable.',
            $directory
        );
    }
}

if ([] !== $errors) {
    foreach ($errors as $error) {
        fwrite(\STDERR, 'error: '.$error."\n");
    }

    exit(1);
}

printf(
    "replace, UPSTREAM.md and upstream/remotes.txt agree on all %d replaced packages%s.\n",
    count($replace),
    [] === $mergedInto ? '' : sprintf(
        ', and on the %d merged tree%s (%s)',
        count($mergedInto),
        1 === count($mergedInto) ? '' : 's',
        implode(', ', array_map(
            static fn (string $from, string $to): string => $from.' → '.$to,
            array_keys($mergedInto),
            array_values($mergedInto)
        ))
    )
);
