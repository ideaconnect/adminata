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

namespace IDCT\Adminata\Tests\Event;

use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Builder\DatagridBuilderInterface;
use IDCT\Adminata\Builder\FormContractorInterface;
use IDCT\Adminata\Builder\ListBuilderInterface;
use IDCT\Adminata\Builder\ShowBuilderInterface;
use IDCT\Adminata\Datagrid\DatagridInterface;
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use IDCT\Adminata\Event\AdminEventExtension;
use IDCT\Adminata\Event\BatchActionEvent;
use IDCT\Adminata\Event\ConfigureEvent;
use IDCT\Adminata\Event\ConfigureQueryEvent;
use IDCT\Adminata\Event\PersistenceEvent;
use IDCT\Adminata\FieldDescription\FieldDescriptionCollection;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Show\ShowMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AdminEventExtensionTest extends TestCase
{
    /**
     * @param list<mixed> $args
     */
    public function getExtension(array $args): AdminEventExtension
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $stub = $eventDispatcher->expects(static::once())->method('dispatch');
        $stub->with(...$args);

        return new AdminEventExtension($eventDispatcher);
    }

    public function getConfigureEventClosure(string $type): callable
    {
        return static function (Event $event) use ($type): bool {
            if (!$event instanceof ConfigureEvent) {
                return false;
            }

            if ($event->getType() !== $type) {
                return false;
            }

            return true;
        };
    }

    public function getConfigurePersistenceClosure(string $type): callable
    {
        return static function (Event $event) use ($type): bool {
            if (!$event instanceof PersistenceEvent) {
                return false;
            }

            if ($event->getType() !== $type) {
                return false;
            }

            return true;
        };
    }

    public function testConfigureFormFields(): void
    {
        $this
            ->getExtension([
                static::callback($this->getConfigureEventClosure(ConfigureEvent::TYPE_FORM)),
                static::equalTo('adminata.admin.event.configure.form'),
            ])
            ->configureFormFields(new FormMapper(
                static::createStub(FormContractorInterface::class),
                static::createStub(FormBuilderInterface::class),
                static::createStub(AdminInterface::class)
            ));
    }

    public function testConfigureListFields(): void
    {
        $this
            ->getExtension([
                static::callback($this->getConfigureEventClosure(ConfigureEvent::TYPE_LIST)),
                static::equalTo('adminata.admin.event.configure.list'),
            ])
            ->configureListFields(new ListMapper(
                static::createStub(ListBuilderInterface::class),
                new FieldDescriptionCollection(),
                static::createStub(AdminInterface::class)
            ));
    }

    public function testConfigureDatagridFields(): void
    {
        $this
            ->getExtension([
                static::callback($this->getConfigureEventClosure(ConfigureEvent::TYPE_DATAGRID)),
                static::equalTo('adminata.admin.event.configure.datagrid'),
            ])
            ->configureDatagridFilters(new DatagridMapper(
                static::createStub(DatagridBuilderInterface::class),
                static::createStub(DatagridInterface::class),
                static::createStub(AdminInterface::class)
            ));
    }

    public function testConfigureShowFields(): void
    {
        $this
            ->getExtension([
                static::callback($this->getConfigureEventClosure(ConfigureEvent::TYPE_SHOW)),
                static::equalTo('adminata.admin.event.configure.show'),
            ])
            ->configureShowFields(new ShowMapper(
                static::createStub(ShowBuilderInterface::class),
                new FieldDescriptionCollection(),
                static::createStub(AdminInterface::class)
            ));
    }

    public function testPreUpdate(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_PRE_UPDATE)),
            static::equalTo('adminata.admin.event.persistence.pre_update'),
        ])->preUpdate($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testConfigureQuery(): void
    {
        $this->getExtension([
            static::isInstanceOf(ConfigureQueryEvent::class),
            static::equalTo('adminata.admin.event.configure.query'),
        ])->configureQuery($this->createMock(AdminInterface::class), $this->createMock(ProxyQueryInterface::class));
    }

    public function testPostUpdate(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_POST_UPDATE)),
            static::equalTo('adminata.admin.event.persistence.post_update'),
        ])->postUpdate($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testPrePersist(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_PRE_PERSIST)),
            static::equalTo('adminata.admin.event.persistence.pre_persist'),
        ])->prePersist($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testPostPersist(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_POST_PERSIST)),
            static::equalTo('adminata.admin.event.persistence.post_persist'),
        ])->postPersist($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testPreRemove(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_PRE_REMOVE)),
            static::equalTo('adminata.admin.event.persistence.pre_remove'),
        ])->preRemove($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testPostRemove(): void
    {
        $this->getExtension([
            static::callback($this->getConfigurePersistenceClosure(PersistenceEvent::TYPE_POST_REMOVE)),
            static::equalTo('adminata.admin.event.persistence.post_remove'),
        ])->postRemove($this->createMock(AdminInterface::class), new \stdClass());
    }

    public function testPreBatchAction(): void
    {
        $idx = [1, 2, 3];

        $this->getExtension([
            static::callback(
                static function (Event $event) use (&$idx): bool {
                    if (!$event instanceof BatchActionEvent) {
                        return false;
                    }

                    if (BatchActionEvent::TYPE_PRE_BATCH_ACTION !== $event->getType()) {
                        return false;
                    }

                    if ('delete' !== $event->getActionName()) {
                        return false;
                    }

                    $idx[] = 4; // Test if this was passed by reference correctly everywhere
                    if ($event->getIdx() !== $idx) {
                        return false;
                    }

                    return true;
                }
            ),
            static::equalTo('adminata.admin.event.batch_action.pre_batch_action'),
        ])->preBatchAction($this->createMock(AdminInterface::class), 'delete', $this->createMock(ProxyQueryInterface::class), $idx, false);
    }
}
