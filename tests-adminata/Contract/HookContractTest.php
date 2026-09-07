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
use Symfony\Component\Yaml\Yaml;

/**
 * The markup hooks of PLAN/02 §8 survive every template rewrite.
 *
 * This is the test that gives the M2 to M4 rewrites their safety net: a template may change every
 * Tailwind utility it carries, but if it drops `sonata-ba-list-field` or `objectId`, an
 * application's CSS, an application's JavaScript, or the forked packages' own functional tests stop
 * working — silently, because nothing else looks at a class name.
 *
 * The check is a static scan of the template sources rather than of rendered output: a hook inside
 * a branch that the demo application never takes is still part of the contract.
 */
final class HookContractTest extends ContractTestCase
{
    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function provideTheHooksOfAGroupArePresentCases(): iterable
    {
        foreach (self::hookGroups() as $name => $group) {
            yield $name => [$name, $group];
        }
    }

    /**
     * @param array<string, mixed> $group
     */
    #[DataProvider('provideTheHooksOfAGroupArePresentCases')]
    public function testTheHooksOfAGroupArePresent(string $name, array $group): void
    {
        \assert(\is_array($group['templates']) && \is_array($group['hooks']));

        if (true !== $group['enabled']) {
            static::markTestSkipped(\sprintf(
                'The %s templates are rewritten in milestone %s; the group is enabled there.',
                $name,
                \is_string($group['milestone'] ?? null) ? $group['milestone'] : '?'
            ));
        }

        $package = \is_string($group['package'] ?? null) ? $group['package'] : 'admin-bundle';
        $sources = '';

        foreach ($group['templates'] as $template) {
            \assert(\is_string($template));
            $file = self::templateFile($package, $template);

            static::assertFileExists($file, \sprintf('The %s group names a template that does not exist.', $name));

            $sources .= file_get_contents($file);
        }

        foreach ($group['hooks'] as $hook) {
            \assert(\is_string($hook));

            static::assertStringContainsString($hook, $sources, \sprintf(
                'The hook "%s" is gone from the %s templates. It is part of the contract in '
                .'PLAN/02 §8: applications select on it and the packages\' own tests assert it.',
                $hook,
                $name
            ));
        }
    }

    public function testEveryGroupOfThePlanIsCovered(): void
    {
        static::assertSame(
            ['buttons', 'flash', 'form_chrome', 'forms', 'list', 'list_row_actions', 'pager', 'shell', 'show'],
            array_keys(self::hookGroups())
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function hookGroups(): array
    {
        /** @var array<string, array<string, mixed>> $groups */
        $groups = Yaml::parseFile(__DIR__.'/hooks.yaml');
        ksort($groups);

        return $groups;
    }

    private static function templateFile(string $package, string $template): string
    {
        $views = 'doctrine-orm-admin-bundle' === $package ? self::ORM_VIEWS : 'src/Resources/views';

        return \sprintf('%s/%s/%s', self::root(), $views, $template);
    }
}
