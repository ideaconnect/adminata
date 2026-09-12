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

namespace IDCT\Adminata\Menu;

use IDCT\Adminata\Admin\Pool;
use IDCT\Adminata\Event\ConfigureMenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Sonata menu builder.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author Alexandru Furculita <alex@furculita.net>
 */
final class MenuBuilder
{
    public function __construct(
        private Pool $pool,
        private FactoryInterface $factory,
        private MenuProviderInterface $provider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Builds sidebar menu.
     */
    public function createSidebarMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        foreach ($this->pool->getAdminGroups() as $name => $group) {
            $extras = [
                'icon' => $group['icon'],
                'translation_domain' => $group['translation_domain'],
                'label_catalogue' => $group['label_catalogue'] ?? '', // NEXT_MAJOR: Remove this line.
                'roles' => $group['roles'],
                'adminata_admin' => true,
            ];

            $menuProvider = $group['provider'] ?? 'adminata_group_menu';
            $subMenu = $this->provider->get(
                $menuProvider,
                [
                    'name' => $name,
                    'group' => $group,
                ]
            );

            $subMenu = $menu->addChild($subMenu);
            $subMenu->setExtras(array_merge($subMenu->getExtras(), $extras));
        }

        $event = new ConfigureMenuEvent($this->factory, $menu);
        $this->eventDispatcher->dispatch($event, ConfigureMenuEvent::SIDEBAR);

        return $event->getMenu();
    }
}
