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

import { describe, expect, it } from 'vitest';

import contract from '../__contract__/controllers.json';
import { controllers } from '../registry.js';

/*
 * PLAN/02 §9: the Stimulus identifiers, targets, values, classes and outlets are a promise.
 * Applications put these names in their own templates and their own controllers reach for them,
 * so removing one is a major release — which is worth failing a build over.
 */
describe('the Stimulus contract', () => {
    it('registers exactly the identifiers it promises', () => {
        expect(Object.keys(controllers).sort()).toEqual(Object.keys(contract.controllers).sort());
    });

    it.each(Object.entries(contract.controllers))('keeps the surface of %s', (identifier, promised) => {
        const controller = controllers[identifier];

        expect([...(controller.targets ?? [])].sort()).toEqual(promised.targets);
        expect(Object.keys(controller.values ?? {}).sort()).toEqual(promised.values);
        expect([...(controller.classes ?? [])].sort()).toEqual(promised.classes);
        expect([...(controller.outlets ?? [])].sort()).toEqual(promised.outlets);
    });

    it('ships every promised identifier in the built bundle', () => {
        const bundle = readFileSync('src/Resources/public/app.js', 'utf8');

        for (const identifier of Object.keys(contract.controllers)) {
            expect(bundle, `${identifier} is missing from the built app.js`).toContain(identifier);
        }
    });

    it('ships no jQuery in the built bundle', () => {
        const bundle = readFileSync('src/Resources/public/app.js', 'utf8');

        expect(bundle).not.toMatch(/\bjQuery\b/);
    });
});
