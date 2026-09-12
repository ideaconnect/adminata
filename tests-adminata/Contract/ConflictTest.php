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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * adminata `conflict`s with the six `sonata-project` packages it forked and `replace`s none of them
 * (PLAN/v2 N15): it provides those APIs under its own names, so an installation cannot hold both.
 * These tests prove that the result still resolves — on its own, next to the two storage layers,
 * and, when the application is available, for recomaty-panel's manifest — and that nothing pulls
 * a `sonata-project` package back in.
 *
 * The resolution tests hit Packagist and GitHub, so they are in the `network` group and are
 * skipped without an explicit `--group network`; the manifest test runs everywhere.
 */
final class ConflictTest extends TestCase
{
    private string $workspace = '';

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'adminata-conflict-');
        static::assertIsString($workspace);

        unlink($workspace);
        mkdir($workspace, 0o755, true);

        $this->workspace = $workspace;
    }

    protected function tearDown(): void
    {
        if ('' !== $this->workspace) {
            new Filesystem()->remove($this->workspace);
        }
    }

    /**
     * Offline: the manifest itself. No `replace`, a `*` conflict with each of the six forked
     * packages, and no `sonata-project` requirement anywhere but the third-party audit bundle.
     */
    public function testTheManifestConflictsAndDoesNotReplace(): void
    {
        $manifest = json_decode((string) file_get_contents(\dirname(__DIR__, 2).'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($manifest);
        static::assertArrayNotHasKey('replace', $manifest);
        static::assertIsArray($manifest['conflict'] ?? null);

        foreach (['admin-bundle', 'block-bundle', 'doctrine-extensions', 'exporter', 'form-extensions', 'twig-extensions'] as $package) {
            static::assertSame('*', $manifest['conflict']['sonata-project/'.$package] ?? null, $package);
        }

        foreach (['require', 'require-dev'] as $section) {
            static::assertIsArray($manifest[$section] ?? null);

            foreach (array_keys($manifest[$section]) as $package) {
                static::assertTrue(
                    !\is_string($package) || !str_starts_with($package, 'sonata-project/') || 'sonata-project/entity-audit-bundle' === $package,
                    \sprintf('%s requires %s.', $section, $package)
                );
            }
        }
    }

    #[Group('network')]
    public function testAdminataResolvesOnItsOwn(): void
    {
        $this->writeProject([
            'idct/adminata' => '@dev',
            'symfony/framework-bundle' => '^7.4 || ^8.0',
        ]);

        $this->assertComposerUpdateSucceeds();
    }

    #[Group('network')]
    public function testAdminataResolvesTogetherWithTheStorageLayers(): void
    {
        $this->writeProject([
            'idct/adminata' => '@dev',
            'idct/adminata-doctrine-orm-admin-bundle' => '^2.0@dev',
            'idct/adminata-admin-mongodb-bundle' => '^7.0@dev',
            'doctrine/doctrine-bundle' => '^3.0',
            'doctrine/mongodb-odm-bundle' => '^5.0',
            'symfony/framework-bundle' => '^8.1',
        ]);

        $this->assertComposerUpdateSucceeds();
    }

    /**
     * The requirement of PLAN/02 §1, sharpened by PLAN/v2 N15: nothing may pull a real
     * `sonata-project` package in next to adminata — not by `replace` any more, which is gone,
     * but by `conflict`, which refuses the install outright.
     */
    #[Group('network')]
    public function testNoUpstreamPackageIsInstalledAlongsideAdminata(): void
    {
        $this->writeProject([
            'idct/adminata' => '@dev',
            'idct/adminata-doctrine-orm-admin-bundle' => '^2.0@dev',
            'idct/adminata-admin-mongodb-bundle' => '^7.0@dev',
            'symfony/framework-bundle' => '^8.1',
        ]);

        $process = $this->composer(['update', '--dry-run', '--no-plugins', '--no-scripts', '--no-interaction']);
        $output = $process->getOutput().$process->getErrorOutput();

        static::assertSame(0, $process->getExitCode(), $output);
        static::assertDoesNotMatchRegularExpression(
            '/- (Locking|Installing) sonata-project\//',
            $output,
            'A real sonata-project package would be installed next to adminata.'
        );
    }

    /**
     * The first step of UPGRADE.md §2, dry-run against the real application: install adminata,
     * then drop every explicit `sonata-project` requirement, which the `conflict`s refuse.
     *
     * Point `ADMINATA_APP_DIR` at a checkout of the application to run it.
     */
    #[Group('network')]
    public function testTheApplicationsComposerJsonStillResolves(): void
    {
        $application = $_SERVER['ADMINATA_APP_DIR'] ?? null;

        if (!\is_string($application) || !is_file($application.'/composer.json')) {
            static::markTestSkipped('Set ADMINATA_APP_DIR to a checkout of the application.');
        }

        $manifest = json_decode((string) file_get_contents($application.'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($manifest);
        static::assertIsArray($manifest['require'] ?? null);

        $require = $manifest['require'];
        foreach (array_keys($require) as $package) {
            if (\is_string($package) && str_starts_with($package, 'sonata-project/')) {
                unset($require[$package]);
            }
        }
        $require['idct/adminata'] = '@dev';

        $this->writeProject($require, $manifest);

        $this->assertComposerUpdateSucceeds();
    }

    /**
     * @param array<string, string> $require
     * @param array<string, mixed>  $manifest
     */
    private function writeProject(array $require, array $manifest = []): void
    {
        $manifest = array_merge($manifest, [
            'name' => 'adminata/conflict-test',
            'type' => 'project',
            'require' => $require,
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => \dirname(__DIR__, 2),
                    'options' => ['symlink' => true],
                ],
                // None of the three is on Packagist under these names yet; name the repositories
                // so this proves what the packages say rather than what Packagist has indexed.
                ['type' => 'vcs', 'url' => 'https://github.com/ideaconnect/adminata-doctrine-orm-admin-bundle.git'],
                ['type' => 'vcs', 'url' => 'https://github.com/ideaconnect/adminata-admin-mongodb-bundle.git'],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
            'config' => ['allow-plugins' => false],
        ]);

        // Nothing in this scratch project is autoloaded or scripted.
        unset($manifest['autoload'], $manifest['autoload-dev'], $manifest['scripts'], $manifest['extra']);

        file_put_contents(
            $this->workspace.'/composer.json',
            json_encode($manifest, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR)
        );
    }

    private function assertComposerUpdateSucceeds(): void
    {
        $process = $this->composer(['update', '--dry-run', '--no-plugins', '--no-scripts', '--no-interaction']);

        static::assertSame(
            0,
            $process->getExitCode(),
            $process->getOutput().$process->getErrorOutput()
        );
    }

    /**
     * @param list<string> $arguments
     */
    private function composer(array $arguments): Process
    {
        $process = new Process(
            array_merge(['composer'], $arguments),
            $this->workspace,
            ['COMPOSER_MEMORY_LIMIT' => '-1', 'COMPOSER_NO_INTERACTION' => '1'],
            null,
            600.0
        );
        $process->run();

        return $process;
    }
}
