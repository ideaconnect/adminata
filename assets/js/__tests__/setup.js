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

/*
 * jsdom 30 knows the `<dialog>` element but implements none of its methods, so `showModal()` is
 * simply missing. This is the smallest thing that behaves like the specification for what
 * `sonata-modal` does with it: `open` reflects the attribute, `close()` fires `close`, and Escape
 * is `cancel` followed by `close` unless the page prevents it. The top layer, the focus trap and
 * the backdrop are the browser's, and the Panther test is what checks those.
 */
const dialog = globalThis.HTMLDialogElement?.prototype;

if (dialog !== undefined && 'function' !== typeof dialog.showModal) {
    Object.defineProperty(dialog, 'open', {
        configurable: true,
        get() {
            return this.hasAttribute('open');
        },
        set(value) {
            this.toggleAttribute('open', Boolean(value));
        },
    });

    dialog.show = function show() {
        this.setAttribute('open', '');
    };

    dialog.showModal = function showModal() {
        this.setAttribute('open', '');
    };

    dialog.close = function close(returnValue) {
        if (!this.hasAttribute('open')) {
            return;
        }

        this.removeAttribute('open');

        if (returnValue !== undefined) {
            this.returnValue = returnValue;
        }

        this.dispatchEvent(new Event('close'));
    };
}
