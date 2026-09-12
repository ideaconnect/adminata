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
 * The shell: the sidebar's two states and the header's mobile menu.
 *
 * It replaces AdminLTE's push-menu and `sidebar.js`. Mounted on `<body>`, which is also the
 * element the CSS keys off — `data-sidebar` is `expanded` or `collapsed`, `data-sidebar-mobile` is
 * `open` or `closed` — so the server can seed both from the `adminata_sidebar_hide` cookie and the
 * first paint is already right. Nothing here reads the DOM to decide state; it writes it.
 *
 * The two states are separate on purpose. Above the breakpoint the sidebar is always present and
 * "collapsed" means the 90px rail, which is a preference worth remembering in a cookie. Below it
 * the sidebar is a drawer that is closed on arrival, and remembering that it was open would be
 * wrong. Upstream conflated the two into one cookie; TailAdmin conflates them into one flag whose
 * meaning flips at the breakpoint. Neither is what a reader expects.
 *
 * Every target is optional: `empty_layout` renders a page with no sidebar, no overlay and no
 * toggle at all.
 */
export default class extends Controller {
    static targets = [
        'sidebar',
        'overlay',
        'toggle',
        'collapseOnly',
        'content',
        'headerMenu',
        'headerMenuToggle',
    ];

    static values = {
        collapsed: { type: Boolean, default: false },
        mobileOpen: { type: Boolean, default: false },
        headerMenuOpen: { type: Boolean, default: false },
        breakpoint: { type: Number, default: 1024 },
        cookieName: { type: String, default: 'adminata_sidebar_hide' },
    };

    // Not `connect()`: Stimulus runs every `…ValueChanged` callback before it, and those repaint.
    initialize() {
        this.query = window.matchMedia(`(min-width: ${this.breakpointValue}px)`);

        // Stimulus runs every `…ValueChanged` callback before `connect()`, with the *default* as
        // the previous value rather than nothing, so there is no way to tell the initial call from
        // a real one by its arguments alone.
        this.connected = false;
    }

    connect() {
        // `event.matches` rather than `query.matches`: the event carries the state the change is
        // announcing, and reading it back off the list is a race with whatever else is resizing.
        this.onBreakpointChange = (event) => {
            // Coming back to the wide layout, a drawer left open would sit over a sidebar that is
            // already there.
            if (event.matches) {
                this.mobileOpenValue = false;
            }
        };

        this.query.addEventListener('change', this.onBreakpointChange);
        this.render();
        this.connected = true;
    }

    disconnect() {
        this.query.removeEventListener('change', this.onBreakpointChange);
    }

    /** The hamburger: the drawer below the breakpoint, the rail above it. */
    toggleSidebar(event) {
        event?.preventDefault();

        if (this.query.matches) {
            this.collapsedValue = !this.collapsedValue;
        } else {
            this.mobileOpenValue = !this.mobileOpenValue;
        }
    }

    /** The wide-layout hamburger, which only ever means the rail. */
    toggleCollapsed(event) {
        event?.preventDefault();

        this.collapsedValue = !this.collapsedValue;
    }

    closeSidebar(event) {
        event?.preventDefault();

        this.mobileOpenValue = false;
    }

    toggleHeaderMenu(event) {
        event?.preventDefault();

        this.headerMenuOpenValue = !this.headerMenuOpenValue;
    }

    /**
     * The state the server rendered is not a change: rewriting the cookie and announcing it on
     * every page load would make both meaningless.
     */
    collapsedValueChanged() {
        this.render();

        if (!this.connected) {
            return;
        }

        this.writeCookie();
        this.announceSidebar();
    }

    mobileOpenValueChanged() {
        this.render();

        if (this.connected) {
            this.announceSidebar();
        }
    }

    headerMenuOpenValueChanged() {
        this.render();

        if (this.connected) {
            this.dispatch('header-menu-changed', { detail: { open: this.headerMenuOpenValue } });
        }
    }

    announceSidebar() {
        this.dispatch('sidebar-changed', {
            detail: { collapsed: this.collapsedValue, mobileOpen: this.mobileOpenValue },
        });
    }

    render() {
        this.element.dataset.sidebar = this.collapsedValue ? 'collapsed' : 'expanded';
        this.element.dataset.sidebarMobile = this.mobileOpenValue ? 'open' : 'closed';

        if (this.hasOverlayTarget) {
            this.overlayTarget.hidden = !this.mobileOpenValue;
        }

        if (this.hasContentTarget) {
            // Everything behind the drawer is out of reach of the keyboard and of a screen reader
            // while it is open; without this, tabbing walks straight past the drawer into it.
            this.contentTarget.inert = this.mobileOpenValue;
        }

        if (this.hasHeaderMenuTarget) {
            this.headerMenuTarget.hidden = !this.headerMenuOpenValue;
        }

        this.toggleTargets.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(this.mobileOpenValue));
        });

        this.collapseOnlyTargets.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(!this.collapsedValue));
        });

        this.headerMenuToggleTargets.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(this.headerMenuOpenValue));
        });
    }

    /**
     * The cookie upstream already used, so an application that reads it keeps working. It records
     * the rail, not the drawer: `Max-Age` a year when collapsed, expired when not.
     */
    writeCookie() {
        const name = encodeURIComponent(this.cookieNameValue);
        const secure = 'https:' === window.location.protocol ? '; Secure' : '';

        document.cookie = this.collapsedValue
            ? `${name}=1; path=/; max-age=31536000; SameSite=Lax${secure}`
            : `${name}=; path=/; max-age=0; SameSite=Lax${secure}`;
    }
}
