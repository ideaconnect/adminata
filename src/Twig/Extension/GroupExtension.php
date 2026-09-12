<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IDCT\Adminata\Twig\Extension;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Twig\GroupRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @phpstan-import-type Item from Pool
 */
final class GroupExtension extends AbstractExtension
{
    /**
     * NEXT_MAJOR: Remove this constructor.
     *
     * @internal This class should only be used through Twig
     */
    public function __construct(
        private GroupRuntime $groupRuntime,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_adminata_dashboard_groups_with_creatable_admins', [GroupRuntime::class, 'getDashboardGroupsWithCreatableAdmins']),
        ];
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @deprecated since sonata-project/admin-bundle version 4.7 use GroupRuntime::getDashboardGroupsWithCreatableAdmins() instead
     *
     * @phpstan-return array<array{
     *     label: string,
     *     label_catalogue?: string,
     *     translation_domain: string,
     *     icon: string,
     *     items: list<AdminInterface<object>>,
     *     keep_open: bool,
     *     on_top: bool,
     *     roles: list<string>,
     *     provider?: string,
     * }>
     */
    public function getDashboardGroupsWithCreatableAdmins(): array
    {
        @trigger_error(\sprintf(
            'The method "%s()" is deprecated since sonata-project/admin-bundle 4.7 and will be removed in 5.0.'
            .'  Use "%s::%s()" instead.',
            __METHOD__,
            GroupRuntime::class,
            __FUNCTION__
        ), \E_USER_DEPRECATED);

        return $this->groupRuntime->getDashboardGroupsWithCreatableAdmins();
    }
}
