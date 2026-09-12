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
use PHPUnit\Framework\TestCase;
use IDCT\Adminata\DependencyInjection\BlockConfiguration;
use IDCT\Adminata\DependencyInjection\Configuration as AdminConfiguration;
use IDCT\Adminata\DependencyInjection\ExporterConfiguration;
use IDCT\Adminata\DependencyInjection\FormConfiguration;
use IDCT\Adminata\DependencyInjection\TwigConfiguration;
use IDCT\Adminata\DoctrineORM\DependencyInjection\Configuration as OrmConfiguration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

/**
 * The configuration trees of PLAN/02 §3 do not drift.
 *
 * Every node, default and description of the six roots that declare one is captured under
 * `config-reference/`. An application's `adminata.yaml` is written against these, and a node
 * that silently changes its default changes what that application does — so a change here has to
 * be a deliberate edit to the reference file, reviewed in the same commit.
 *
 * The baseline was taken after the P0-10 changes, which are the intended differences from upstream
 * (the four removed AdminLTE options, the asset defaults and the new `theme` node). It dumps the
 * `Configuration` classes directly rather than booting a kernel, so no test application has to
 * register the bundles for this to run.
 *
 * Five of the six roots belong to admin-bundle: `adminata_admin` from `Configuration`, and
 * `adminata_block`, `adminata_exporter`, `adminata_form` and `adminata_twig` from the
 * `BlockConfiguration`, `ExporterConfiguration`, `FormConfiguration` and `TwigConfiguration`
 * classes beside it. Five aliases, one bundle — the aliases are part of the contract, so an
 * application's existing `adminata_exporter.yaml`, `adminata_form.yaml` and `adminata_twig.yaml` keep
 * configuring the same trees.
 *
 * There is no `adminata_doctrine` root any more. The tree it used to name took no options at all --
 * doctrine-extensions never declared a `Configuration` class -- so when that package was merged in,
 * its services moved to `AdminataExtension` rather than to a registered extension that would
 * only have added an empty configuration key.
 */
final class ConfigContractTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ConfigurationInterface}>
     */
    public static function provideTheTreeMatchesTheCapturedReferenceCases(): iterable
    {
        yield 'adminata' => ['adminata', new AdminConfiguration()];
        yield 'adminata_block' => ['adminata_block', new BlockConfiguration([])];
        yield 'adminata_doctrine_orm' => ['adminata_doctrine_orm', new OrmConfiguration()];
        yield 'adminata_exporter' => ['adminata_exporter', new ExporterConfiguration()];
        yield 'adminata_form' => ['adminata_form', new FormConfiguration()];
        yield 'adminata_twig' => ['adminata_twig', new TwigConfiguration()];
    }

    #[DataProvider('provideTheTreeMatchesTheCapturedReferenceCases')]
    public function testTheTreeMatchesTheCapturedReference(string $root, ConfigurationInterface $configuration): void
    {
        $reference = \sprintf('%s/config-reference/%s.yaml', __DIR__, $root);

        static::assertFileExists($reference, \sprintf('No captured reference for the %s root.', $root));

        static::assertSame(
            file_get_contents($reference),
            new YamlReferenceDumper()->dump($configuration),
            \sprintf(
                'The %s configuration tree changed. If that is intended, update '
                .'tests/Contract/config-reference/%s.yaml in the same commit and say why — an '
                .'application\'s configuration files are written against this tree (PLAN/02 §3).',
                $root,
                $root
            )
        );
    }

    /**
     * The four options PLAN/01 P6 removes are gone for good: leaving one in `adminata.yaml`
     * has to be an error an application sees, not a silently ignored key.
     */
    public function testTheRemovedAdminLteOptionsAreAbsent(): void
    {
        $dumped = new YamlReferenceDumper()->dump(new AdminConfiguration());

        foreach (['skin:', 'use_select2:', 'use_icheck:', 'use_bootlint:'] as $option) {
            static::assertStringNotContainsString($option, $dumped);
        }
    }

    public function testTheThemeNodeIsPartOfTheAdminRoot(): void
    {
        $dumped = new YamlReferenceDumper()->dump(new AdminConfiguration());

        static::assertStringContainsString('theme:', $dumped);
        static::assertStringContainsString('mode:', $dumped);
        static::assertStringContainsString('logo_dark:', $dumped);
        static::assertStringContainsString('logo_icon:', $dumped);
    }
}
