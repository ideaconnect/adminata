/*!
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import Config from '../core/config.js';
import Translation from '../core/translation.js';

export default class extends Controller {
    static values = {
        snapshot: String,
        skip: Boolean,
    };

    static get shouldLoad() {
        return Config.param('CONFIRM_EXIT');
    }

    connect() {
        this.snapshotValue = this.snapshot;
    }

    skip() {
        this.skipValue = true;
    }

    confirm(event) {
        if (!this.shouldConfirm) {
            return undefined;
        }

        const message = Translation.trans('CONFIRM_EXIT');

        // `preventDefault()` is what the specification asks for and what Chrome has required
        // since 119; `returnValue` is the legacy path other browsers still read. The message
        // itself is ignored by every current browser, which shows its own wording.
        event.preventDefault();
        event.returnValue = message;

        return message;
    }

    get shouldConfirm() {
        if (this.skipValue) {
            return false;
        }

        return this.snapshotValue !== this.snapshot;
    }

    get snapshot() {
        const params = new URLSearchParams(new FormData(this.element));
        return params.toString();
    }
}
