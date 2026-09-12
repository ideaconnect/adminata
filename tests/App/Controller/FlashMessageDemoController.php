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

namespace IDCT\Adminata\Tests\App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Renders a page that puts the session's flash bag through the flash-message template.
 */
final class FlashMessageDemoController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render('flash.html.twig'));
    }
}
