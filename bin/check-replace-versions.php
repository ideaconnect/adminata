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
 * Asserts that the three places that record which upstream version each forked package sits at
 * agree: the `replace` block of composer.json, the table in UPSTREAM.md, and the tag column of
 * upstream/remotes.txt. A sync that updates one and forgets the others fails here.
 */

$root = dirname(__DIR__);
$errors = [];

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
assert(is_array($composer));
$replace = $composer['replace'] ?? [];
assert(is_array($replace));

$upstream = (string) file_get_contents($root.'/UPSTREAM.md');
$remotes = (string) file_get_contents($root.'/upstream/remotes.txt');

/** @var array<string, string> $tagsFromRemotes */
$tagsFromRemotes = [];
foreach (explode("\n", $remotes) as $line) {
    if ('' === trim($line) || str_starts_with(trim($line), '#')) {
        continue;
    }

    $columns = preg_split('/\s+/', trim($line)) ?: [];

    if (3 !== count($columns)) {
        $errors[] = sprintf('upstream/remotes.txt: cannot parse "%s"', trim($line));

        continue;
    }

    $tagsFromRemotes[$columns[0]] = $columns[2];
}

if ([] === $replace) {
    $errors[] = 'composer.json has no "replace" block.';
}

foreach ($replace as $package => $version) {
    assert(is_string($package) && is_string($version));

    $directory = substr($package, strrpos($package, '/') + 1);

    if (!isset($tagsFromRemotes[$directory])) {
        $errors[] = sprintf('%s: no row in upstream/remotes.txt for packages/%s.', $package, $directory);
    } elseif ($tagsFromRemotes[$directory] !== $version) {
        $errors[] = sprintf(
            '%s: composer.json replaces %s but upstream/remotes.txt records %s.',
            $package,
            $version,
            $tagsFromRemotes[$directory]
        );
    }

    $pattern = sprintf(
        '/\|\s*`packages\/%s`\s*\|\s*`%s`\s*\|[^|]*\|\s*%s\s*\|/',
        preg_quote($directory, '/'),
        preg_quote($package, '/'),
        preg_quote($version, '/')
    );

    if (1 !== preg_match($pattern, $upstream)) {
        $errors[] = sprintf('%s: UPSTREAM.md does not list packages/%s at %s.', $package, $directory, $version);
    }

    if (!is_dir($root.'/packages/'.$directory.'/src')) {
        $errors[] = sprintf('%s: packages/%s/src does not exist.', $package, $directory);
    }
}

foreach (array_keys($tagsFromRemotes) as $directory) {
    $package = 'sonata-project/'.$directory;

    if (!isset($replace[$package])) {
        $errors[] = sprintf('packages/%s is in upstream/remotes.txt but composer.json does not replace %s.', $directory, $package);
    }
}

if ([] !== $errors) {
    foreach ($errors as $error) {
        fwrite(\STDERR, 'error: '.$error."\n");
    }

    exit(1);
}

printf("replace, UPSTREAM.md and upstream/remotes.txt agree on all %d packages.\n", count($replace));
