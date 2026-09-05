Porting status
==============

adminata rewrote the screens its 1.0 scope covers. **Thirty-seven templates are inherited from
upstream unported**: they still render Bootstrap markup, which is now unstyled, so they look broken
rather than merely dated.

This is deliberate, and it is visible rather than hidden. Each of those files carries a
``{# adminata: not yet ported #}`` marker on its first line, they are listed in
``tests/Contract/deferred-templates.txt``, and ``DeferredTemplateTest`` checks that the list, the
markers and the files on disk agree — so one cannot quietly appear, or quietly disappear.

They are ported **on demand**. Speculatively rewriting a screen nobody uses is how a fork acquires
markup no one has ever looked at. If you need one, open an issue saying which and what you use it
for, and it gets done against a real case.

Association edit flows
----------------------

Eleven templates, plus the ``ModelListType`` widget that drives them.

These are deferred rather than merely unfinished: they are built on AJAX form submission, which
adminata does not have and will not add. Redesigning the flow without it is a design decision, not
a translation of markup, and it wants a real application to design against.

.. code-block:: text

    CRUD/Association/edit_many_script
    CRUD/Association/edit_many_to_many
    CRUD/Association/edit_many_to_one
    CRUD/Association/edit_modal
    CRUD/Association/edit_one_script
    CRUD/Association/edit_one_to_many
    CRUD/Association/edit_one_to_many_inline_table
    CRUD/Association/edit_one_to_many_inline_tabs
    CRUD/Association/edit_one_to_many_sortable_script_table
    CRUD/Association/edit_one_to_many_sortable_script_tabs
    CRUD/Association/edit_one_to_one
    Form/Type/sonata_type_model_list
    Helper/short-object-description

``ModelAutocompleteType`` is **not** in this list: it was rewritten as an ARIA 1.2 combobox and is
the supported way to pick a related object in 1.0.

History and revision compare
----------------------------

The audit trail. Ported when the first application that reads revisions asks for it.

.. code-block:: text

    CRUD/base_history
    CRUD/base_show_compare
    CRUD/history
    CRUD/history_revision_timestamp
    CRUD/show_compare

ACL
---

The per-object permission editor, reached with ``security.handler: acl``.

.. code-block:: text

    CRUD/acl
    CRUD/base_acl
    CRUD/base_acl_macro

Other CRUD pages
----------------

Preview mode, the subclass chooser, the tree view, the mosaic and flat list modes, and the generic
custom-action page.

.. code-block:: text

    CRUD/action
    CRUD/base_list_flat_field
    CRUD/base_list_flat_inner_row
    CRUD/list_outer_rows_mosaic
    CRUD/preview
    CRUD/select_subclass
    CRUD/tree

Global search and the tab menu
------------------------------

The search results page, reached with ``search: true``, and the tab menu a child admin or
``configureTabMenu()`` renders.

.. code-block:: text

    Core/search
    Core/tab_menu_template

Dashboard and block templates
-----------------------------

Ported on first use of the block type.

.. code-block:: text

    Block/block_admin_preview
    Block/block_audit
    Block/block_core_rss
    Block/block_rss_dashboard
    Block/block_search_result
    Block/block_side_menu_template
    Block/block_stats

What "not yet ported" means in practice
---------------------------------------

The page **works**. The controller, the form, the security checks and the routing are adminata's
and are tested; only the markup is upstream's. A deferred screen renders its content with no
styling — unstyled tables, unstyled buttons — inside adminata's shell.

If that is unacceptable for a screen you need today, you have three options, in increasing order of
effort: override the template in your application and style it yourself; open an issue; or send a
pull request rewriting it against the ``.adm-*`` components, removing the marker and its line from
``deferred-templates.txt`` in the same commit.
