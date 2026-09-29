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

namespace IDCT\Adminata\Tests\Admin;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Admin\BreadcrumbsBuilder;
use IDCT\Adminata\Route\RouteGeneratorInterface;
use IDCT\Adminata\Translator\NativeLabelTranslatorStrategy;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * A screen is named by its title — for the admin itself, the label the sidebar shows unless a
 * title says otherwise — and only a screen with no title at all by its model class.
 */
final class BreadcrumbsBuilderTitlesTest extends TestCase
{
    public function testTheAdminsCrumbIsItsListTitle(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => 'Bags']), 'list');

        static::assertSame(['link_breadcrumb_dashboard', 'Bags'], self::names($breadcrumbs));
        static::assertSame(['translation_domain' => 'App'], $breadcrumbs[1]->getExtras(), 'Translated where the sidebar translates the label.');
        static::assertNull($breadcrumbs[1]->getUri(), 'The list page does not link to itself.');
    }

    public function testEveryPageOfTheAdminLinksItsTitledCrumbToTheList(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => 'Bags'], new \stdClass()), 'edit');

        static::assertSame(['link_breadcrumb_dashboard', 'Bags', 'WOR-1'], self::names($breadcrumbs));
        static::assertSame('/bags/list', $breadcrumbs[1]->getUri());
    }

    public function testACreatePageWithoutACreateTitleEndsOnItsOwnHeading(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => 'Bags']), 'create');

        static::assertSame(['link_breadcrumb_dashboard', 'Bags', 'title_create'], self::names($breadcrumbs));
        static::assertSame(['translation_domain' => 'AdminataBundle'], $breadcrumbs[2]->getExtras());
    }

    public function testACreateTitleNamesTheCreatePage(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => 'Bags', 'create' => 'New bag']), 'create');

        static::assertSame(['link_breadcrumb_dashboard', 'Bags', 'New bag'], self::names($breadcrumbs));
        static::assertSame(['translation_domain' => 'App'], $breadcrumbs[2]->getExtras());
    }

    public function testACustomRouteWithoutAnObjectEndsOnItsTitle(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => 'Test runs', 'launch' => 'Run a test']), 'launch');

        static::assertSame(['link_breadcrumb_dashboard', 'Test runs', 'Run a test'], self::names($breadcrumbs));
        static::assertSame(['translation_domain' => 'App'], $breadcrumbs[2]->getExtras());
    }

    public function testAScreenWithoutATitleIsNamedAfterTheModelClass(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin([]), 'launch');

        static::assertSame(['link_breadcrumb_dashboard', 'Caution Bag List', 'Caution Bag Launch'], self::names($breadcrumbs));
        static::assertSame(['translation_domain' => 'App'], $breadcrumbs[1]->getExtras());
    }

    public function testAnEmptyTitleNamesNothing(): void
    {
        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($this->admin(['list' => '', 'create' => '']), 'create');

        static::assertSame(['link_breadcrumb_dashboard', 'Caution Bag List', 'title_create'], self::names($breadcrumbs));
    }

    public function testAChildAdminsCrumbIsItsOwnTitle(): void
    {
        $subject = new \stdClass();
        $parent = $this->admin(['list' => 'Bags'], $subject);
        $child = $this->admin(['list' => 'Items'], null, 'CautionBagItem');

        $parent->method('getRequest')->willReturn(new Request(['id' => '7']));
        $parent->method('getIdParameter')->willReturn('id');
        $parent->method('getCurrentChildAdmin')->willReturn($child);
        $child->method('isChild')->willReturn(true);
        $child->method('getParent')->willReturn($parent);

        $breadcrumbs = new BreadcrumbsBuilder()->getBreadcrumbs($child, 'list');

        static::assertSame(['link_breadcrumb_dashboard', 'Bags', 'WOR-1', 'Items'], self::names($breadcrumbs));
        static::assertSame('/bags/list', $breadcrumbs[1]->getUri());
        static::assertSame('/bags/7/show', $breadcrumbs[2]->getUri());
        static::assertNull($breadcrumbs[3]->getUri());
    }

    /**
     * @param array<string, string> $titles
     *
     * @return AdminInterface<object>&Stub
     */
    private function admin(array $titles, ?object $subject = null, string $classname = 'CautionBag'): AdminInterface
    {
        $routeGenerator = static::createStub(RouteGeneratorInterface::class);
        $routeGenerator->method('generate')->willReturn('/dashboard');

        $admin = static::createStub(AdminInterface::class);
        $admin->method('getMenuFactory')->willReturn(new MenuFactory());
        $admin->method('getRouteGenerator')->willReturn($routeGenerator);
        $admin->method('getTitle')->willReturnCallback(static fn (string $action = 'list'): ?string => $titles[$action] ?? null);
        $admin->method('getTranslationDomain')->willReturn('App');
        $admin->method('getClassnameLabel')->willReturn($classname);
        $admin->method('getLabelTranslatorStrategy')->willReturn(new NativeLabelTranslatorStrategy());
        $admin->method('hasRoute')->willReturn(true);
        $admin->method('hasAccess')->willReturn(true);
        $admin->method('generateUrl')->willReturnCallback(
            static fn (string $name, mixed $parameters = []): string => \is_array($parameters) && isset($parameters['id']) && \is_string($parameters['id'])
                ? \sprintf('/bags/%s/%s', $parameters['id'], $name)
                : '/bags/'.$name
        );
        $admin->method('hasSubject')->willReturn(null !== $subject);

        if (null !== $subject) {
            $admin->method('getSubject')->willReturn($subject);
            $admin->method('toString')->willReturn('WOR-1');
        }

        return $admin;
    }

    /**
     * @param ItemInterface[] $breadcrumbs
     *
     * @return list<string>
     */
    private static function names(array $breadcrumbs): array
    {
        return array_values(array_map(static fn (ItemInterface $item): string => $item->getName(), $breadcrumbs));
    }
}
