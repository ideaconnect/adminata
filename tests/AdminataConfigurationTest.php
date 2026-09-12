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

namespace IDCT\Adminata\Tests;

use PHPUnit\Framework\TestCase;
use IDCT\Adminata\AdminataConfiguration;

final class AdminataConfigurationTest extends TestCase
{
    private AdminataConfiguration $configuration;

    protected function setUp(): void
    {
        $this->configuration = new AdminataConfiguration('title', '/path/to/logo.png', [
            'confirm_exit' => true,
            'default_admin_route' => 'show',
            'default_group' => 'default',
            'default_icon' => '<i class="fas fa-folder"></i>',
            'default_translation_domain' => 'AdminataBundle',
            'dropdown_number_groups_per_colums' => 2,
            'form_type' => 'standard',
            'html5_validate' => true,
            'javascripts' => [],
            'js_debug' => false,
            'list_action_button_content' => 'all',
            'list_row_link' => true,
            'lock_protection' => false,
            'logo_content' => 'text',
            'mosaic_background' => 'bundles/adminata/images/default_mosaic_image.png',
            'pager_links' => null,
            'role_admin' => 'ROLE_ADMINATA_ADMIN',
            'role_super_admin' => 'ROLE_SUPER_ADMIN',
            'search' => true,
            'sort_admins' => true,
            'stylesheets' => [],
            'theme' => ['mode' => 'system', 'logo_dark' => null, 'logo_icon' => null],
            'use_stickyforms' => false,
        ]);
    }

    public function testGetTitle(): void
    {
        static::assertSame('title', $this->configuration->getTitle());
    }

    public function testGetLogo(): void
    {
        static::assertSame('/path/to/logo.png', $this->configuration->getLogo());
    }

    public function testGetOption(): void
    {
        static::assertTrue($this->configuration->getOption('html5_validate'));
        static::assertFalse($this->configuration->getOption('lock_protection'));
    }

    public function testGetOptionDefault(): void
    {
        static::assertNull($this->configuration->getOption('pager_links'));
        static::assertSame(1, $this->configuration->getOption('pager_links', 1));
    }
}
