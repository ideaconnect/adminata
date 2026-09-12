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

import { readFileSync } from 'node:fs';

import { Application } from '@hotwired/stimulus';
import { onTestFinished } from 'vitest';

import Config from '../core/config.js';
import Translation from '../core/translation.js';

const pages = new Map();

/**
 * A piece of what the demo application actually renders.
 *
 * `tests-adminata/fixtures/js/*.html` is dumped from the demo by `JsFixtureDumperTest` (PLAN/05 §9), so a
 * template that renames a target or drops an action fails here rather than in a browser. Re-dump
 * with `make js-fixtures`.
 *
 * @param {string} page file name under `tests-adminata/fixtures/js`, without the extension
 * @param {string} selector what to take out of it
 * @returns {string} the outer HTML of the first match
 */
export function fixture(page, selector) {
    return element(page, selector).outerHTML;
}

/**
 * Mounts one controller on the real markup, with the page's own `adminata-config` and
 * `adminata-translations` in the head — which is where `Config.param()` and `Translation.trans()`
 * read from, and what decides whether a controller loads at all.
 *
 * @param {string} identifier
 * @param {typeof import('@hotwired/stimulus').Controller} controller
 * @param {string} page
 * @param {string} selector
 * @returns {Promise<{application: Application, element: HTMLElement}>}
 */
export async function mountFixture(identifier, controller, page, selector) {
    Config.params = null;
    Translation.messages = null;

    const source = parse(page);
    document.head.replaceChildren(
        ...[...source.querySelectorAll('meta[name^="adminata-"]')].map((meta) => meta.cloneNode(true)),
    );

    const target = source.querySelector(selector);

    if (null === target) {
        throw new Error(`The ${page} fixture has no "${selector}". Re-dump it with \`make js-fixtures\`.`);
    }

    // A `<body>` cannot be nested inside the document's own, so its attributes and children are
    // copied onto it instead.
    if (target === source.body) {
        for (const { name, value } of [...target.attributes]) {
            document.body.setAttribute(name, value);
        }

        return start(identifier, controller, target.innerHTML, () => document.body);
    }

    return start(identifier, controller, target.outerHTML, () =>
        document.querySelector(`[data-controller~="${identifier}"]`),
    );
}

/**
 * The parsed page, kept.
 *
 * Nothing here mutates it — a caller takes `outerHTML` and Stimulus mounts a copy — and parsing
 * the list fixture is a quarter of a megabyte of HTML, which at once per mount is most of the
 * suite's running time.
 */
function parse(page) {
    if (!pages.has(page)) {
        pages.set(
            page,
            new DOMParser().parseFromString(
                readFileSync(`tests-adminata/fixtures/js/${page}.html`, 'utf8'),
                'text/html',
            ),
        );
    }

    return pages.get(page);
}

function element(page, selector) {
    const found = parse(page).querySelector(selector);

    if (null === found) {
        throw new Error(`The ${page} fixture has no "${selector}". Re-dump it with \`make js-fixtures\`.`);
    }

    return found;
}

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

    return start(identifier, controller, null, () =>
        document.querySelector(`[data-controller~="${identifier}"]`),
    );
}

/**
 * Starts one controller and waits for Stimulus to connect it.
 *
 * @param {string} identifier
 * @param {typeof import('@hotwired/stimulus').Controller} controller
 * @param {string|null} html written into the body first, when given
 * @param {() => HTMLElement} locate
 * @returns {Promise<{application: Application, element: HTMLElement}>}
 */
async function start(identifier, controller, html, locate) {
    if (null !== html) {
        document.body.innerHTML = html;
    }

    const application = new Application(document.documentElement);

    // Stimulus swallows a controller error into `console.error`, where Node's inspector then
    // trips over jsdom's DOM objects. A controller that throws should fail the test instead.
    application.handleError = (error, message) => {
        throw new Error(`${message}: ${error.message}`, { cause: error });
    };

    application.register(identifier, controller);
    application.start();

    // Stimulus keeps a MutationObserver running; left alive it fires after the environment is
    // torn down, where the DOM globals no longer exist.
    onTestFinished(async () => {
        // `stop()` is asynchronous; without awaiting it the observers outlive the test and touch
        // a document jsdom has already torn down.
        await application.stop();
        document.body.innerHTML = '';
    });

    // Stimulus connects on the next microtask after the mutation observer runs.
    await new Promise((resolve) => setTimeout(resolve, 0));

    return { application, element: locate() };
}

/** Lets Stimulus process a DOM mutation or an event before the assertions run. */
export function settle() {
    return new Promise((resolve) => setTimeout(resolve, 0));
}
