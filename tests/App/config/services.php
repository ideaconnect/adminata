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

use Adminata\Tests\App\Admin\CategoryAdmin;
use Adminata\Tests\App\Admin\ProductAdmin;
use Adminata\Tests\App\Entity\Category;
use Adminata\Tests\App\Entity\Product;
use Adminata\Tests\App\EventListener\BrowserConsoleRecorderListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        ->load('Adminata\\Tests\\App\\DataFixtures\\', dirname(__DIR__).'/DataFixtures')
        ->load('Adminata\\Tests\\App\\EventListener\\', dirname(__DIR__).'/EventListener')
            ->exclude(dirname(__DIR__).'/EventListener/BrowserConsoleRecorderListener.php')
        ->load('Adminata\\Tests\\App\\Form\\', dirname(__DIR__).'/Form')
        ->load('Adminata\\Tests\\App\\Controller\\', dirname(__DIR__).'/Controller')
            ->tag('controller.service_arguments')

        ->set(ProductAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Product::class,
                'label' => 'Products',
                'group' => 'Catalogue',
                'icon' => '<i class="fa-solid fa-box"></i>',
                'default' => true,
            ])

        ->set(CategoryAdmin::class)
            ->tag('sonata.admin', [
                'manager_type' => 'orm',
                'model_class' => Category::class,
                'label' => 'Categories',
                'group' => 'Taxonomy',
                'icon' => '<i class="fa-solid fa-tags"></i>',
            ]);

    // Only where a browser test reads it back: it rewrites every HTML response, and `make demo`
    // has no reason to carry that. `browser` is the environment DemoServer runs a real HTTP server
    // in; `test` is BrowserKit, in process.
    if (in_array($container->env(), ['test', 'browser'], true)) {
        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->set(BrowserConsoleRecorderListener::class);
    }
};
