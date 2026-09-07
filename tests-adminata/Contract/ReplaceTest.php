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
 * adminata `replace`s three packages at once (PLAN/02 §13). These tests prove that the result still
 * resolves: on its own, next to `idct/sonata-admin-mongodb-bundle`, and — when the application is
 * available — for the migration recomaty-panel will run in phase 5.
 *
 * They hit Packagist, so they are in the `network` group and are skipped without an explicit
 * `--group network`.
 */
#[Group('network')]
final class ReplaceTest extends TestCase
{
    private string $workspace = '';

    protected function setUp(): void
    {
        $workspace = tempnam(sys_get_temp_dir(), 'adminata-replace-');
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

    public function testAdminataResolvesOnItsOwn(): void
    {
        $this->writeProject([
            'idct/adminata' => '@dev',
            'symfony/framework-bundle' => '^7.4 || ^8.0',
        ]);

        $this->assertComposerUpdateSucceeds();
    }

    public function testAdminataResolvesTogetherWithTheMongoDbFork(): void
    {
        $this->skipUntilTheForkIsReleasedAgainstAdminata();

        $this->writeProject([
            'idct/adminata' => '@dev',
            'idct/sonata-admin-mongodb-bundle' => '^5.2',
            'doctrine/doctrine-bundle' => '^3.0',
            'doctrine/mongodb-odm-bundle' => '^5.0',
            'symfony/framework-bundle' => '^8.1',
        ]);

        $this->assertComposerUpdateSucceeds();
    }

    /**
     * The requirement of PLAN/02 §1: nothing may pull a real `sonata-project` package back in
     * next to adminata, because Composer would then have to choose between two providers of the
     * same names.
     */
    public function testNoSonataPackageIsInstalledAlongsideAdminata(): void
    {
        $this->skipUntilTheForkIsReleasedAgainstAdminata();

        $this->writeProject([
            'idct/adminata' => '@dev',
            'idct/sonata-admin-mongodb-bundle' => '^5.2',
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
     * The first step of PLAN/10 §1, dry-run against the real application: install adminata, then
     * drop the two explicit `sonata-project` requirements the `replace` makes redundant.
     *
     * Point `ADMINATA_APP_DIR` at a checkout of the application to run it.
     */
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
     * The MongoDB fork's published tags (up to v5.2.2) and its committed `5.x` still
     * `require: sonata-project/exporter ^3.0` and `sonata-project/form-extensions ^2.0`. Both used
     * to be satisfied by adminata's `replace`; since those trees were merged into admin-bundle
     * adminata `conflict`s with them instead, so Composer cannot put the two together until the
     * fork is released with `idct/adminata` in place of its three sonata-project requirements.
     *
     * The fork's fix is written and waiting in its working tree. Set ADMINATA_FORK_RELEASED=1 once
     * the tag is published: the two tests below then run again, and must pass.
     */
    private function skipUntilTheForkIsReleasedAgainstAdminata(): void
    {
        if ('1' === getenv('ADMINATA_FORK_RELEASED')) {
            return;
        }

        static::markTestSkipped(
            'Needs an idct/sonata-admin-mongodb-bundle release requiring idct/adminata instead of '
            .'sonata-project/exporter and sonata-project/form-extensions; every published tag still '
            .'requires both, which adminata now conflicts with. Set ADMINATA_FORK_RELEASED=1 when '
            .'the tag lands.'
        );
    }

    /**
     * @param array<string, string> $require
     * @param array<string, mixed>  $manifest
     */
    private function writeProject(array $require, array $manifest = []): void
    {
        $manifest = array_merge($manifest, [
            'name' => 'adminata/replace-test',
            'type' => 'project',
            'require' => $require,
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => \dirname(__DIR__, 2),
                    'options' => ['symlink' => true],
                ],
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
