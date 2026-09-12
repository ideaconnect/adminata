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

import AutocompleteController from './controllers/autocomplete_controller.js';
import BatchController from './controllers/batch_controller.js';
import CollectionController from './controllers/collection_controller.js';
import ConfirmExitController from './controllers/confirm_exit_controller.js';
import DismissController from './controllers/dismiss_controller.js';
import DropdownController from './controllers/dropdown_controller.js';
import EditController from './controllers/edit_controller.js';
import FilterController from './controllers/filter_controller.js';
import FilterListController from './controllers/filter_list_controller.js';
import LayoutController from './controllers/layout_controller.js';
import MenuController from './controllers/menu_controller.js';
import ModalController from './controllers/modal_controller.js';
import ModalTriggerController from './controllers/modal_trigger_controller.js';
import PerPageController from './controllers/per_page_controller.js';
import QuestionController from './controllers/question_controller.js';
import ReadmoreController from './controllers/readmore_controller.js';
import RevealController from './controllers/reveal_controller.js';
import RevisionController from './controllers/revision_controller.js';
import RowLinkController from './controllers/row_link_controller.js';
import StickyController from './controllers/sticky_controller.js';
import ThemeController from './controllers/theme_controller.js';

/**
 * Every controller is registered here by hand: no `stimulus-bridge`, no `require.context`, so the
 * built bundle contains exactly what this file names and `__contract__/controllers.json` can be
 * checked against it.
 *
 * The nine inherited from Sonata are below, and the new ones join them as M2 to M4 land.
 *
 * @type {Record<string, typeof import('@hotwired/stimulus').Controller>}
 */
export const controllers = {
    'adminata-autocomplete': AutocompleteController,
    'adminata-batch': BatchController,
    'adminata-collection': CollectionController,
    'adminata-confirm-exit': ConfirmExitController,
    'adminata-dismiss': DismissController,
    'adminata-dropdown': DropdownController,
    'adminata-edit': EditController,
    'adminata-filter': FilterController,
    'adminata-filter-list': FilterListController,
    'adminata-layout': LayoutController,
    'adminata-menu': MenuController,
    'adminata-modal': ModalController,
    'adminata-modal-trigger': ModalTriggerController,
    'adminata-per-page': PerPageController,
    'adminata-question': QuestionController,
    'adminata-readmore': ReadmoreController,
    'adminata-reveal': RevealController,
    'adminata-revision': RevisionController,
    'adminata-row-link': RowLinkController,
    'adminata-sticky': StickyController,
    'adminata-theme': ThemeController,
};

/**
 * @param {import('@hotwired/stimulus').Application} application
 */
export function register(application) {
    for (const [identifier, controller] of Object.entries(controllers)) {
        application.register(identifier, controller);
    }
}
