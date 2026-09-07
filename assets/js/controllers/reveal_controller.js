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
 * A control whose value shows or hides other parts of the form.
 *
 * It sits on the control — a `<select>`, a checkbox, a radio group's wrapper — names what it
 * reveals with a selector in `target`, and says which value or values reveal it in `when`. Every
 * element the selector matches is shown while the control has one of those values and hidden
 * otherwise, with the `hidden` attribute, which the preflight honours over any display utility.
 * A `<select multiple>` counts as having each of its selected values.
 *
 * The selector is resolved from the nearest ancestor that holds both the control and a match, not
 * from the document. A group's class is the natural hook, and in a collection every row renders
 * the same group with the same class: resolved from the document, the last row's controller to
 * connect would decide every row's section. Resolved from the row, each decides its own.
 *
 * The first application happens on connect, so a saved value puts its section in the right state
 * without waiting for a change. Until then the page shows whatever the server rendered, and a form
 * that must not flash its section may render it with the `hidden` class: the controller removes
 * that class when it takes over, because from then on the attribute is the truth and a class left
 * behind would keep the section hidden for good.
 *
 * Hidden fields are still fields — they submit, and a `required` one still blocks the form. Keep
 * the required constraint on the master, or on the server, where it can see the whole picture.
 */
export default class extends Controller {
    static values = {
        target: String,
        when: String,
    };

    connect() {
        this.onChange = () => this.apply();
        this.element.addEventListener('change', this.onChange);
        this.apply();
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
    }

    apply() {
        const accepted = this.accepted();
        const shown = this.current().some((value) => accepted.includes(value));

        for (const section of this.sections()) {
            section.hidden = !shown;
            section.classList.remove('hidden');
        }
    }

    /**
     * `when` is one value, or a JSON list of them — `"1"` or `'["1","2"]'` — because a value
     * written by hand in an admin class is one thing, and one built by the Stimulus helper from
     * a PHP array is the other.
     *
     * @returns {string[]}
     */
    accepted() {
        const raw = this.whenValue.trim();

        if (raw.startsWith('[')) {
            try {
                const parsed = JSON.parse(raw);

                if (Array.isArray(parsed)) {
                    return parsed.map(String);
                }
            } catch {
                // Not a list after all: one value that happens to start with a bracket.
            }
        }

        return [raw];
    }

    /**
     * The value or values the control currently has.
     *
     * @returns {string[]}
     */
    current() {
        const element = this.element;

        if (element instanceof HTMLSelectElement) {
            return [...element.selectedOptions].map((option) => option.value);
        }

        if (element instanceof HTMLTextAreaElement) {
            return [element.value];
        }

        if (element instanceof HTMLInputElement) {
            if ('checkbox' === element.type || 'radio' === element.type) {
                return element.checked ? [element.value] : [];
            }

            return [element.value];
        }

        // A radio group's wrapper, or any container around the control that changed.
        return [...element.querySelectorAll('input:checked')]
            .filter((input) => input instanceof HTMLInputElement)
            .map((input) => input.value);
    }

    /**
     * @returns {Element[]}
     */
    sections() {
        if ('' === this.targetValue) {
            return [];
        }

        let scope = this.element.parentElement;

        while (null !== scope && null === scope.querySelector(this.targetValue)) {
            scope = scope.parentElement;
        }

        return [...(scope ?? document).querySelectorAll(this.targetValue)];
    }
}
