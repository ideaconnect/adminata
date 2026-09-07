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

import { describe, expect, it } from 'vitest';

import { controllers } from '../registry.js';
import { mountFixture } from './helpers.js';

/**
 * Every controller connects to the markup the demo actually renders (PLAN/05 §9).
 *
 * A Stimulus controller is a contract with a template, and the template holds both ends of it:
 * the identifier, the targets, the actions and the values. Nothing in PHP looks at
 * `data-sonata-batch-target`, so a rewrite that renames one breaks the controller silently. The
 * behaviour suites beside this one use markup written for the case they exercise; this one uses
 * `tests-adminata/fixtures/js/*.html`, dumped from the demo by `JsFixtureDumperTest`, and asserts that each
 * controller finds what it reaches for.
 *
 * `make js-fixtures` re-dumps them.
 *
 * @type {ReadonlyArray<{identifier: string, page: string, selector: string, targets: string[]}>}
 */
const MOUNTS = [
    {
        identifier: 'sonata-layout',
        page: 'dashboard',
        selector: 'body',
        targets: ['sidebar', 'collapseOnly', 'overlay'],
    },
    {
        identifier: 'sonata-menu',
        page: 'dashboard',
        selector: 'nav[data-controller~="sonata-menu"]',
        targets: ['toggle'],
    },
    {
        identifier: 'sonata-theme',
        page: 'dashboard',
        selector: '[data-controller~="sonata-theme"]',
        targets: [],
    },
    {
        identifier: 'sonata-dropdown',
        page: 'dashboard',
        selector: '[data-controller~="sonata-dropdown"]',
        targets: ['toggle', 'menu'],
    },
    { identifier: 'sonata-sticky', page: 'dashboard', selector: 'body', targets: ['topNavbar', 'navbar'] },
    {
        identifier: 'sonata-dismiss',
        page: 'dialog',
        selector: '[data-controller~="sonata-dismiss"]',
        targets: [],
    },
    {
        identifier: 'sonata-modal',
        page: 'dialog',
        selector: '[data-controller~="sonata-modal"]',
        targets: ['dialog'],
    },
    {
        identifier: 'sonata-modal-trigger',
        page: 'dialog',
        selector: '#open-shared-content',
        targets: [],
    },
    {
        identifier: 'sonata-batch',
        page: 'product-list',
        selector: 'form[action*="batch"]',
        targets: ['all', 'row'],
    },
    {
        identifier: 'sonata-filter',
        page: 'product-list',
        selector: '[data-controller~="sonata-filter"]',
        targets: ['form', 'group', 'advanced', 'submitter'],
    },
    {
        identifier: 'sonata-filter-list',
        page: 'product-list',
        selector: '[data-controller~="sonata-filter-list"]',
        targets: ['counter', 'field'],
    },
    {
        identifier: 'sonata-per-page',
        page: 'product-list',
        selector: '[data-controller~="sonata-per-page"]',
        targets: [],
    },
    {
        identifier: 'sonata-readmore',
        page: 'product-list',
        selector: '[data-controller~="sonata-readmore"]',
        targets: ['content', 'button'],
    },
    {
        identifier: 'sonata-collection',
        page: 'product-edit',
        selector: '[data-controller~="sonata-collection"]',
        targets: ['item'],
    },
    {
        identifier: 'sonata-confirm-exit',
        page: 'product-create',
        selector: '[data-controller~="sonata-confirm-exit"]',
        targets: [],
    },
    {
        identifier: 'sonata-edit',
        page: 'product-create',
        selector: '[data-controller~="sonata-edit"]',
        targets: [],
    },
    {
        identifier: 'sonata-autocomplete',
        page: 'product-create',
        selector: '[data-controller~="sonata-autocomplete"]',
        targets: ['input', 'listbox', 'status', 'hiddenInputs', 'itemTemplate', 'chipTemplate'],
    },
    {
        identifier: 'sonata-autocomplete',
        page: 'category-list',
        selector: '[data-controller~="sonata-autocomplete"]',
        targets: ['input', 'listbox', 'status', 'hiddenInputs'],
    },
    {
        // The whole table: a bare `<tbody>` written into `document.body` is dropped by the parser.
        identifier: 'sonata-row-link',
        page: 'product-list',
        selector: 'table.sonata-ba-list',
        targets: [],
    },
];

/**
 * `sonata-revision` is the one controller with no page here: it belongs to the history view, which
 * 1.0 inherits unported (PLAN/03 §E). Its suite keeps markup of its own until that page is
 * rewritten.
 */
const WITHOUT_A_PAGE = ['sonata-revision'];

const capitalise = (name) => name.charAt(0).toUpperCase() + name.slice(1);

describe('the demo markup', () => {
    it.each(MOUNTS)(
        'connects $identifier on the $page page',
        async ({ identifier, page, selector, targets }) => {
            const { application, element } = await mountFixture(
                identifier,
                controllers[identifier],
                page,
                selector,
            );
            const controller = application.getControllerForElementAndIdentifier(element, identifier);

            expect(controller, `${identifier} did not connect to what ${page} renders.`).not.toBeNull();

            for (const target of targets) {
                expect(
                    controller[`has${capitalise(target)}Target`],
                    `${identifier} has no "${target}" target in what ${page} renders.`,
                ).toBe(true);
            }
        },
    );

    it('covers every controller the registry ships', () => {
        const mounted = new Set([...MOUNTS.map((entry) => entry.identifier), ...WITHOUT_A_PAGE]);

        expect([...mounted].sort()).toEqual(Object.keys(controllers).sort());
    });
});
