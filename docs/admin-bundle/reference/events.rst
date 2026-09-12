Events
======

An event mechanism is available to add an extra entry point to extend an Admin instance.

ConfigureEvent
^^^^^^^^^^^^^^

This event is generated when a form, list, show, datagrid is configured. The event names are:

- ``adminata.admin.event.configure.form``
- ``adminata.admin.event.configure.list``
- ``adminata.admin.event.configure.datagrid``
- ``adminata.admin.event.configure.show``

PersistenceEvent
^^^^^^^^^^^^^^^^

This event is generated when a persistency layer update, save or delete an object. The event names are:

- ``adminata.admin.event.persistence.pre_update``
- ``adminata.admin.event.persistence.post_update``
- ``adminata.admin.event.persistence.pre_persist``
- ``adminata.admin.event.persistence.post_persist``
- ``adminata.admin.event.persistence.pre_remove``
- ``adminata.admin.event.persistence.post_remove``

ConfigureQueryEvent
^^^^^^^^^^^^^^^^^^^

This event is generated when a list query is defined. The event name is: ``adminata.admin.event.configure.query``

BlockEvent
^^^^^^^^^^

Block events help you customize your templates. Available events are :

- ``adminata.admin.dashboard.top``
- ``adminata.admin.dashboard.bottom``
- ``adminata.admin.list.table.top``
- ``adminata.admin.list.table.bottom``
- ``adminata.admin.edit.form.top``
- ``adminata.admin.edit.form.bottom``
- ``adminata.admin.show.top``
- ``adminata.admin.show.bottom``

If you want more information about block events, you should check
:doc:`block_events`.

BatchActionEvent
^^^^^^^^^^^^^^^^

This event is dispatched when a batch action is being executed. The event name is:

- ``adminata.admin.event.batch_action.pre_batch_action``
