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
 * Dismisses the thing it is mounted on, in place of Bootstrap's `data-dismiss="alert"`.
 *
 * It removes the element rather than hiding it: a dismissed flash message has nothing left to say,
 * and a hidden one still occupies the accessibility tree. `remove: false` keeps it in the document
 * for anything that needs to find it again.
 */
export default class extends Controller {
    static values = {
        remove: { type: Boolean, default: true },
    };

    dismiss(event) {
        event?.preventDefault();

        this.dispatch('dismissed');

        if (this.removeValue) {
            this.element.remove();

            return;
        }

        this.element.hidden = true;
    }
}
