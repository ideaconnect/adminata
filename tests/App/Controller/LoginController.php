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

namespace Adminata\Tests\App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The demo's login form.
 *
 * `http_basic` alone is enough for BrowserKit and for Playwright, which both send the header, but
 * not for a real browser: credentials in a URL are the only way to hand them to one, and Firefox
 * treats a repeat of that as something to confirm — a modal that blocks WebDriver until it times
 * out. A form logs in once and the session cookie carries the rest, which is also what an
 * application actually has.
 */
final class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'demo_login', methods: ['GET', 'POST'])]
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
