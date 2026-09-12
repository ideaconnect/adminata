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

import { buildQueryString } from '../core/utils.js';

/**
 * The `adminata_type_model_autocomplete` widget, written by hand (PLAN/01 J5, PLAN/06 §3).
 *
 * select2 left with jQuery and nothing replaced it: this is the ARIA 1.2 combobox pattern over
 * `adminata_retrieve_autocomplete_items`, which is a few hundred lines and no dependency.
 *
 * The **hidden inputs are the only submitted state**. The combobox itself is a search box that the
 * form never names, so what the server receives is exactly what upstream's select2 sent — which is
 * what keeps a `ModelAutocompleteFilter`, a `ModelFilter` and a form field all working unchanged.
 */
export default class extends Controller {
    static targets = ['input', 'listbox', 'status', 'chips', 'hiddenInputs', 'itemTemplate', 'chipTemplate'];

    static values = {
        url: String,
        requestParameters: { type: Object, default: {} },
        searchParameter: { type: String, default: 'q' },
        pageParameter: { type: String, default: '_page' },
        perPageParameter: { type: String, default: '_per_page' },
        minLength: { type: Number, default: 3 },
        perPage: { type: Number, default: 10 },
        delay: { type: Number, default: 100 },
        multiple: Boolean,
        name: String,
        safeLabel: Boolean,
        disabled: Boolean,
        selected: { type: Array, default: [] },
        moreText: { type: String, default: 'More' },
        minLengthText: { type: String, default: '' },
        noResultsText: { type: String, default: '' },
        loadingText: { type: String, default: '' },
        removeText: { type: String, default: 'Remove' },
    };

    initialize() {
        this.items = [];
        this.active = -1;
        this.page = 1;
        this.more = false;
        this.query = '';
        this.timer = null;
        this.request = null;
    }

    connect() {
        // The chips are drawn here rather than by Twig so that one piece of code owns their markup;
        // the hidden inputs are what the server rendered, and they are what a submit carries.
        if (this.multipleValue && this.hasChipsTarget) {
            this.chipsTarget.replaceChildren(...this.selectedValue.map((item) => this.chip(item)));
        }

        this.onDocumentClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };

