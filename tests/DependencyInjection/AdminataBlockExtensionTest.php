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

namespace IDCT\Adminata\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use IDCT\Adminata\DependencyInjection\AdminataBlockExtension;

final class AdminataBlockExtensionTest extends AbstractExtensionTestCase
{
    public function testLoadDefault(): void
    {
        $this->setParameter('kernel.bundles', []);
        $this->load();

        $this->assertContainerBuilderHasService('adminata.block.service.container');
        $this->assertContainerBuilderHasService('adminata.block.service.empty');
        $this->assertContainerBuilderHasService('adminata.block.service.text');
        $this->assertContainerBuilderHasService('adminata.block.service.rss');
        $this->assertContainerBuilderHasService('adminata.block.service.template');

        $this->assertContainerBuilderNotHasService('adminata.block.service.menu');
    }

    public function testLoadWithKnpMenuBundle(): void
    {
        $this->setParameter('kernel.bundles', ['KnpMenuBundle' => true]);
        $this->load();

        $this->assertContainerBuilderHasService('adminata.block.service.menu');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new AdminataBlockExtension(),
        ];
    }
}
