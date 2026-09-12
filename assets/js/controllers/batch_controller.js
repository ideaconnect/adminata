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

const SELECTED = 'adminata-list-row-selected';

/**
 * The list's batch selection, in place of the twelve lines of jQuery upstream printed into a
 * `<script>` inside the page.
 *
 * Three things: the header checkbox selects everything and shows an indeterminate state when only
 * some rows are, a selected row is marked with the `adminata-list-row-selected` class an
 * application's CSS may select on, and holding shift extends the selection from the last row
 * clicked.
 *
 * Upstream's shift-range only worked downwards: the upward half of its condition read
 * `indexedDB > currentIndex` — the browser's IndexedDB global, not the loop's index — so it was
 * never true.
 */
export default class extends Controller {
    static targets = ['all', 'row'];

    connect() {
        this.lastIndex = null;
        this.render();
    }

    toggleAll() {
        if (!this.hasAllTarget) {
            return;
        }

        this.rowTargets.forEach((row) => {
            row.checked = this.allTarget.checked;
        });

        this.lastIndex = null;
        this.render();
    }

    toggleRow(event) {
        const index = this.rowTargets.indexOf(event.currentTarget);

        if (-1 === index) {
            return;
        }

        if (event.shiftKey && null !== this.lastIndex) {
            const [from, to] = [this.lastIndex, index].sort((a, b) => a - b);

            for (let i = from; i <= to; i += 1) {
                this.rowTargets[i].checked = event.currentTarget.checked;
            }
        }

        this.lastIndex = index;
        this.render();
    }

    render() {
        this.rowTargets.forEach((row) => {
            // `div.adminata-list-field-batch` and not the bare class: in a table the checkbox's
            // own `<td>` carries it, and the row is what should look selected. The mosaic list has
            // no `<tr>`, which is what the `div` is for.
            row.closest('tr, div.adminata-list-field-batch')?.classList.toggle(SELECTED, row.checked);
        });

        if (!this.hasAllTarget) {
            return;
        }

        const checked = this.rowTargets.filter((row) => row.checked).length;

        this.allTarget.checked = checked > 0 && checked === this.rowTargets.length;
        this.allTarget.indeterminate = checked > 0 && checked < this.rowTargets.length;
    }
}
