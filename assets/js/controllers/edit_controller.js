/*!
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'tabStore', 'errorMark'];

    connect() {
        // A tick later: `adminata-tabs` sits inside this form and connects after this controller
        // does, and the `adminata-tabs:show` sent to a tab before it listens is simply lost.
        this.reveal = setTimeout(() => {
            this.reveal = null;

            if (this.tabSelected) {
                this.showFirstTabWithErrors('.adminata-field-error');
            }
        }, 0);
    }

    disconnect() {
        if (null !== this.reveal) {
            clearTimeout(this.reveal);
            this.reveal = null;
        }
    }

    checkValidity() {
        if (this.tabSelected) {
            this.showFirstTabWithErrors(':invalid');
        }
    }

    prepareSubmit() {
        setTimeout(() => {
            this.submitters.forEach((submitter) => {
                submitter.disabled = true;
            });
        }, 1);

        if (this.tabSelected) {
            this.tabStoreTarget.value = this.tabSelected.getAttribute('aria-controls');
        }
    }

    showFirstTabWithErrors(errorSelector) {
        let firstTabWithErrors = null;

        this.tabTargets.forEach((tab) => {
            const pane = this.element.querySelector(
                `#${CSS.escape(tab.getAttribute('aria-controls') ?? '')}`,
            );
            const icon = tab.querySelector('[data-adminata-edit-target~="errorMark"]');

            if (pane && pane.querySelectorAll(errorSelector).length > 0) {
                // Only show first tab with errors
                if (!firstTabWithErrors) {
                    // Upstream showed the tab through Bootstrap's plugin here; `adminata-tabs`
                    // listens for this on its element and selects the tab it was sent on.
                    this.dispatch('show', { prefix: 'adminata-tabs', target: tab });
                    firstTabWithErrors = tab;
                }

                if (icon) {
                    icon.hidden = false;
                }
            } else if (icon) {
                icon.hidden = true;
            }
        });
    }

    changeTab(event) {
        const { history, location } = window;
        const { search, href, origin } = location;
        const tab = event.currentTarget;

        const searchParams = new URLSearchParams(search);
        searchParams.set('_tab', tab.getAttribute('aria-controls'));

        const url = new URL(href, origin);
        url.search = searchParams.toString();

        if (history) {
            history.pushState({ path: url.toString() }, '', url.toString());
        }
    }

    get tabSelected() {
        return this.tabTargets.find((tab) => 'true' === tab.getAttribute('aria-selected'));
    }

    get submitters() {
        return this.element.querySelectorAll('button');
    }
}
