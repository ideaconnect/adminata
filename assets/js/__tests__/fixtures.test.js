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
 * `data-adminata-batch-target`, so a rewrite that renames one breaks the controller silently. The
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
        identifier: 'adminata-layout',
        page: 'dashboard',
        selector: 'body',
        targets: ['sidebar', 'collapseOnly', 'overlay'],
    },
    {
        identifier: 'adminata-menu',
        page: 'dashboard',
        selector: 'nav[data-controller~="adminata-menu"]',
        targets: ['toggle'],
    },
    {
        identifier: 'adminata-theme',
        page: 'dashboard',
        selector: '[data-controller~="adminata-theme"]',
        targets: [],
    },
    {
        identifier: 'adminata-dropdown',
        page: 'dashboard',
        selector: '[data-controller~="adminata-dropdown"]',
        targets: ['toggle', 'menu'],
    },
    { identifier: 'adminata-sticky', page: 'dashboard', selector: 'body', targets: ['topNavbar', 'navbar'] },
    {
        identifier: 'adminata-dismiss',
        page: 'dialog',
        selector: '[data-controller~="adminata-dismiss"]',
        targets: [],
    },
    {
        identifier: 'adminata-modal',
        page: 'dialog',
        selector: '[data-controller~="adminata-modal"]',
        targets: ['dialog'],
    },
    {
        identifier: 'adminata-question',
        page: 'dialog',
        selector: '[data-controller~="adminata-question"]',
        targets: [],
    },
    {
        identifier: 'adminata-modal-trigger',
        page: 'dialog',
        selector: '#open-shared-content',
        targets: [],
    },
    {
        identifier: 'adminata-reveal',
        page: 'reveal',
        selector: '#demo-uses-map',
        targets: [],
    },
    {
        identifier: 'adminata-batch',
        page: 'product-list',
        selector: 'form[action*="batch"]',
        targets: ['all', 'row'],
    },
    {
        identifier: 'adminata-filter',
        page: 'product-list',
        selector: '[data-controller~="adminata-filter"]',
        targets: ['form', 'group', 'advanced', 'submitter'],
    },
    {
        identifier: 'adminata-filter-list',
        page: 'product-list',
        selector: '[data-controller~="adminata-filter-list"]',
        targets: ['counter', 'field'],
    },
    {
        identifier: 'adminata-per-page',
        page: 'product-list',
        selector: '[data-controller~="adminata-per-page"]',
        targets: [],
    },
    {
        identifier: 'adminata-readmore',
        page: 'product-list',
        selector: '[data-controller~="adminata-readmore"]',
        targets: ['content', 'button'],
    },
    {
        identifier: 'adminata-collection',
        page: 'product-edit',
        selector: '[data-controller~="adminata-collection"]',
        targets: ['item'],
    },
    {
        identifier: 'adminata-confirm-exit',
        page: 'product-create',
        selector: '[data-controller~="adminata-confirm-exit"]',
        targets: [],
    },
    {
        identifier: 'adminata-edit',
        page: 'product-create',
        selector: '[data-controller~="adminata-edit"]',
        targets: [],
    },
    {
        identifier: 'adminata-autocomplete',
        page: 'product-create',
        selector: '[data-controller~="adminata-autocomplete"]',
        targets: ['input', 'listbox', 'status', 'hiddenInputs', 'itemTemplate', 'chipTemplate'],
    },
    {
        identifier: 'adminata-autocomplete',
        page: 'category-list',
        selector: '[data-controller~="adminata-autocomplete"]',
        targets: ['input', 'listbox', 'status', 'hiddenInputs'],
    },
    {
        // The whole table: a bare `<tbody>` written into `document.body` is dropped by the parser.
        identifier: 'adminata-row-link',
        page: 'product-list',
        selector: 'table.adminata-list',
        targets: [],
    },
];

/**
 * `adminata-revision` is the one controller with no page here: it belongs to the history view, which
 * 1.0 inherits unported (PLAN/03 §E). Its suite keeps markup of its own until that page is
 * rewritten.
 */
const WITHOUT_A_PAGE = ['adminata-revision'];

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
