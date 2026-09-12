/*!
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { getMetaContent } from './utils.js';

class Config {
    params = null;

    param(key) {
        if (this.params === null) {
            const content = getMetaContent('adminata-config');

            // A page without the meta tag — a login page, an application's own template extending
            // `empty_layout` — is not an error; every parameter is simply unset. Malformed JSON still
            // throws, because that is a mistake in the template.
            if (content === undefined) {
                return null;
            }

            try {
                this.params = JSON.parse(content);
            } catch (e) {
                throw new Error(
                    `An error has occurred resolving the "adminata-config" meta tag: ${e.message}.`,
                    { cause: e },
                );
            }
        }

        if (key in this.params) {
            return this.params[key];
        }

        return null;
    }
}

export default new Config();
