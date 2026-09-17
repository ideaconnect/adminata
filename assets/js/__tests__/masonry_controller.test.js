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

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import MasonryController from '../controllers/masonry_controller.js';
import { mount, settle } from './helpers.js';

/*
 * jsdom lays nothing out, so the browser's half of the mechanism — the auto-placement that puts
 * each item under the column that ended soonest — is stood in for by hand: `place()` says where
 * the browser "put" every item, and the tests assert what the controller does with that. The
 * placement itself is covered by the visual suite, which photographs the demo's product form in
 * three browsers at three widths.
 */

const GAP = 24;
let TRACKS = [300, 300, 300];

/** @type {Map<Element, {height: number, column: number}>} */
const layout = new Map();

const markup = (items, attributes = '') => `
    <div id="grid" data-controller="adminata-masonry" ${attributes}>
        ${items
            .map(
                ([id, className = '']) =>
                    `<div id="${id}" class="${className}" data-adminata-masonry-target="item"><p>${id}</p></div>`,
            )
            .join('')}
    </div>
`;

const grid = () => document.getElementById('grid');
const item = (id) => document.getElementById(id);

/**
 * Where the browser "placed" an item and how tall it is.
 *
 * @param {string} id
 * @param {number} height
 * @param {number} column 1-based
 */
const place = (id, height, column) => layout.set(item(id), { height, column });

const left = (column) => (column - 1) * (TRACKS[0] + GAP);

const computedStyle = (element) => {
    if (element === grid()) {
        return {
            columnGap: `${GAP}px`,
            gridTemplateColumns: TRACKS.map((track) => `${track}px`).join(' '),
            direction: 'ltr',
            paddingLeft: '0px',
            paddingRight: '0px',
        };
    }

    return {
        gridColumnStart:
            element.style.gridColumnStart ||
            (element.classList.contains('col-span-full')
                ? '1'
                : element.classList.contains('col-span-2')
                  ? 'span 2'
                  : 'auto'),
    };
};

const rect = function rect() {
    if (this === grid()) {
        return { left: 0, right: 3 * TRACKS[0] + 2 * GAP, height: 0 };
    }

    const placed = layout.get(this) ?? { height: 0, column: 1 };

    return {
        height: placed.height,
        width: TRACKS[0],
        left: left(placed.column),
        right: left(placed.column) + TRACKS[0],
    };
};

const observer = () => ResizeObserver.instances.at(-1);

/** What the browser does after a breakpoint: every item is resized at once. */
const resizeAll = () =>
    observer().callback(
        [...observer().elements].map((target) => ({ target })),
        observer(),
    );

const span = (height, unit = 4) => `span ${Math.ceil((height + GAP) / unit)}`;

/**
 * Packs again as the browser would after a resize. The controller repacks when an item's width
 * differs from the one it last packed at, so the tracks are nudged for one notification and
 * restored for a second, which leaves the pins reflecting `place()`.
 */
const repack = () => {
    const current = TRACKS;
    TRACKS = current.map((track) => track + 1);
    resizeAll();
    TRACKS = current;
    resizeAll();
};

