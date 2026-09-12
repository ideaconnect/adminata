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

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Block\AdminPreviewBlockService;
use IDCT\Adminata\Datagrid\DatagridInterface;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\FieldDescription\FieldDescriptionCollection;
use IDCT\Adminata\Test\BlockServiceTestCase;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;

/**
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class AdminPreviewBlockServiceTest extends BlockServiceTestCase
{
    private Pool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pool = new Pool(new Container());
    }

    public function testDefaultSettings(): void
    {
        $blockService = new AdminPreviewBlockService(static::createStub(Environment::class), $this->pool);
        $blockContext = $this->getBlockContext($blockService);

        self::assertSettings([
            'text' => 'Preview',
            'filters' => [],
            'icon' => false,
            'limit' => 10,
            'code' => false,
            'template' => '@Adminata/Block/block_admin_preview.html.twig',
            'remove_list_fields' => [ListMapper::NAME_ACTIONS],
        ], $blockContext);
    }

    public function testBlockExecution(): void
    {
        $adminCode = 'admin.bar';
        $responseContent = '<div>AdminBlockPreview</div>';

        $admin = $this->createMock(AdminInterface::class);
        $admin
            ->method('getCode')
            ->willReturn($adminCode);

        $container = new Container();
        $container->set($adminCode, $admin);
        $pool = new Pool($container, [$adminCode]);
        $datagrid = static::createStub(DatagridInterface::class);
        $twig = $this->createMock(Environment::class);

        $blockService = new AdminPreviewBlockService($twig, $pool);
        $blockContext = $this->getBlockContext($blockService)->setSetting('code', 'admin.bar');

        $admin->expects(static::once())->method('checkAccess')->with('list');
        $admin->expects(static::exactly(2))->method('getDatagrid')->willReturn($datagrid);
        $admin->expects(static::once())->method('getList')->willReturn(new FieldDescriptionCollection());
        $twig->expects(static::once())->method('render')->willReturn($responseContent);

        $response = $blockService->execute($blockContext);

        static::assertSame($responseContent, $response->getContent());
        static::assertSame(200, $response->getStatusCode());
    }
}
