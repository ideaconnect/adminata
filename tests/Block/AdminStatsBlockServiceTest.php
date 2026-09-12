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
use IDCT\Adminata\Block\AdminStatsBlockService;
use IDCT\Adminata\Test\BlockServiceTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AdminStatsBlockServiceTest extends BlockServiceTestCase
{
    private Pool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = new Pool(new Container());
    }

    public function testDefaultSettings(): void
    {
        $blockService = new AdminStatsBlockService($this->twig, $this->pool);
        $blockContext = $this->getBlockContext($blockService);

        self::assertSettings([
            'icon' => 'fas fa-chart-line',
            'text' => 'Statistics',
            'translation_domain' => null,
            'color' => 'bg-aqua',
            'code' => false,
            'filters' => [],
            'limit' => 1000,
            'template' => '@Adminata/Block/block_stats.html.twig',
        ], $blockContext);
    }
}
