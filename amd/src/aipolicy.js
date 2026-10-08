// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Makes sure the user has accepted the site's AI usage policy before any AI request is sent.
 *
 * Every AI feature in Dari runs through Moodle's own AI subsystem, which requires each user to
 * accept the site's AI usage policy once. Core's placements (TinyMCE, course assistance) show the
 * same policy; this shows it for Ask Dari, banners and card images, using core's own wording
 * and core's own web services, so accepting it in one place counts everywhere.
 *
 * @module     format_dari/aipolicy
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import {getString} from 'core/str';
import Notification from 'core/notification';

/** @type {Promise<Boolean>|null} A dialogue already open, so two clicks do not stack two. */
let pending = null;

/**
 * Resolve true once the policy is accepted, showing core's policy dialogue if it has not been.
 *
 * @returns {Promise<Boolean>} True when accepted, false when the user declined or closed it.
 */
export const ensureAccepted = async() => {
    const userid = M.cfg.userId;
    // Loaded on demand: core_ai/policy only exists on Moodle 4.5 and later. A static import would
    // stop every module that uses this one from loading on Moodle 4.4, where Dari runs without AI.
    let Policy;
    try {
        const module = await import('core_ai/policy');
        // Moodle's AMD build returns a default export as the module itself.
        Policy = module && module.default ? module.default : module;
    } catch (error) {
        // Moodle 4.4: there is no AI usage policy to accept.
        return true;
    }
    try {
        if (await Policy.getPolicyStatus(userid)) {
            return true;
        }
    } catch (error) {
        // Fall through to the dialogue: if the status cannot be read, asking is the safe default.
    }

    if (pending) {
        return pending;
    }

    pending = (async() => {
        const modal = await ModalSaveCancel.create({
            title: getString('aiusagepolicy', 'core_ai'),
            body: getString('userpolicy', 'core_ai'),
            buttons: {
                save: getString('acceptai', 'core_ai'),
                cancel: getString('declineaipolicy', 'core_ai'),
            },
            large: true,
            removeOnClose: true,
        });
        modal.getRoot().addClass('dari-ai-policy-modal');

        return new Promise((resolve) => {
            let accepted = false;
            modal.getRoot().on(ModalEvents.save, (e) => {
                e.preventDefault();
                Policy.acceptPolicy().then(() => {
                    accepted = true;
                    modal.destroy();
                    resolve(true);
                    return true;
                }).catch((error) => {
                    Notification.exception(error);
                    modal.destroy();
                    resolve(false);
                });
            });
            modal.getRoot().on(ModalEvents.hidden, () => {
                if (!accepted) {
                    resolve(false);
                }
            });
            modal.show();
        });
    })();

    try {
        return await pending;
    } finally {
        pending = null;
    }
};

