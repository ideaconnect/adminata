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

import { Controller } from '@hotwired/stimulus';

/**
 * Clicking a list row opens the object.
 *
 * The destination is computed in `list_outer_rows_list.html.twig` from `default_admin_route` and
 * written onto the `<tr>`, so a row the administrator may not open carries no URL and this does
 * nothing. The controller sits on the `<tbody>` rather than on every row: one listener for a page
 * of results instead of one Stimulus instance per row.
 *
 * This is a pointer convenience layered on markup that already works. The row is not given
 * `tabindex` or `role="link"` — the accessible name of such a control would be the whole row, and
 * the same destination is one Tab away in the identifier cell or the action column. Keyboard and
 * screen-reader users lose nothing by this controller not existing.
 *
 * The things a click on a row can mean other than "open it" are all honoured: a click that lands
 * on a control — or anywhere in a cell that exists only to hold controls — belongs to that
 * control, a click that ends a text selection was a selection, and a middle-click or a modified
 * click opens a new tab the way it would on a link.
 */
const INTERACTIVE = 'a, button, input, select, textarea, label, summary, [role="button"], [contenteditable]';

/**
 * Cells whose whole area belongs to their controls: the batch checkbox is aimed at, not hit, and
 * the gaps between row actions are part of the action column, not of the row.
 */
const RESERVED = '.sonata-ba-list-field-batch, .sonata-ba-list-field-select, .sonata-ba-list-field-actions';

export default class extends Controller {
    /**
     * @param {MouseEvent} event
     */
    open(event) {
        // `auxclick` also fires for the right button, which belongs to the context menu.
        if (0 !== event.button && 1 !== event.button) {
            return;
        }

        const row =
            event.target instanceof Element ? event.target.closest('tr[data-sonata-row-link-url]') : null;

        if (null === row || !this.element.contains(row)) {
            return;
        }

        if (null !== event.target.closest(INTERACTIVE) || null !== event.target.closest(RESERVED)) {
            return;
        }

        if (this.hasSelection) {
            return;
        }

        const url = row.dataset.sonataRowLinkUrl;

        event.preventDefault();

        if (1 === event.button || event.ctrlKey || event.metaKey || event.shiftKey) {
            window.open(url, '_blank', 'noopener');

            return;
        }

        window.location.assign(url);
    }

    /**
     * True while the pointer has selected text, which is what the click was for.
     */
    get hasSelection() {
        const selection = window.getSelection();

        return null !== selection && !selection.isCollapsed && '' !== selection.toString().trim();
    }
}
