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
use Sonata\AdminBundle\DependencyInjection\Configuration as AdminConfiguration;
use Sonata\BlockBundle\DependencyInjection\Configuration as BlockConfiguration;
use Sonata\DoctrineORMAdminBundle\DependencyInjection\Configuration as OrmConfiguration;
use Sonata\Exporter\Bridge\Symfony\DependencyInjection\Configuration as ExporterConfiguration;
use Sonata\Form\Bridge\Symfony\DependencyInjection\Configuration as FormConfiguration;
use Sonata\Twig\Bridge\Symfony\DependencyInjection\Configuration as TwigConfiguration;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;

/**
 * The configuration trees of PLAN/02 §3 do not drift.
 *
 * Every node, default and description of the six roots that declare one is captured under
 * `config-reference/`. An application's `sonata_admin.yaml` is written against these, and a node
 * that silently changes its default changes what that application does — so a change here has to
 * be a deliberate edit to the reference file, reviewed in the same commit.
 *
 * The baseline was taken after the P0-10 changes, which are the intended differences from upstream
 * (the four removed AdminLTE options, the asset defaults and the new `theme` node). It dumps the
 * `Configuration` classes directly rather than booting a kernel, so no test application has to
 * register all seven bundles for this to run.
 *
 * `sonata_doctrine` has no reference: doctrine-extensions declares no `Configuration` class, so
 * its root takes no options at all.
 */
final class ConfigContractTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ConfigurationInterface}>
     */
    public static function provideTheTreeMatchesTheCapturedReferenceCases(): iterable
    {
        yield 'sonata_admin' => ['sonata_admin', new AdminConfiguration()];
        yield 'sonata_block' => ['sonata_block', new BlockConfiguration([])];
        yield 'sonata_doctrine_orm_admin' => ['sonata_doctrine_orm_admin', new OrmConfiguration()];
        yield 'sonata_exporter' => ['sonata_exporter', new ExporterConfiguration()];
        yield 'sonata_form' => ['sonata_form', new FormConfiguration()];
        yield 'sonata_twig' => ['sonata_twig', new TwigConfiguration()];
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
     * The four options PLAN/01 P6 removes are gone for good: leaving one in `sonata_admin.yaml`
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