        document.addEventListener('click', this.onDocumentClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onDocumentClick);
        window.clearTimeout(this.timer);
        this.request?.abort();
        this.request = null;
    }

    /** Typing: debounce, then search — or say how many more characters are needed. */
    search() {
        window.clearTimeout(this.timer);

        // Editing the box in single mode means the previous choice no longer stands. Without this
        // an emptied filter would still submit the identifier the page was rendered with.
        if (!this.multipleValue && this.selectedIds().length > 0) {
            this.hiddenInputsTarget.replaceChildren(this.hiddenInput(''));
            this.dispatch('cleared');
        }

        const term = this.inputTarget.value.trim();

        if (term.length < this.minLengthValue) {
            this.query = term;
            this.items = [];
            this.more = false;
            this.render(this.minLengthMessage(term));

            return;
        }

        this.timer = window.setTimeout(() => this.load(term, 1), this.delayValue);
    }

    navigate(event) {
        const handlers = {
            ArrowDown: () => this.move(1),
            ArrowUp: () => this.move(-1),
            Home: () => this.moveTo(0),
            End: () => this.moveTo(this.optionCount() - 1),
            Enter: () => this.choose(),
            Escape: () => this.close(),
            Backspace: () => this.removeLast(),
        };

        const handler = handlers[event.key];

        if (undefined === handler) {
            return;
        }

        // Backspace only means "drop the last chip" while there is nothing left to delete.
        if ('Backspace' === event.key && ('' !== this.inputTarget.value || !this.multipleValue)) {
            return;
        }

        // Enter with nothing highlighted belongs to the form, which is how a filter panel is
        // submitted from the keyboard.
        if ('Enter' === event.key && -1 === this.active) {
            return;
        }

        event.preventDefault();
        handler();
    }

    async load(term, page) {
        this.query = term;
        this.page = page;

        if (1 === page) {
            this.announce(this.loadingTextValue);
        }

        this.request?.abort();

        const request = new AbortController();
        this.request = request;

        const parameters = {
            ...this.requestParametersValue,
            [this.searchParameterValue]: term,
            [this.pageParameterValue]: page,
            [this.perPageParameterValue]: this.perPageValue,
        };

        let response;

        try {
            response = await fetch(`${this.urlValue}?${buildQueryString(parameters)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: request.signal,
            });
        } catch (error) {
            if ('AbortError' === error.name) {
                return;
            }

            throw error;
        }

        // The action answers 403 when the term is shorter than the admin's own minimum, which is
        // the same situation as having typed too little and reads better said that way.
        if (403 === response.status) {
            this.items = [];
            this.more = false;
            this.render(this.minLengthMessage(term));

            return;
        }

        const data = await response.json();
        const items = Array.isArray(data.items) ? data.items : [];

        this.items = 1 === page ? items : [...this.items, ...items];
        this.more = true === data.more;
        this.render(0 === this.items.length ? this.noResultsTextValue : '');
    }

    choose() {
        const item = this.items[this.active];

        if (undefined === item) {
            // The one option that is not an item is "load more".
            if (this.more && this.active === this.items.length) {
                this.load(this.query, this.page + 1);
            }

            return;
        }

        this.select(item);
    }

    /** Clicking an option. `mousedown` would move focus off the input before the click lands. */
    pick(event) {
        event.preventDefault();

        this.active = Number(event.currentTarget.dataset.adminataAutocompleteIndex);
        this.choose();
    }

    select(item) {
        if (!this.multipleValue) {
            this.hiddenInputsTarget.replaceChildren(this.hiddenInput(item.id));
            this.inputTarget.value = this.plainLabel(item);
        } else {
            if (this.selectedIds().includes(String(item.id))) {
                this.close();

                return;
            }

            this.hiddenInputsTarget.append(this.hiddenInput(item.id));
            this.chipsTarget.append(this.chip(item));
            this.inputTarget.value = '';
        }

        this.items = [];
        this.more = false;
        this.close();
        this.announce('');
        this.dispatch('selected', { detail: { id: item.id, label: this.labelOf(item) } });
    }

    remove(event) {
        event.preventDefault();

        this.removeId(
            event.currentTarget.closest('[data-adminata-autocomplete-id]').dataset.adminataAutocompleteId,
        );
        this.inputTarget.focus();
    }

    removeLast() {
        const ids = this.selectedIds();

        if (0 === ids.length) {
            return;
        }

        this.removeId(ids[ids.length - 1]);
    }

    removeId(id) {
        this.hiddenInputs()
            .filter((input) => input.value === String(id))
            .forEach((input) => input.remove());

        if (this.hasChipsTarget) {
            [...this.chipsTarget.children]
                .filter((chip) => chip.dataset.adminataAutocompleteId === String(id))
                .forEach((chip) => chip.remove());
        }

        this.dispatch(this.multipleValue ? 'removed' : 'cleared', { detail: { id } });
    }

    render(message) {
        const options = this.items.map((item, index) => this.option(item, index, false));

        if (this.more) {
            options.push(this.option({ label: this.moreTextValue }, options.length, true));
        }

        this.listboxTarget.replaceChildren(...options);
        this.open(options.length > 0);
        this.highlight();
        this.announce(message);
    }

    option(item, index, more) {
        const element = this.itemTemplateTarget.content.firstElementChild.cloneNode(true);

        element.id = `${this.inputTarget.id}_option_${index}`;
        element.dataset.adminataAutocompleteIndex = String(index);
        element.setAttribute('role', 'option');
        element.setAttribute('aria-selected', 'false');
        this.write(element, this.labelOf(item), more);

        return element;
    }

    chip(item) {
        const element = this.chipTemplateTarget.content.firstElementChild.cloneNode(true);

        element.dataset.adminataAutocompleteId = String(item.id);
        this.write(element.querySelector('[data-label]') ?? element, this.labelOf(item), false);
        element
            .querySelector('[data-remove]')
            ?.setAttribute('aria-label', `${this.removeTextValue} ${this.plainLabel(item)}`.trim());

        return element;
    }

    /**
     * `safe_label` is the option that says the server sends markup. Without it the label is text,
     * and `textContent` is what keeps a name containing `<` from becoming an element.
     */
    write(element, label, plain) {
        if (this.safeLabelValue && !plain) {
            element.innerHTML = label;

            return;
        }

        element.textContent = label;
    }

    labelOf(item) {
        return String(item.label ?? item.text ?? '');
    }

    /**
     * A text input holds text, so a `safe_label` selection is flattened. `<template>` content is
     * inert — nothing in it loads, runs or renders — which is what makes this safe to parse.
     */
    plainLabel(item) {
        const label = this.labelOf(item);

        if (!this.safeLabelValue) {
            return label;
        }

        const parser = document.createElement('template');
        parser.innerHTML = label;

        return parser.content.textContent.trim();
    }

    hiddenInput(id) {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = this.multipleValue ? `${this.nameValue}[]` : this.nameValue;
        input.value = String(id);
        input.disabled = this.disabledValue;

        return input;
    }

    hiddenInputs() {
        return [...this.hiddenInputsTarget.querySelectorAll('input')];
    }

    selectedIds() {
        return this.hiddenInputs()
            .map((input) => input.value)
            .filter((value) => '' !== value);
    }

    optionCount() {
        return this.listboxTarget.querySelectorAll('[role="option"]').length;
    }

    move(direction) {
        const count = this.optionCount();

        if (0 === count) {
            return;
        }

        this.moveTo((this.active + direction + count) % count);
    }

    moveTo(index) {
        this.active = index;
        this.open(this.optionCount() > 0);
        this.highlight();
    }

    highlight() {
        const options = [...this.listboxTarget.querySelectorAll('[role="option"]')];

        options.forEach((option, index) =>
            option.setAttribute('aria-selected', String(index === this.active)),
        );

        const current = options[this.active];

        // Removed rather than emptied: `aria-activedescendant` holds an id, and an empty one is
        // a reference to nothing.
        if (undefined === current) {
            this.inputTarget.removeAttribute('aria-activedescendant');

            return;
        }

        this.inputTarget.setAttribute('aria-activedescendant', current.id);
        current.scrollIntoView?.({ block: 'nearest' });
    }

    open(open) {
        this.listboxTarget.hidden = !open;
        this.inputTarget.setAttribute('aria-expanded', String(open));
    }

    close() {
        this.active = -1;
        this.open(false);
        this.inputTarget.removeAttribute('aria-activedescendant');
    }

    /** The live region: "loading", "no result", "type three more characters". */
    announce(message) {
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = message ?? '';
        }
    }

    minLengthMessage(term) {
        return this.minLengthTextValue.replace(
            '%count%',
            String(Math.max(0, this.minLengthValue - term.length)),
        );
    }
}
