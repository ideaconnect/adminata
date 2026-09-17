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
 * Tabs — the WAI-ARIA tabs pattern over the `role="tablist"` / `role="tab"` / `role="tabpanel"`
 * markup of a tabbed form or show page (PLAN/05 §4).
 *
 * The state is `aria-selected` on the tabs and `hidden` on the panels, and nothing else: the
 * stylesheet draws the underline from the attribute, so a tab looks selected exactly when
 * assistive technology is told it is. A tab is a link to its panel (`href="#id"`, which the
 * click handler stops from scrolling) rather than a button, so that a disabled `<fieldset>` an
 * application locks a read-only form with cannot disable the tabs along with the fields and
 * strand every panel but the first. Every tab names its panel in `aria-controls`; the selected
 * tab is the one in the tab order (`tabindex="0"`), the others are reached with the arrow keys,
 * Home and End, and moving to a tab selects it — automatic activation, which is what a form
 * wants: the panel is the thing being looked for.
 *
 * `adminata-tabs:show`, dispatched ON a tab, selects it from outside — `adminata-edit` sends it
 * for the first tab holding a field with an error, on load and again when the browser reports an
 * invalid field, so the browser can focus a field the person could not otherwise see.
 * `adminata-tabs:shown` goes out after every change, with the tab and its panel; a card packed by
 * `adminata-masonry` inside a panel needs nothing from it — being shown resizes its items, and
 * that is what it packs on.
 *
 * The address bar is not this controller's business: `adminata-edit` writes the selected tab
 * into `?_tab=` and the `_tab` field the redirect after a save carries, and the template reads
 * it back by the tab's index.
 */
export default class extends Controller {
    static targets = ['tab', 'panel'];

    connect() {
        this.onShow = (event) => {
            const tab = this.tabTargets.find(
                (candidate) => candidate === event.target || candidate.contains(event.target),
            );

            if (tab) {
                this.show(tab);
            }
        };
        this.element.addEventListener('adminata-tabs:show', this.onShow);

        const selected = this.selected ?? this.tabTargets[0];

        if (selected) {
            this.show(selected);
        }
    }

    disconnect() {
        this.element.removeEventListener('adminata-tabs:show', this.onShow);
    }

    /**
     * @param {Event} event a click on a tab
     */
    select(event) {
        event.preventDefault();
        this.show(event.currentTarget);
    }

    /**
     * Arrow keys, Home and End move between the tabs and select the one reached.
     *
     * @param {KeyboardEvent} event
     */
    move(event) {
        const tabs = this.tabTargets;
        const index = tabs.indexOf(event.currentTarget);

        if (-1 === index) {
            return;
        }

        const targets = {
            ArrowLeft: (index - 1 + tabs.length) % tabs.length,
            ArrowRight: (index + 1) % tabs.length,
            Home: 0,
            End: tabs.length - 1,
        };

        if (!(event.key in targets)) {
            return;
        }

        event.preventDefault();

        const tab = tabs[targets[event.key]];
        this.show(tab);
        tab.focus();
    }

    /**
     * @param {HTMLElement} tab
     */
    show(tab) {
        const id = tab.getAttribute('aria-controls');

        for (const candidate of this.tabTargets) {
            const current = candidate === tab;

            candidate.setAttribute('aria-selected', current ? 'true' : 'false');
            candidate.setAttribute('tabindex', current ? '0' : '-1');
        }

        let panel = null;

        for (const candidate of this.panelTargets) {
            const current = candidate.id === id;

            candidate.hidden = !current;

            if (current) {
                panel = candidate;
            }
        }

        this.dispatch('shown', { detail: { tab, panel } });
    }

    /** @returns {HTMLElement|undefined} the tab the server rendered as selected */
    get selected() {
        return this.tabTargets.find((tab) => 'true' === tab.getAttribute('aria-selected'));
    }
}
