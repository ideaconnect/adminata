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

namespace IDCT\Adminata\Event;

use IDCT\Adminata\Admin\AbstractAdminExtension;
use IDCT\Adminata\Admin\AdminInterface;
use IDCT\Adminata\Datagrid\DatagridMapper;
use IDCT\Adminata\Datagrid\ListMapper;
use IDCT\Adminata\Datagrid\ProxyQueryInterface;
use IDCT\Adminata\Form\FormMapper;
use IDCT\Adminata\Show\ShowMapper;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * @phpstan-extends AbstractAdminExtension<object>
 */
final class AdminEventExtension extends AbstractAdminExtension
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function configureFormFields(FormMapper $form): void
    {
        $this->eventDispatcher->dispatch(
            new ConfigureEvent($form->getAdmin(), $form, ConfigureEvent::TYPE_FORM),
            'adminata.admin.event.configure.form'
        );
    }

    public function configureListFields(ListMapper $list): void
    {
        $this->eventDispatcher->dispatch(
            new ConfigureEvent($list->getAdmin(), $list, ConfigureEvent::TYPE_LIST),
            'adminata.admin.event.configure.list'
        );
    }

    public function configureDatagridFilters(DatagridMapper $filter): void
    {
        $this->eventDispatcher->dispatch(
            new ConfigureEvent($filter->getAdmin(), $filter, ConfigureEvent::TYPE_DATAGRID),
            'adminata.admin.event.configure.datagrid'
        );
    }

    public function configureShowFields(ShowMapper $show): void
    {
        $this->eventDispatcher->dispatch(
            new ConfigureEvent($show->getAdmin(), $show, ConfigureEvent::TYPE_SHOW),
            'adminata.admin.event.configure.show'
        );
    }

    public function configureQuery(AdminInterface $admin, ProxyQueryInterface $query, string $context = 'list'): void
    {
        $this->eventDispatcher->dispatch(
            new ConfigureQueryEvent($admin, $query, $context),
            'adminata.admin.event.configure.query'
        );
    }

    public function preUpdate(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_UPDATE),
            'adminata.admin.event.persistence.pre_update'
        );
    }

    public function postUpdate(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_UPDATE),
            'adminata.admin.event.persistence.post_update'
        );
    }

    public function prePersist(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_PERSIST),
            'adminata.admin.event.persistence.pre_persist'
        );
    }

    public function postPersist(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_PERSIST),
            'adminata.admin.event.persistence.post_persist'
        );
    }

    public function preRemove(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_PRE_REMOVE),
            'adminata.admin.event.persistence.pre_remove'
        );
    }

    public function postRemove(AdminInterface $admin, object $object): void
    {
        $this->eventDispatcher->dispatch(
            new PersistenceEvent($admin, $object, PersistenceEvent::TYPE_POST_REMOVE),
            'adminata.admin.event.persistence.post_remove'
        );
    }

    public function preBatchAction(AdminInterface $admin, string $actionName, ProxyQueryInterface $query, array &$idx, bool $allElements): void
    {
        $this->eventDispatcher->dispatch(
            new BatchActionEvent($admin, BatchActionEvent::TYPE_PRE_BATCH_ACTION, $actionName, $query, $idx, $allElements),
            'adminata.admin.event.batch_action.pre_batch_action'
        );
    }
}
