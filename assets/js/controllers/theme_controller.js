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
 * Light, dark, or whatever the operating system says.
 *
 * The server already decided which of the three the page is in — `ThemeRuntime` reads the
 * `adminata_theme` cookie and stamps `<html class="dark" data-theme>` before a byte is sent, so
 * there is no flash of the wrong theme (PLAN/01 C2). This controller only handles the click: it
 * writes the cookie the server will read next time and swaps the class immediately, so the page
 * does not have to reload for the change to show.
 *
 * `system` is deliberately part of the cycle rather than an initial state that disappears once a
 * visitor touches the toggle: a browser that follows the OS at sunset should keep doing so.
 */
const THEMES = ['light', 'dark', 'system'];

export default class extends Controller {
    static targets = ['label'];

    static values = {
        theme: { type: String, default: 'system' },
        cookieName: { type: String, default: 'adminata_theme' },
        labels: { type: Object, default: {} },
    };

    // Not `connect()`: Stimulus runs every `…ValueChanged` callback before it, and those repaint.
    initialize() {
        this.query = window.matchMedia('(prefers-color-scheme: dark)');

        // Stimulus runs every `…ValueChanged` callback before `connect()`, with the *default* as
        // the previous value rather than nothing, so there is no way to tell the initial call from
        // a real one by its arguments alone.
        this.connected = false;
    }

    connect() {
        this.onSystemChange = () => {
            if ('system' === this.themeValue) {
                this.paint();
            }
        };

        this.query.addEventListener('change', this.onSystemChange);
        this.render();
        this.connected = true;
    }

    disconnect() {
        this.query.removeEventListener('change', this.onSystemChange);
    }

    /** Light → dark → system → light. */
    cycle(event) {
        event?.preventDefault();

        this.themeValue = THEMES[(THEMES.indexOf(this.themeValue) + 1) % THEMES.length];
    }

    /** For a control that names the theme it selects, rather than cycling. */
    select(event) {
        event?.preventDefault();

        const theme = event?.params?.theme ?? event?.currentTarget?.dataset?.theme;

        if (THEMES.includes(theme)) {
            this.themeValue = theme;
        }
    }

    /**
     * The theme the server rendered is not a change: painting it is right, writing the cookie and
     * announcing a switch the visitor did not make is not.
     */
    themeValueChanged() {
        this.render();

        if (!this.connected) {
            return;
        }

        this.writeCookie();
        this.dispatch('changed', { detail: { theme: this.themeValue, dark: this.isDark() } });
    }

    render() {
        this.paint();

        this.element.setAttribute('aria-label', this.label());
        this.element.title = this.label();

        this.labelTargets.forEach((label) => {
            label.textContent = this.label();
        });
    }

    /** The only thing that touches the page: `<html>`'s class and its resolved `data-theme`. */
    paint() {
        const root = document.documentElement;

        root.dataset.theme = this.themeValue;
        root.classList.toggle('dark', this.isDark());
    }

    isDark() {
        return 'dark' === this.themeValue || ('system' === this.themeValue && this.query.matches);
    }

    /** What the button says it will do next; falls back to the theme name when untranslated. */
    label() {
        const next = THEMES[(THEMES.indexOf(this.themeValue) + 1) % THEMES.length];

        return this.labelsValue[next] ?? next;
    }

    writeCookie() {
        const name = encodeURIComponent(this.cookieNameValue);
        const secure = 'https:' === window.location.protocol ? '; Secure' : '';

        document.cookie = `${name}=${this.themeValue}; path=/; max-age=31536000; SameSite=Lax${secure}`;
    }
}
