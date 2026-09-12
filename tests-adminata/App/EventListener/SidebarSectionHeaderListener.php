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

namespace Adminata\Tests\App\EventListener;

use Knp\Menu\ItemInterface;
use IDCT\Adminata\Event\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * recomaty-panel's SidebarMenuSubscriber (appendix C §2) injects non-link items carrying
 * `sidebar-section-header` before given groups. The demo reproduces it so that the sidebar
 * rewrite in M2 has the real shape to style, and so the contract test for the hook has
 * something that renders it.
 */
final class SidebarSectionHeaderListener
{
    /**
     * Dashboard group key => the header to insert before it. The key, not the label:
     * `GroupMenuProvider::get()` names the child after the group and sets the label separately.
     *
     * @var array<string, string>
     */
    private const array HEADERS = [
        'catalogue' => 'Shop',
        'settings' => 'System',
    ];

    #[AsEventListener(event: ConfigureMenuEvent::SIDEBAR)]
    public function __invoke(ConfigureMenuEvent $event): void
    {
        $menu = $event->getMenu();

        foreach (self::HEADERS as $group => $header) {
            if (null === $menu->getChild($group)) {
                continue;
            }

            $menu->addChild($header, ['attributes' => ['class' => 'sidebar-section-header']]);

            $menu->reorderChildren(self::order($menu->getChildren(), $header, $group));
        }
    }

    /**
     * The names of `$children` with `$header` moved directly in front of `$group`.
     *
     * @param array<string, ItemInterface> $children
     *
     * @return list<string>
     */
    private static function order(array $children, string $header, string $group): array
    {
        $order = [];

        foreach (array_keys($children) as $name) {
            if ($name === $header) {
                continue;
            }

            if ($name === $group) {
                $order[] = $header;
            }

            $order[] = $name;
        }

        return $order;
    }
}
