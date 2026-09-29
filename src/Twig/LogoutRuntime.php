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

namespace IDCT\Adminata\Twig;

use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Where the signed-in visitor signs out, for the sign-out button above the sidebar menu.
 *
 * The path is the one the firewall this request is behind configures, CSRF token included when it
 * asks for one — Symfony's own `logout_path()`, except that a firewall with no `logout` is not an
 * error: that function throws there and would take the whole layout with it, so this answers
 * `null` and the layout renders no button.
 */
final readonly class LogoutRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ?LogoutUrlGenerator $logoutUrlGenerator,
    ) {
    }

    public function getLogoutPath(): ?string
    {
        if (null === $this->logoutUrlGenerator) {
            return null;
        }

        try {
            return $this->logoutUrlGenerator->getLogoutPath();
        } catch (\InvalidArgumentException) {
            // No logout on this firewall, or no firewall in front of this request at all.
            return null;
        }
    }
}
