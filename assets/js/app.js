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

import { Application } from '@hotwired/stimulus';

import { register } from './registry.js';

/**
 * One Stimulus application owns every `sonata-*` controller. Applications resolve identifiers
 * independently, so an application may run its own alongside this one; see PLAN/05 §5.
 */
const application = Application.start();

application.debug = document.documentElement.dataset.sonataDebug === 'true';

register(application);

window.sonataApplication = application;

// Progressive enhancement marker: stylesheets can key off `html.no-js` until this runs.
document.documentElement.classList.remove('no-js');

export { application };
