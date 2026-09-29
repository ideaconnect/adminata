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
 * The row of controls above the sidebar menu: the dashboard, collapse and expand every group, and
 * sign-out.
 *
 * Only the middle two need JavaScript, and the server renders them `hidden`: they are shown once an
 * `adminata-menu` outlet has connected and holds at least one group, so a page without JavaScript —
 * or a menu with nothing to fold — never offers a button that does nothing. The two links on either
 * side work without any of this.
 *
 * The menu owns its groups and what is remembered about them; this only asks it to act.
 */
export default class extends Controller {
    static targets = ['collapse', 'expand'];

    static outlets = ['adminata-menu'];

    connect() {
        this.sync();
    }

    adminataMenuOutletConnected() {
        this.sync();
    }

    adminataMenuOutletDisconnected() {
        this.sync();
    }

    collapseAll() {
        this.adminataMenuOutlets.forEach((menu) => menu.collapseAll());
    }

    expandAll() {
        this.adminataMenuOutlets.forEach((menu) => menu.expandAll());
    }

    /** Offers the two menu buttons only while there is a group for them to act on. */
    sync() {
        const groups = this.adminataMenuOutlets.some((menu) => menu.hasToggleTarget);

        [...this.collapseTargets, ...this.expandTargets].forEach((button) => {
            button.hidden = !groups;
        });
    }
}
