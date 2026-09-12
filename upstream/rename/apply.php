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
 * The rename engine's command line (PLAN/v2/02 §1). Runs from a checkout of adminata and from an
 * application's vendor/idct/adminata/ alike; bin/adminata-rename is a launcher for it.
 *
 *   apply.php --tree <dir> [--dry-run]     rewrite one of adminata's own trees
 *   apply.php --app <dir> [--dry-run]      rewrite an application: the names adminata, the ORM
 *                                          layer and the MongoDB layer own; report the rest
 *   apply.php --check [--app] <dir>        the gate: list what is left, exit 1 if anything is
 *   apply.php --stdin [--as <path>]        rewrite standard input (upstream/sync.sh)
 *   apply.php --path <path>                print the renamed path (upstream/sync.sh)
 *
 * Files come from `git ls-files` (tracked and untracked-but-not-ignored) when the directory is
 * inside a git work tree, else from a walk that skips .git, vendor, node_modules and var. Paths
 * that change are moved with `git mv` (or rename() outside git).
 */

use Adminata\Rename\Engine;

require __DIR__.'/Engine.php';

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    $args = array_slice($argv, 1);
    $mode = null;
    $action = null;
    $target = null;
    $dryRun = false;
    $as = 'stdin.txt';

    for ($i = 0; $i < count($args); ++$i) {
        switch ($args[$i]) {
            case '--tree':
            case '--app':
                $action ??= 'rewrite';
                $mode = '--tree' === $args[$i] ? Engine::MODE_TREE : Engine::MODE_APP;
                if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')) {
                    $target = $args[++$i];
                }
                break;
            case '--check':
                $action = 'check';
                if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')) {
                    $target = $args[++$i];
                }
                break;
            case '--dry-run':
                $dryRun = true;
                break;
            case '--stdin':
                $action = 'stdin';
                break;
            case '--as':
                $as = $args[++$i] ?? $as;
                break;
            case '--path':
                $action = 'path';
                $target = $args[++$i] ?? null;
                break;
            default:
                if (null === $target && !str_starts_with($args[$i], '--')) {
                    $target = $args[$i];
                    break;
                }

                fwrite(\STDERR, sprintf("unknown argument %s\n", $args[$i]));

                return 64;
        }
    }

    $engine = Engine::fromDirectory(__DIR__, $mode ?? Engine::MODE_TREE);

    switch ($action) {
        case 'stdin':
            echo $engine->rewrite((string) stream_get_contents(\STDIN), $as);

            return 0;
        case 'path':
            if (null === $target) {
                fwrite(\STDERR, "usage: apply.php --path <path>\n");

                return 64;
            }

            echo $engine->rewritePath($target), "\n";

            return 0;
        case 'check':
            return check($engine, realTarget($target));
        case 'rewrite':
            return rewrite($engine, realTarget($target), $dryRun);
        default:
            fwrite(\STDERR, "usage: apply.php --tree <dir> | --app <dir> | --check [--app] <dir> | --stdin [--as <path>] | --path <path>  [--dry-run]\n");

            return 64;
    }
}

function realTarget(?string $target): string
{
    $real = null === $target ? false : realpath($target);

    if (false === $real || !is_dir($real)) {
        fwrite(\STDERR, sprintf("not a directory: %s\n", $target ?? '(none)'));

        exit(66);
    }

    return $real;
}

/**
 * @return list<string> paths relative to $root, sorted
 */
function listFiles(string $root): array
{
    $files = [];

    if (isGit($root)) {
        $output = shell_exec(sprintf('cd %s && git ls-files -z --cached --others --exclude-standard', escapeshellarg($root)));

        foreach (explode("\0", (string) $output) as $path) {
            if ('' !== $path && is_file($root.'/'.$path)) {
                $files[] = $path;
            }
        }
    } else {
        $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $file): bool => !in_array($file->getFilename(), ['.git', 'vendor', 'node_modules', 'var'], true),
        ));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $files[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
    }

    sort($files);

    return array_values(array_unique($files));
}

