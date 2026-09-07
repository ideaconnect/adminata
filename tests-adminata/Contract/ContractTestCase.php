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

use PHPUnit\Framework\TestCase;

/**
 * The contract suite reads the packages off disk rather than through a kernel, so that a broken
 * container cannot make a frozen-interface check pass by not running.
 */
abstract class ContractTestCase extends TestCase
{
    /**
     * Where the storage layer that ships separately keeps its views, relative to the repository
     * root. It is a Composer dependency now, but adminata still owns the porting plan its
     * templates are tracked against, so the contract suite reaches into its installed copy.
     */
    final public const string ORM_VIEWS = 'vendor/idct/adminata-doctrine-orm-admin-bundle/src/Resources/views';

    final protected static function root(): string
    {
        return \dirname(__DIR__, 2);
    }

    /**
     * The directories a glob pattern matches.
     *
     * `glob()` returns `false` on a filesystem error, which for these fixed in-repo patterns means
     * the checkout is unreadable — a failure worth reporting rather than an empty result that would
     * quietly make every check below it vacuous.
     *
     * @return list<string>
     */
    final protected static function directories(string $pattern): array
    {
        $found = glob($pattern, \GLOB_ONLYDIR);

        if (false === $found) {
            throw new \RuntimeException(\sprintf('Could not list the directories matching "%s".', $pattern));
        }

        return $found;
    }

    /**
     * Every Twig template the admin bundle ships, as repository-relative paths.
     *
     * @return list<string>
     */
    final protected static function templates(): array
    {
        $root = self::root();
        $found = [];

        foreach (self::directories($root.'/src/Resources/views') as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo) {
                    throw new \RuntimeException(\sprintf('Could not read the contents of "%s".', $directory));
                }

                if ('twig' === $file->getExtension()) {
                    $found[] = substr($file->getPathname(), \strlen($root) + 1);
                }
            }
        }

        sort($found);

        return $found;
    }
}
