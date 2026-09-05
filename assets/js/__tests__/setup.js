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

/*
 * jsdom has no ResizeObserver, which the readmore controller uses to re-measure its content. The
 * stub records the observed elements so a test can trigger a resize deliberately.
 */
class ResizeObserverStub {
    static instances = [];

    constructor(callback) {
        this.callback = callback;
        this.elements = new Set();
        ResizeObserverStub.instances.push(this);
    }

    observe(element) {
        this.elements.add(element);
    }

    unobserve(element) {
        this.elements.delete(element);
    }

    disconnect() {
        this.elements.clear();
    }

    /** Fires the callback as the browser would after a layout change. */
    trigger(element) {
        this.callback([{ target: element }], this);
    }
}

globalThis.ResizeObserver = ResizeObserverStub;