function isGit(string $root): bool
{
    exec(sprintf('cd %s && git rev-parse --is-inside-work-tree 2>/dev/null', escapeshellarg($root)), $output, $code);

    return 0 === $code;
}

function rewrite(Engine $engine, string $root, bool $dryRun): int
{
    $git = isGit($root);
    $changed = 0;
    $moved = 0;
    /** @var array<string, list<array{line: int, text: string, retired: bool}>> $report */
    $report = [];

    foreach (listFiles($root) as $path) {
        if ($engine->isSkipped($path)) {
            continue;
        }

        $absolute = $root.'/'.$path;
        $content = file_get_contents($absolute);
        $binary = false === $content || str_contains($content, "\0");
        $rewritten = $binary ? '' : $engine->rewrite((string) $content, $path);

        if (!$binary && $rewritten !== $content) {
            ++$changed;
            printf("M %s\n", $path);

            if (!$dryRun) {
                file_put_contents($absolute, $rewritten);
            }
        }

        $newPath = $engine->rewritePath($path);

        if ($newPath !== $path) {
            ++$moved;
            printf("R %s -> %s\n", $path, $newPath);

            if (!$dryRun) {
                move($root, $path, $newPath, $git);
            }
        }

        if (!$binary && Engine::MODE_APP === $engine->mode()) {
            $left = $engine->leftovers($rewritten, $newPath);

            if ([] !== $left) {
                $report[$newPath] = $left;
            }
        }
    }

    printf("%s%d files rewritten, %d paths renamed\n", $dryRun ? '(dry run) ' : '', $changed, $moved);

    if ([] !== $report) {
        echo "\nLeft to you — names adminata does not own, or lines to delete:\n";
        printReport($report);
    }

    return 0;
}

function check(Engine $engine, string $root): int
{
    /** @var array<string, list<array{line: int, text: string, retired: bool}>> $report */
    $report = [];
    $paths = [];

    foreach (listFiles($root) as $path) {
        if ($engine->isSkipped($path)) {
            continue;
        }

        $content = file_get_contents($root.'/'.$path);

        if (false === $content) {
            continue;
        }

        $left = $engine->leftovers($content, $path);

        if ([] !== $left) {
            $report[$path] = $left;
        }

        $newPath = $engine->rewritePath($path);

        if ($newPath !== $path) {
            $paths[] = sprintf('R %s -> %s', $path, $newPath);
        }
    }

    if ([] === $report && [] === $paths) {
        echo "check-names: clean\n";

        return 0;
    }

    foreach ($paths as $line) {
        echo $line, "\n";
    }

    printReport($report);
    printf("check-names: %d lines in %d files, %d paths\n", array_sum(array_map(count(...), $report)), count($report), count($paths));

    return 1;
}

/**
 * @param array<string, list<array{line: int, text: string, retired: bool}>> $report
 */
function printReport(array $report): void
{
    foreach ($report as $path => $lines) {
        foreach ($lines as $entry) {
            printf("%s:%d:%s %s\n", $path, $entry['line'], $entry['retired'] ? ' [retired]' : '', mb_strimwidth($entry['text'], 0, 160, '…'));
        }
    }
}

function move(string $root, string $from, string $to, bool $git): void
{
    $directory = dirname($root.'/'.$to);

    if (!is_dir($directory) && !mkdir($directory, 0o777, true) && !is_dir($directory)) {
        throw new RuntimeException(sprintf('Cannot create %s', $directory));
    }

    if ($git) {
        exec(sprintf('cd %s && git mv -k %s %s', escapeshellarg($root), escapeshellarg($from), escapeshellarg($to)), $output, $code);

        if (0 === $code) {
            return;
        }
    }

    if (!rename($root.'/'.$from, $root.'/'.$to)) {
        throw new RuntimeException(sprintf('Cannot move %s to %s', $from, $to));
    }
}

exit(main($_SERVER['argv'] ?? []));
