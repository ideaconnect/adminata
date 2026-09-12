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

/**
 * A page on the admin layout with a control whose value reveals a section of the form.
 *
 * `adminata-reveal` is in the registry so that an application's admin classes can use it with three
 * attributes on a field; this is the page that proves it from nothing but markup, and what the
 * fixture for its test is dumped from. A plain form rather than an admin's, so that the demo
 * application's own screens — which the visual suite photographs — stay as they are.
 */
final class RevealDemoController extends AbstractController
{
    #[Route(path: '/admin/demo/reveal', name: 'demo_reveal', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('demo/reveal.html.twig');
    }
}
