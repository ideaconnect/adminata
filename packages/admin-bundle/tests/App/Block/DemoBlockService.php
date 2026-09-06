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

namespace Sonata\AdminBundle\Tests\App\Block;

use Sonata\AdminBundle\Block\Service\AbstractBlockService;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DemoBlockService extends AbstractBlockService
{
    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'template' => 'block/demo.html.twig',
        ]);
    }
}
