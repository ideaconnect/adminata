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
 * A page on the admin layout that opens a dialog and carries one flash message of each type.
 *
 * `sonata-modal` is in the registry so that applications can use it on their own dialogs; this is
 * the page that proves it works from nothing but markup, and it is what the Panther test drives.
 * The flashes are here because a flash is otherwise only ever seen for one redirect: this gives
 * `sonata-dismiss` a page to be dumped from and the visual suite something to render. Three types,
 * because three is what `SonataTwigExtension` maps by default — `info` is not one of them.
 */
final class DialogDemoController extends AbstractController
{
    #[Route(path: '/admin/demo/dialog', name: 'demo_dialog', methods: ['GET'])]
    public function __invoke(): Response
    {
        foreach ([
            'sonata_flash_success' => 'The product was saved.',
            'sonata_flash_error' => 'The product could not be saved.',
            'warning' => 'Two variants share a label.',
        ] as $type => $message) {
            $this->addFlash($type, $message);
        }

        return $this->render('demo/dialog.html.twig');
    }
}
