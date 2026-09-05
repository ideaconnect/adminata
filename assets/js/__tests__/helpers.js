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

import { Application } from '@hotwired/stimulus';
import { onTestFinished } from 'vitest';

/**
 * Starts one controller against a piece of markup and waits for Stimulus to connect it.
 *
 * @param {string} identifier
 * @param {typeof import('@hotwired/stimulus').Controller} controller
 * @param {string} html
 * @returns {Promise<{application: Application, element: HTMLElement}>}
 */
export async function mount(identifier, controller, html) {
    document.body.innerHTML = html;

    const application = new Application(document.documentElement);
    application.register(identifier, controller);
    application.start();

    // Stimulus keeps a MutationObserver running; left alive it fires after the environment is
    // torn down, where the DOM globals no longer exist.
    onTestFinished(() => {
        application.stop();
        document.body.innerHTML = '';
    });

    // Stimulus connects on the next microtask after the mutation observer runs.
    await new Promise((resolve) => setTimeout(resolve, 0));

    return { application, element: document.querySelector(`[data-controller~="${identifier}"]`) };
}

/** Lets Stimulus process a DOM mutation or an event before the assertions run. */
export function settle() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