describe('adminata-masonry', () => {
    beforeEach(() => {
        layout.clear();
        TRACKS = [300, 300, 300];
        ResizeObserver.instances.length = 0;
        vi.spyOn(globalThis, 'getComputedStyle').mockImplementation(computedStyle);
        vi.spyOn(Element.prototype, 'getBoundingClientRect').mockImplementation(rect);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('turns the grid into unit rows and spans every item over its height plus the gap', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['b'], ['c'], ['d']]));
        place('a', 300, 1);
        place('b', 120, 2);
        place('c', 200, 3);
        place('d', 90, 2);
        repack();

        expect(grid().style.gridAutoRows).toBe('4px');
        expect(grid().style.rowGap).toBe('0px');
        expect(item('a').style.alignSelf).toBe('start');
        expect(item('a').style.gridRowEnd).toBe(span(300));
        expect(item('b').style.gridRowEnd).toBe(span(120));
        expect(item('c').style.gridRowEnd).toBe(span(200));
        expect(item('d').style.gridRowEnd).toBe(span(90));
    });

    it('pins each item to the column the browser placed it in', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['b'], ['c'], ['d']]));
        place('a', 300, 1);
        place('b', 120, 2);
        place('c', 200, 3);
        place('d', 90, 2);
        // The connect-time pack ran before anything was "placed", so it pinned every item to the
        // first column; a notification without a width change leaves that alone.
        resizeAll();
        expect(item('d').style.gridColumnStart).toBe('1');

        repack();

        expect(item('a').style.gridColumnStart).toBe('1');
        expect(item('b').style.gridColumnStart).toBe('2');
        expect(item('c').style.gridColumnStart).toBe('3');
        expect(item('d').style.gridColumnStart).toBe('2');
        expect(item('d').dataset.adminataMasonryPinned).toBe('');
    });

    it('leaves an item that places itself alone, and keeps a span while pinning', async () => {
        await mount(
            'adminata-masonry',
            MasonryController,
            markup([['a'], ['wide', 'col-span-full'], ['double', 'col-span-2']]),
        );
        place('a', 100, 1);
        place('wide', 80, 1);
        place('double', 60, 2);
        repack();

        expect(item('wide').style.gridColumnStart).toBe('');
        expect('adminataMasonryPinned' in item('wide').dataset).toBe(false);
        expect(item('wide').style.gridRowEnd).toBe(span(80));
        expect(item('double').style.gridColumnStart).toBe('2');
    });

    it('re-spans a card that grew without moving anything else', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['b'], ['c'], ['d']]));
        place('a', 300, 1);
        place('b', 120, 2);
        place('c', 200, 3);
        place('d', 90, 2);
        repack();

        // A collection row was added to `b`; the browser would now put `d` elsewhere, but the pin holds.
        place('b', 400, 2);
        place('d', 90, 3);
        observer().trigger(item('b'));

        expect(item('b').style.gridRowEnd).toBe(span(400));
        expect(item('b').style.gridColumnStart).toBe('2');
        expect(item('d').style.gridColumnStart).toBe('2');
    });

    it('drops the pins and packs again when the items change width', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['b'], ['c'], ['d']]));
        place('a', 300, 1);
        place('b', 120, 2);
        place('c', 200, 3);
        place('d', 90, 2);
        repack();
        expect(item('c').style.gridColumnStart).toBe('3');

        // Below `xl`: two columns, and the browser lands `c` under `a` and `d` under `b`.
        TRACKS = [462, 462];
        place('c', 200, 1);
        place('d', 90, 2);
        resizeAll();

        expect(item('a').style.gridColumnStart).toBe('1');
        expect(item('b').style.gridColumnStart).toBe('2');
        expect(item('c').style.gridColumnStart).toBe('1');
        expect(item('d').style.gridColumnStart).toBe('2');
    });

    it('releases every pin before it reads the grid, so a stale pin cannot hold a column open', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['b'], ['c']]));
        place('a', 300, 1);
        place('b', 120, 2);
        place('c', 200, 3);
        repack();
        expect(item('c').style.gridColumnStart).toBe('3');

        // One column now. Reading the tracks with `c` still pinned to 3 would report the two
        // implicit columns that pin holds open; the controller must see one, and pin 1.
        let pinnedWhileReading = null;
        vi.spyOn(globalThis, 'getComputedStyle').mockImplementation((element) => {
            if (element === grid()) {
                pinnedWhileReading = item('c').style.gridColumnStart;

                return {
                    columnGap: `${GAP}px`,
                    gridTemplateColumns: '' === pinnedWhileReading ? '900px' : '900px 300px 300px',
                    direction: 'ltr',
                    paddingLeft: '0px',
                    paddingRight: '0px',
                };
            }

            return computedStyle(element);
        });
        TRACKS = [900];
        place('b', 120, 1);
        place('c', 200, 1);
        resizeAll();

        expect(pinnedWhileReading).toBe('');
        expect(item('c').style.gridColumnStart).toBe('1');
    });

    it('takes the row unit from its value', async () => {
        await mount(
            'adminata-masonry',
            MasonryController,
            markup([['a']], 'data-adminata-masonry-unit-value="8"'),
        );
        place('a', 100, 1);
        observer().trigger(item('a'));

        expect(grid().style.gridAutoRows).toBe('8px');
        expect(item('a').style.gridRowEnd).toBe(span(100, 8));
    });

    it('skips an item that is not laid out, such as a hidden group', async () => {
        await mount('adminata-masonry', MasonryController, markup([['a'], ['hidden']]));
        place('a', 100, 1);
        observer().trigger(item('hidden'));

        expect(item('hidden').style.gridRowEnd).toBe('');
    });

    it('leaves the grid as it found it on disconnect', async () => {
        const { element } = await mount('adminata-masonry', MasonryController, markup([['a'], ['b']]));
        const [a, b] = [item('a'), item('b')];
        place('a', 100, 1);
        place('b', 50, 2);
        repack();
        expect(b.style.gridColumnStart).toBe('2');

        element.remove();
        await settle();

        expect(element.style.gridAutoRows).toBe('');
        expect(element.style.rowGap).toBe('');
        expect(a.style.gridRowEnd).toBe('');
        expect(a.style.alignSelf).toBe('');
        expect(b.style.gridColumnStart).toBe('');
        expect('adminataMasonryPinned' in b.dataset).toBe(false);
    });
});
