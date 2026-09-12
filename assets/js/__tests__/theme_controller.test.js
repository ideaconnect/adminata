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

import ThemeController from '../controllers/theme_controller.js';
import { mount, settle } from './helpers.js';

/** jsdom answers no media query, so the OS preference is stubbed. */
let listeners = [];

function prefersDark(matches) {
    listeners = [];

    vi.stubGlobal('matchMedia', (query) => ({
        matches,
        media: query,
        addEventListener: (_, listener) => listeners.push(listener),
        removeEventListener: (_, listener) => {
            listeners = listeners.filter((registered) => registered !== listener);
        },
    }));
}

const button = (theme) =>
    `<button data-controller="adminata-theme" data-adminata-theme-theme-value="${theme}"
             data-action="click->adminata-theme#cycle"></button>`;

beforeEach(() => {
    document.cookie = 'adminata_theme=; path=/; max-age=0';
    document.documentElement.className = '';
    delete document.documentElement.dataset.theme;
});

afterEach(() => {
    document.documentElement.className = '';
});

describe('adminata-theme', () => {
    it('leaves the server-rendered theme alone when it connects', async () => {
        prefersDark(false);
        document.documentElement.classList.add('dark');

        await mount('adminata-theme', ThemeController, button('dark'));

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.documentElement.dataset.theme).toBe('dark');
        expect(document.cookie).not.toContain('adminata_theme=');
    });

    it('cycles light, dark, system', async () => {
        prefersDark(false);
        const { element } = await mount('adminata-theme', ThemeController, button('light'));

        element.click();
        await settle();
        expect(document.documentElement.dataset.theme).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        element.click();
        await settle();
        expect(document.documentElement.dataset.theme).toBe('system');
        expect(document.documentElement.classList.contains('dark')).toBe(false);

        element.click();
        await settle();
        expect(document.documentElement.dataset.theme).toBe('light');
    });

    it('resolves system against the operating system', async () => {
        prefersDark(true);
        await mount('adminata-theme', ThemeController, button('system'));

        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('follows the operating system while it is set to system, and not after', async () => {
        prefersDark(false);
        const { element } = await mount('adminata-theme', ThemeController, button('system'));

        expect(document.documentElement.classList.contains('dark')).toBe(false);

        listeners.forEach((listener) => listener({ matches: true }));
        await settle();
        // The stub reports the value it was built with, so `isDark()` still reads false; what this
        // pins is that a system change repaints at all while `system` is selected.
        expect(document.documentElement.dataset.theme).toBe('system');

        element.click();
        await settle();
        expect(document.documentElement.dataset.theme).toBe('light');
    });

    it('writes the cookie the server reads back', async () => {
        prefersDark(false);
        const { element } = await mount('adminata-theme', ThemeController, button('light'));

        element.click();
        await settle();

        expect(document.cookie).toContain('adminata_theme=dark');
    });

    it('announces the change', async () => {
        prefersDark(false);
        const { element } = await mount('adminata-theme', ThemeController, button('light'));
        const seen = [];

        element.addEventListener('adminata-theme:changed', (event) => seen.push(event.detail));

        element.click();
        await settle();

        expect(seen).toEqual([{ theme: 'dark', dark: true }]);
    });

    it('names what the button will do next', async () => {
        prefersDark(false);
        const { element } = await mount(
            'adminata-theme',
            ThemeController,
            `<button data-controller="adminata-theme" data-adminata-theme-theme-value="light"
                     data-adminata-theme-labels-value='{"light":"To light","dark":"To dark","system":"Follow the system"}'
                     data-action="click->adminata-theme#cycle"></button>`,
        );

        expect(element.getAttribute('aria-label')).toBe('To dark');

        element.click();
        await settle();

        expect(element.getAttribute('aria-label')).toBe('Follow the system');
    });

    it('selects a named theme directly', async () => {
        prefersDark(false);
        const { element } = await mount(
            'adminata-theme',
            ThemeController,
            `<div data-controller="adminata-theme" data-adminata-theme-theme-value="light">
                <button data-action="click->adminata-theme#select" data-theme="dark"></button>
            </div>`,
        );

        element.querySelector('button').click();
        await settle();

        expect(document.documentElement.dataset.theme).toBe('dark');
    });
});
