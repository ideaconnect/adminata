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
 * `conflict` block of composer.json, the table in UPSTREAM.md, and the tag column of
 * upstream/remotes.txt. A sync that updates one and forgets the others fails here.
 *
 * upstream/remotes.txt has a row per upstream tree that is still part of this repository. One of
 * them, `admin-bundle`, is the repository itself — src/ and tests/. The rest are listed in
 * upstream/merged.txt: their sources were merged into the admin bundle. Since the rename to
 * IDCT\Adminata (PLAN/v2 N15) adminata provides every one of those APIs under its own names, so
 * composer.json `conflict`s with all six upstream packages and `replace`s none. Their tags are
 * still recorded, in remotes.txt and UPSTREAM.md, because that is the upstream release the forked
 * sources sit at, and the release an upstream diff is taken from.
 */

$root = dirname(__DIR__);
$errors = [];

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
assert(is_array($composer));
$conflict = $composer['conflict'] ?? [];
assert(is_array($conflict));

if (isset($composer['replace'])) {
    $errors[] = 'composer.json has a "replace" block: adminata provides nothing under a sonata-project name any more (PLAN/v2 N15).';
}

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

    if (!isset($conflict[$package]) || '*' !== $conflict[$package]) {
        $errors[] = sprintf(
            '%s: composer.json must conflict with it at "*" — adminata provides that API under '
                .'its own names and the two stacks cannot coexist.',
            $package
        );
    }

    if ($isMerged) {
        $target = $mergedInto[$directory];

        if (!$treeExists($target)) {
            $errors[] = sprintf('%s: was merged into %s, which does not exist.', $package, $target);
        }

        continue;
    }

    if (!$treeExists($directory)) {
        $errors[] = sprintf(
            '%s is in upstream/remotes.txt but has no tree: a tree that was merged belongs in '
                .'upstream/merged.txt, and one that moved to a repository of its own in neither file.',
            $directory
        );
    }
}

foreach ($conflict as $package => $constraint) {
    if (!is_string($package) || !str_starts_with($package, 'sonata-project/')) {
        continue;
    }

    $directory = substr($package, strlen('sonata-project/'));

    if ('entity-audit-bundle' === $directory) {
        continue; // a third party's package, not forked; the constraint is its own business
    }

    if (!isset($tagsFromRemotes[$directory])) {
        $errors[] = sprintf(
            'composer.json conflicts with %s, which upstream/remotes.txt does not list.',
            $package
        );
    }
}

if ([] !== $errors) {
    foreach ($errors as $error) {
        fwrite(\STDERR, $error."\n");
    }

    exit(1);
}

echo "conflict, UPSTREAM.md and upstream/remotes.txt agree.\n";
