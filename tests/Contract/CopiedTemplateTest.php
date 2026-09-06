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

/**
 * The twelve templates PLAN/03 §G copies unchanged carry no Bootstrap (PLAN/03 §G, PLAN/02 §1).
 *
 * They are copied because they had none to begin with — the ORM's two form themes only delegate to
 * admin blocks and to the association includes, and block-bundle's ten are structural. That is a
 * property worth asserting rather than remembering: a later edit that reaches for `btn` or
 * `col-md-6` in one of them would put Bootstrap back into a package whose stylesheet no longer
 * ships it, and nothing else would notice.
 *
 * The MongoDB fork's own two themes have the same shape and the same contract; the `mongo-compat`
 * job renders them against this checkout.
 */
final class CopiedTemplateTest extends ContractTestCase
{
    /**
     * Class names of the Bootstrap 3 / AdminLTE vocabulary PLAN/02 §8 drops. Matched as whole
     * words so that `adm-btn` and `sonata-ba-box` do not trip them.
     *
     * @var list<string>
     */
    private const array DROPPED = [
        'btn',
        'btn-default',
        'btn-primary',
        'btn-success',
        'btn-danger',
        'btn-warning',
        'btn-info',
        'btn-link',
        'btn-sm',
        'btn-xs',
        'btn-group',
        'form-control',
        'form-group',
        'form-horizontal',
        'control-label',
        'help-block',
        'has-error',
        'input-group',
        'input-group-addon',
        'list-unstyled',
        'text-muted',
        'nav-tabs',
        'nav-tabs-custom',
        'tab-pane',
        'tab-content',
        'well',
        'box',
        'box-body',
        'box-header',
        'box-footer',
        'box-title',
        'box-primary',
        'box-danger',
        'panel',
        'panel-body',
        'panel-default',
        'panel-group',
        'modal',
        'modal-body',
        'modal-dialog',
        'modal-content',
        'sonata-bc',
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTheTemplateCarriesNoBootstrapCases(): iterable
    {
        $templates = [
            'packages/doctrine-orm-admin-bundle/src/Resources/views/Form/form_admin_fields.html.twig',
            'packages/doctrine-orm-admin-bundle/src/Resources/views/Form/filter_admin_fields.html.twig',
        ];

        foreach ([
            'block_base',
            'block_container',
            'block_template',
            'block_core_text',
            'block_core_menu',
            'block_core_action',
            'block_exception',
            'block_exception_debug',
            'block_no_page_available',
        ] as $block) {
            $templates[] = \sprintf('packages/admin-bundle/src/Resources/views/Block/%s.html.twig', $block);
        }

        // `Profiler/block.html.twig` is deliberately not here: it renders inside Symfony's web
        // profiler, which brings its own stylesheet, and the `tab-content` it uses is the
        // profiler's own class rather than Bootstrap's.

        foreach ($templates as $template) {
            yield $template => [$template];
        }
    }

    #[DataProvider('provideTheTemplateCarriesNoBootstrapCases')]
    public function testTheTemplateCarriesNoBootstrap(string $template): void
    {
        $path = self::root().'/'.$template;

        static::assertFileExists($path, \sprintf('%s is listed as copied unchanged but is not there.', $template));

        $source = (string) file_get_contents($path);
        $found = [];

        foreach (self::DROPPED as $class) {
            // Inside a class attribute and delimited: `class="… btn …"`, not `adm-btn`.
            if (1 === preg_match('/class="[^"]*(?<![\w-])'.preg_quote($class, '/').'(?![\w-])[^"]*"/', $source)) {
                $found[] = $class;
            }
        }

        static::assertSame([], $found, \sprintf('%s carries Bootstrap classes.', $template));
    }

    /**
     * The ORM form theme delegates its association widgets to templates that are still inherited,
     * and every one of them has to be on the deferred list — otherwise the theme renders unported
     * markup that nothing is tracking.
     */
    public function testTheAssociationIncludesAreDeclaredDeferred(): void
    {
        $theme = (string) file_get_contents(
            self::root().'/packages/doctrine-orm-admin-bundle/src/Resources/views/Form/form_admin_fields.html.twig'
        );

        static::assertGreaterThan(
            0,
            preg_match_all("#include '@SonataAdmin/(CRUD/Association/[^']+)'#", $theme, $matches),
            'The ORM form theme no longer includes any association template.'
        );

        $deferred = array_flip(array_filter(
            array_map(trim(...), explode("\n", (string) file_get_contents(self::root().'/tests/Contract/deferred-templates.txt'))),
            static fn (string $line): bool => '' !== $line && !str_starts_with($line, '#')
        ));

        foreach ($matches[1] as $include) {
            static::assertArrayHasKey(
                'packages/admin-bundle/src/Resources/views/'.$include,
                $deferred,
                \sprintf('%s is included by the ORM form theme but is not declared deferred.', $include)
            );
        }
    }
}
