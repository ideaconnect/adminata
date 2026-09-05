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
 * A page on `empty_layout`: the shell with the sidebar, the header and the breadcrumb taken out.
 *
 * The visual and accessibility suites walk it, because a layout with none of its targets is where
 * `sonata-layout` and the stylesheet are most likely to assume something that is not there.
 */
final class EmptyLayoutDemoController extends AbstractController
{
    #[Route(path: '/admin/demo/empty', name: 'demo_empty_layout', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('demo/empty.html.twig');
    }
}
