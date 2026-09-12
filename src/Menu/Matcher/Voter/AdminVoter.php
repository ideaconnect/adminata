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

namespace IDCT\Adminata\Menu\Matcher\Voter;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\BCLayer\BCHelper;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Admin menu voter based on extra `admin`.
 *
 * @author Samusev Andrey <andrey.simfi@ya.ru>
 */
final class AdminVoter implements VoterInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        $admin = $item->getExtra('admin');
        if (
            $admin instanceof AdminInterface
            && $admin->hasRoute('list') && $admin->hasAccess('list')
            && $this->match($admin, BCHelper::getFromRequest($request, '_adminata_admin'))
        ) {
            return true;
        }

        $route = $item->getExtra('route');
        if (null !== $route && $route === BCHelper::getFromRequest($request, '_route')) {
            return true;
        }

        return null;
    }

    /**
     * @param AdminInterface<object> $admin
     */
    private function match(AdminInterface $admin, mixed $requestCode): bool
    {
        if ($admin->getBaseCodeRoute() === $requestCode) {
            return true;
        }

        return array_any($admin->getChildren(), fn ($child) => $this->match($child, $requestCode));
    }
}
