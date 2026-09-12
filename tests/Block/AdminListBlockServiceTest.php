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

namespace IDCT\Adminata\Tests\Block;

use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Block\AdminListBlockService;
use IDCT\Adminata\Templating\TemplateRegistryInterface;
use IDCT\Adminata\Test\BlockServiceTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AdminListBlockServiceTest extends BlockServiceTestCase
{
    private Pool $pool;

    /**
     * @var TemplateRegistryInterface&MockObject
     */
    private TemplateRegistryInterface $templateRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = new Pool(new Container());
        $this->templateRegistry = $this->createMock(TemplateRegistryInterface::class);
    }

    public function testDefaultSettings(): void
    {
        $blockService = new AdminListBlockService($this->twig, $this->pool, $this->templateRegistry);
        $blockContext = $this->getBlockContext($blockService);

        self::assertSettings([
            'groups' => false,
        ], $blockContext);
    }
}
