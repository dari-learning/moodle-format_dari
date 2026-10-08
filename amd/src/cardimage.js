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
 * Card images, edit mode only (2.5.0).
 *
 * Every card's image area carries four tools for a teacher: upload a picture, generate one with AI,
 * choose the card's colour, and remove the picture. Section cards render the tools in their
 * template. Activity cards are not drawn while editing -- the section page shows core's editor
 * instead -- so this module adds a compact image row to each of core's activity rows, from data
 * the page carries in #dari-cardimage-data, and re-adds it whenever core re-renders a row.
 *
 * One click handler, delegated from the document, serves both.
 *
 * @module     format_dari/cardimage
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getString, getStrings} from 'core/str';
import Notification from 'core/notification';
import Modal from 'core/modal';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Pending from 'core/pending';
import {ensureAccepted as ensureAiPolicy} from 'format_dari/aipolicy';

/** @var {Number} Longest edge an upload is scaled down to before it is sent. */
const MAX_EDGE = 1600;

/** @var {Number} Largest file the browser will even try to read, in bytes. */
const MAX_PICK_BYTES = 20 * 1024 * 1024;

/** @var {Number} Milliseconds between status polls while an image is generated. */
const POLL_EVERY = 3000;

/** @var {Number} Polls before giving up: 120 x 3s is six minutes, as the banner allows. */
const POLL_LIMIT = 120;

/** @var {Number} Longest prompt, matching generate_card_image::PROMPT_MAX. */
const PROMPT_MAX = 400;

/** @var {String[]} The colour swatches offered, chosen to hold white text at 4.5:1 or better. */
const SWATCHES = [
    '#1d4ed8', '#0369a1', '#0f766e', '#15803d', '#4d7c0f', '#a16207',
    '#c2410c', '#b91c1c', '#be185d', '#9333ea', '#6d28d9', '#334155',
];

const SELECTORS = {
    MEDIA: '.drf-media',
    TOOL: '.drf-tools__btn',
    DATA: '#dari-cardimage-data',
    CMITEM: '[data-for="cmitem"][data-id]',
    ALLBUTTON: '[data-action="dari-cardimage-all"]',
};

/** @var {Object} Page configuration read from #dari-cardimage-data. */
let config = {courseid: 0, cost: 0, style: '', cms: {}};

/** @var {Object} Strings, loaded once. */
let str = {};

/** @var {Boolean} Guards against a second init() on the same page. */
let initialised = false;

/** @var {Object} Running polls, keyed "type:id", so one card is never polled twice. */
const polls = {};

const STRING_KEYS = [
    'cardimage_upload', 'cardimage_ai', 'cardimage_colour', 'cardimage_remove',
    'cardimage_uploadfor', 'cardimage_aifor', 'cardimage_colourfor', 'cardimage_removefor',
    'cardimage_uploading', 'cardimage_saved', 'cardimage_removed', 'cardimage_uploaderror',
    'cardimage_toolarge', 'cardimage_title', 'cardimage_titlefor', 'cardimage_promptlabel',
    'cardimage_promptph', 'cardimage_prompthint', 'cardimage_style', 'cardimage_cost',
    'cardimage_generate', 'cardimage_generating', 'cardimage_generated', 'cardimage_failed',
    'cardimage_colour_desc', 'cardimage_colour_default', 'cardimage_colour_auto', 'cardimage_colour_custom',
    'cardimage_colour_saved', 'cardimage_all_title', 'cardimage_all_scope',
    'cardimage_all_scope_all', 'cardimage_all_scope_sections', 'cardimage_all_scope_activities',
    'cardimage_all_onlymissing', 'cardimage_all_counting', 'cardimage_all_none',
    'cardimage_all_desc', 'cardimage_all_capped', 'cardimage_all_queued', 'cardimage_menu', 'ai_notready_title',
    'cardimage_promptused', 'cardimage_promptused_ai', 'cardimage_promptused_template',
    'cardimage_dialogtitle', 'save', 'cancel',
];

/**
 * Load every string this module uses in one request.
 *
 * @returns {Promise<void>}
 */
const loadStrings = async() => {
    const requests = STRING_KEYS.map((key) => ({
        key,
        component: (key === 'save' || key === 'cancel') ? 'core' : 'format_dari',
    }));
    const values = await getStrings(requests);
    STRING_KEYS.forEach((key, i) => {
        str[key] = values[i];
    });
};

/**
 * Replace {$a} in a loaded string.
 *
 * Strings with a parameter are loaded once with a placeholder rather than fetched per card.
 *
 * @param {String} key String key.
 * @param {String|Number} a The value.
 * @returns {String}
 */
const fill = (key, a) => String(str[key] || '').replace('{$a}', String(a));

/**
 * Call one of this plugin's web services.
 *
 * @param {String} name Function name without the format_dari_ prefix.
 * @param {Object} args Arguments.
 * @returns {Promise<Object>}
 */
const call = (name, args) => Ajax.call([{methodname: 'format_dari_' + name, args}])[0];

/**
 * Explain why AI images cannot be generated yet (no AI image service set up, or AI switched off).
 *
 * @param {String} reason HTML from the server, already escaped, possibly with a settings link.
 */
const showAiHint = (reason) => {
    Notification.alert(str.ai_notready_title || '', reason || str.ai_notready_title || '');
};

/**
 * The target a media element stands for.
 *
 * @param {HTMLElement} media A .drf-media element.
 * @returns {{type: String, id: Number, name: String}}
 */
const targetOf = (media) => ({
    type: media.dataset.cardtype,
    id: parseInt(media.dataset.cardid, 10),
    name: media.dataset.cardname || '',
});

/**
 * Every media element on the page for one target: a section card and its fragment re-render, or
 * an activity row, are all kept in step.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 * @returns {HTMLElement[]}
 */
const mediasFor = (type, id) => [...document.querySelectorAll(
    `${SELECTORS.MEDIA}[data-cardtype="${type}"][data-cardid="${id}"]`
)];

/**
 * The element that carries a card's colour: the card itself where there is one.
 *
 * @param {HTMLElement} media A .drf-media element.
 * @returns {HTMLElement}
 */
const colourHost = (media) => media.closest('.drf-card, .drf-cmrow') || media;

/**
 * The element whose tools belong to a media element: an activity row keeps its tools beside the
 * thumbnail rather than on it.
 *
 * @param {HTMLElement} media A .drf-media element.
 * @returns {HTMLElement}
 */
const toolScope = (media) => media.closest('.drf-cmrow') || media;

/**
 * Show a new image, or none, on every media element for a target.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 * @param {String} url Image URL, or '' for the placeholder.
 * @param {String} source card, banner or ''.
 */
const applyImage = (type, id, url, source) => {
    mediasFor(type, id).forEach((media) => {
        let img = media.querySelector('.drf-media__img');
        if (url) {
            if (!img) {
                img = document.createElement('img');
                img.className = 'drf-media__img';
                img.alt = '';
                img.decoding = 'async';
                media.querySelector('.drf-media__panel').after(img);
            }
            img.src = url;
        } else if (img) {
            img.remove();
        }
        media.classList.toggle('drf-media--image', Boolean(url));
        // The card switches between the image layout and the colour-panel layout.
        const card = media.closest('.drf-card');
        if (card) {
            card.classList.toggle('drf-card--noimage', !url);
        }
        media.dataset.imagesource = source || '';
        const removeTool = toolScope(media).querySelector('[data-action="remove"]');
        if (removeTool) {
            removeTool.hidden = source !== 'card';
        }
    });
    if (type === 'cm' && config.cms[id]) {
        config.cms[id].url = url;
        config.cms[id].source = source;
    }
};

/**
 * Whether text on this colour must be dark: the same WCAG luminance test as cardimage::is_light().
 *
 * @param {String} colour #rrggbb or ''.
 * @returns {Boolean}
 */
const isLight = (colour) => {
    const m = /^#([0-9a-f]{6})$/i.exec(colour || '');
    if (!m) {
        return false;
    }
    const lum = [[0, 0.2126], [2, 0.7152], [4, 0.0722]].reduce((sum, [at, weight]) => {
        const c = parseInt(m[1].substr(at, 2), 16) / 255;
        return sum + weight * (c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4));
    }, 0);
    return lum > 0.18;
};

/**
 * Paint a colour, or the course accent, on every card for a target.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 * @param {String} colour #rrggbb, or '' for the course accent.
 */
const applyColour = (type, id, colour) => {
    mediasFor(type, id).forEach((media) => {
        media.dataset.colour = colour || '';
        media.classList.toggle('drf-media--light', isLight(colour));
        const host = colourHost(media);
        if (colour) {
            host.style.setProperty('--drf-card-colour', colour);
        } else {
            host.style.removeProperty('--drf-card-colour');
        }
    });
    if (type === 'cm' && config.cms[id]) {
        config.cms[id].colour = colour;
    }
};

/**
 * Cover a target's media with a progress message, or take it away.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 * @param {String|null} message Text to show, or null to clear.
 */
const setBusy = (type, id, message) => {
    mediasFor(type, id).forEach((media) => {
        let busy = media.querySelector('.drf-media__busy');
        if (!busy) {
            busy = document.createElement('div');
            busy.className = 'drf-media__busy';
            busy.innerHTML = '<span class="drf-media__spinner" aria-hidden="true"></span>'
                + '<span class="drf-media__busytext"></span>';
            media.appendChild(busy);
        }
        busy.hidden = message === null;
        busy.querySelector('.drf-media__busytext').textContent = message || '';
        toolScope(media).querySelectorAll(SELECTORS.TOOL).forEach((tool) => {
            tool.disabled = message !== null;
        });
    });
};

/**
 * Say something to screen readers and sighted users alike.
 *
 * @param {String} message Plain text.
 * @param {String} type success, error, info.
 */
const tell = (message, type = 'success') => {
    const div = document.createElement('div');
    div.textContent = message;
    Notification.addNotification({message: div.innerHTML, type});
};

/**
 * Read a picked file and scale it down to at most MAX_EDGE on its longest side.
 *
 * Done in the browser so a 12 MB phone photo travels as a few hundred kilobytes. The server still
 * validates what arrives; this is a courtesy, not a control.
 *
 * @param {File} file The picked file.
 * @returns {Promise<String>} Base64 JPEG in 64-character lines, without a data: prefix.
 */
const prepareUpload = async(file) => {
    const url = URL.createObjectURL(file);
    try {
        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.onload = () => resolve(el);
            el.onerror = reject;
            el.src = url;
        });
        const scale = Math.min(1, MAX_EDGE / Math.max(img.naturalWidth, img.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
        const ctx = canvas.getContext('2d');
        // JPEG has no transparency; a transparent PNG would otherwise turn black.
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        // Moodle's PARAM_BASE64 is the PEM layout: 64-character lines joined by newlines.
        return canvas.toDataURL('image/jpeg', 0.86).split(',')[1].match(/.{1,64}/g).join('\n');
    } finally {
        URL.revokeObjectURL(url);
    }
};

/**
 * Let the teacher pick a file and store it as the card's image.
 *
 * @param {HTMLElement} media The card's media element.
 */
const upload = (media) => {
    const target = targetOf(media);
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/gif,image/webp';
    input.style.display = 'none';
    input.addEventListener('change', async() => {
        const file = input.files && input.files[0];
        input.remove();
        if (!file) {
            return;
        }
        if (file.size > MAX_PICK_BYTES) {
            tell(str.cardimage_toolarge, 'error');
            return;
        }
        const pending = new Pending('format_dari/cardimage:upload');
        setBusy(target.type, target.id, str.cardimage_uploading);
        try {
            const imagedata = await prepareUpload(file);
            const result = await call('upload_card_image', {
                courseid: config.courseid,
                targettype: target.type,
                targetid: target.id,
                imagedata,
            });
            stopPoll(target.type, target.id);
            applyImage(target.type, target.id, result.imageurl, 'card');
            tell(str.cardimage_saved);
        } catch (error) {
            tell((error && error.message) || str.cardimage_uploaderror, 'error');
        } finally {
            setBusy(target.type, target.id, null);
            pending.resolve();
        }
    });
    document.body.appendChild(input);
    input.click();
};

/**
 * Stop polling a target.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 */
const stopPoll = (type, id) => {
    const key = type + ':' + id;
    if (polls[key]) {
        window.clearTimeout(polls[key]);
        delete polls[key];
    }
};

/**
 * Poll a queued generation until it finishes, then show the result.
 *
 * @param {String} type section or cm.
 * @param {Number} id Target id.
 * @param {String} name The card's name, for the announcement.
 */
const poll = (type, id, name) => {
    const key = type + ':' + id;
    stopPoll(type, id);
    setBusy(type, id, str.cardimage_generating);
    let attempts = 0;
    const tick = async() => {
        attempts++;
        let status;
        try {
            status = await call('get_card_image_status', {courseid: config.courseid, targettype: type, targetid: id});
        } catch (error) {
            status = {status: 'failed', message: (error && error.message) || ''};
        }
        if (!polls[key]) {
            // Stopped while the request was in flight: an upload replaced the image.
            return;
        }
        if (status.status === 'done' && status.imageurl) {
            delete polls[key];
            setBusy(type, id, null);
            applyImage(type, id, status.imageurl, 'card');
            tell(fill('cardimage_generated', name));
            return;
        }
        if (status.status === 'failed' || status.status === 'idle' || attempts >= POLL_LIMIT) {
            delete polls[key];
            setBusy(type, id, null);
            tell(fill('cardimage_failed', status.message || ''), 'error');
            return;
        }
        polls[key] = window.setTimeout(tick, POLL_EVERY);
    };
    polls[key] = window.setTimeout(tick, POLL_EVERY);
};

/**
 * Build a DOM element from a tag, attributes and children.
 *
 * Text is always set as text, never as HTML, so a section name can never become markup.
 *
 * @param {String} tag Tag name.
 * @param {Object} attrs Attributes; 'text' sets textContent.
 * @param {Array} children Child nodes.
 * @returns {HTMLElement}
 */
const el = (tag, attrs = {}, children = []) => {
    const node = document.createElement(tag);
    Object.entries(attrs).forEach(([name, value]) => {
        if (name === 'text') {
            node.textContent = value;
        } else if (value !== false && value !== null && value !== undefined) {
            node.setAttribute(name, value === true ? '' : value);
        }
    });
    children.forEach((child) => node.appendChild(child));
    return node;
};

/**
 * Ask the AI for a card image, with the teacher's own description.
 *
 * @param {HTMLElement} media The card's media element.
 */
const generate = async(media) => {
    const target = targetOf(media);
    const promptid = 'dari-cardimage-prompt-' + target.type + target.id;
    const hintid = promptid + '-hint';
    const textarea = el('textarea', {
        'id': promptid,
        'class': 'form-control',
        'rows': '3',
        'maxlength': String(PROMPT_MAX),
        'placeholder': str.cardimage_promptph,
        'aria-describedby': hintid,
    });
    const body = el('div', {'class': 'dari-cardimage-dialog'}, [
        el('p', {'class': 'dari-cardimage-for', 'text': fill('cardimage_titlefor', target.name)}),
        el('label', {'for': promptid, 'class': 'form-label fw-semibold', 'text': str.cardimage_promptlabel}),
        textarea,
        el('p', {'id': hintid, 'class': 'form-text text-muted small mt-1', 'text': str.cardimage_prompthint}),
        el('div', {'class': 'dari-cardimage-meta'}, [
            el('span', {'class': 'dari-cardimage-style', 'text': fill('cardimage_style', config.style)}),
            el('span', {'class': 'dari-cardimage-cost', 'text': fill('cardimage_cost', config.cost)}),
        ]),
    ]);
    // The prompt Dari sent last time for this card, so the teacher can see exactly what the image
    // model was asked for, and refine it with their own words above.
    const used = el('details', {'class': 'dari-cardimage-promptused', 'hidden': 'hidden'}, [
        el('summary', {'text': str.cardimage_promptused}),
        el('p', {'class': 'dari-cardimage-promptused-source small text-muted'}),
        el('pre', {'class': 'dari-cardimage-promptused-text'}),
    ]);
    body.appendChild(used);
    call('get_image_prompt', {courseid: config.courseid, targettype: target.type, targetid: target.id}).then((result) => {
        if (result && result.prompt) {
            used.querySelector('.dari-cardimage-promptused-text').textContent = result.prompt;
            used.querySelector('.dari-cardimage-promptused-source').textContent =
                result.source === 'artdirector' ? str.cardimage_promptused_ai : str.cardimage_promptused_template;
            used.hidden = false;
        }
        return result;
    }).catch(() => null);

    const modal = await ModalSaveCancel.create({
        title: str.cardimage_title,
        body,
        buttons: {save: str.cardimage_generate},
        removeOnClose: true,
        show: true,
    });
    modal.getRoot().on(ModalEvents.shown, () => textarea.focus());
    modal.getRoot().on(ModalEvents.save, async() => {
        const pending = new Pending('format_dari/cardimage:generate');
        try {
            await call('generate_card_image', {
                courseid: config.courseid,
                targettype: target.type,
                targetid: target.id,
                prompt: textarea.value.slice(0, PROMPT_MAX),
            });
            poll(target.type, target.id, target.name);
        } catch (error) {
            tell(fill('cardimage_failed', (error && error.message) || ''), 'error');
        } finally {
            pending.resolve();
        }
    });
};

/**
 * Choose the card's colour, previewed live on the card, saved on Save and undone on Cancel.
 *
 * @param {HTMLElement} media The card's media element.
 */
const chooseColour = async(media) => {
    const target = targetOf(media);
    const original = media.dataset.colour || '';
    let chosen = original;
    const groupname = 'dari-cardcolour-' + target.type + target.id;

    const option = (value, label, swatch) => {
        const input = el('input', {
            'type': 'radio',
            'name': groupname,
            'value': value,
            'class': 'dari-cardcolour-input',
            'aria-label': label,
            'checked': value === original,
        });
        const face = el('span', {'class': 'dari-cardcolour-swatch' + (value ? '' : ' dari-cardcolour-default'),
            'aria-hidden': 'true', 'text': value ? '' : str.cardimage_colour_auto});
        if (swatch) {
            face.style.background = swatch;
        }
        return el('label', {'class': 'dari-cardcolour-option', 'title': label}, [input, face]);
    };

    const custom = el('input', {
        'type': 'color',
        'class': 'dari-cardcolour-custom',
        'value': original || '#1d4ed8',
        'aria-label': str.cardimage_colour_custom,
    });
    const grid = el('div', {'class': 'dari-cardcolour-grid', 'role': 'radiogroup',
        'aria-label': str.cardimage_colour}, [
        option('', str.cardimage_colour_default, ''),
        ...SWATCHES.map((hex) => option(hex, hex, hex)),
    ]);
    const body = el('div', {'class': 'dari-cardimage-dialog'}, [
        el('p', {'class': 'dari-cardimage-for', 'text': fill('cardimage_titlefor', target.name)}),
        el('p', {'class': 'text-muted small', 'text': str.cardimage_colour_desc}),
        grid,
        el('label', {'class': 'dari-cardcolour-customrow'}, [
            custom,
            el('span', {'text': str.cardimage_colour_custom}),
        ]),
    ]);

    grid.addEventListener('change', (e) => {
        chosen = e.target.value;
        applyColour(target.type, target.id, chosen);
    });
    custom.addEventListener('input', () => {
        chosen = custom.value;
        grid.querySelectorAll('input').forEach((input) => {
            input.checked = false;
        });
        applyColour(target.type, target.id, chosen);
    });

    const modal = await ModalSaveCancel.create({
        title: str.cardimage_colour,
        body,
        buttons: {save: str.save},
        removeOnClose: true,
        show: true,
    });
    let saved = false;
    modal.getRoot().on(ModalEvents.save, async() => {
        saved = true;
        const pending = new Pending('format_dari/cardimage:colour');
        try {
            const result = await call('set_card_colour', {
                courseid: config.courseid,
                targettype: target.type,
                targetid: target.id,
                colour: chosen,
            });
            applyColour(target.type, target.id, result.colour);
            tell(str.cardimage_colour_saved);
        } catch (error) {
            applyColour(target.type, target.id, original);
            tell((error && error.message) || '', 'error');
        } finally {
            pending.resolve();
        }
    });
    modal.getRoot().on(ModalEvents.hidden, () => {
        if (!saved) {
            applyColour(target.type, target.id, original);
        }
    });
};

/**
 * Remove the card's own image, after confirming.
 *
 * @param {HTMLElement} media The card's media element.
 */
const remove = async(media) => {
    const target = targetOf(media);
    try {
        await Notification.deleteCancelPromise(
            str.cardimage_remove,
            fill('cardimage_removefor', target.name) + '?',
            str.cardimage_remove
        );
    } catch (e) {
        return;
    }
    const pending = new Pending('format_dari/cardimage:remove');
    try {
        const result = await call('delete_card_image', {
            courseid: config.courseid,
            targettype: target.type,
            targetid: target.id,
        });
        applyImage(target.type, target.id, result.imageurl, result.source);
        tell(str.cardimage_removed);
    } catch (error) {
        tell((error && error.message) || '', 'error');
    } finally {
        pending.resolve();
    }
};

/**
 * Generate images for many cards: count first, show the credit total, then queue.
 */
const generateAll = async() => {
    let scope = 'all';
    let onlymissing = true;
    const summary = el('p', {'class': 'dari-cardimage-summary', 'aria-live': 'polite', 'text': str.cardimage_all_counting});
    const radios = ['all', 'sections', 'activities'].map((value) => el('label', {'class': 'dari-cardimage-radio'}, [
        el('input', {'type': 'radio', 'name': 'dari-cardimage-scope', 'value': value, 'checked': value === scope}),
        el('span', {'text': str['cardimage_all_scope_' + value]}),
    ]));
    const missing = el('input', {'type': 'checkbox', 'checked': true});
    const body = el('div', {'class': 'dari-cardimage-dialog'}, [
        el('p', {'text': fill('cardimage_all_desc', config.style)}),
        el('fieldset', {'class': 'dari-cardimage-fieldset'}, [
            el('legend', {'class': 'form-label fw-semibold', 'text': str.cardimage_all_scope}),
            ...radios,
        ]),
        el('label', {'class': 'dari-cardimage-radio'}, [missing, el('span', {'text': str.cardimage_all_onlymissing})]),
        summary,
    ]);

    const modal = await ModalSaveCancel.create({
        title: str.cardimage_all_title,
        body,
        buttons: {save: str.cardimage_generate},
        removeOnClose: true,
        show: true,
    });
    const saveButton = () => modal.getFooter()[0].querySelector('[data-action="save"]');
    let latest = null;

    const count = async() => {
        summary.textContent = str.cardimage_all_counting;
        saveButton().disabled = true;
        const request = {courseid: config.courseid, scope, onlymissing, dryrun: true};
        latest = request;
        try {
            const result = await call('generate_all_card_images', request);
            if (latest !== request) {
                return;
            }
            if (!result.count) {
                summary.textContent = str.cardimage_all_none;
                return;
            }
            const [line, button] = await Promise.all([
                getString('cardimage_all_summary', 'format_dari', {count: result.count, credits: result.credits}),
                getString('cardimage_all_confirm', 'format_dari', result.count),
            ]);
            summary.textContent = line + (result.skipped ? ' ' + fill('cardimage_all_capped', result.count) : '');
            saveButton().textContent = button;
            saveButton().disabled = false;
        } catch (error) {
            summary.textContent = (error && error.message) || '';
        }
    };

    body.addEventListener('change', (e) => {
        if (e.target.name === 'dari-cardimage-scope') {
            scope = e.target.value;
        } else if (e.target === missing) {
            onlymissing = missing.checked;
        }
        count();
    });
    modal.getRoot().on(ModalEvents.shown, count);
    modal.getRoot().on(ModalEvents.save, async() => {
        const pending = new Pending('format_dari/cardimage:all');
        try {
            const result = await call('generate_all_card_images', {courseid: config.courseid, scope, onlymissing, dryrun: false});
            if (result.queued) {
                result.targets.forEach((t) => {
                    const media = mediasFor(t.type, t.id)[0];
                    if (media) {
                        poll(t.type, t.id, targetOf(media).name);
                    }
                });
                tell(fill('cardimage_all_queued', result.count), 'info');
            }
        } catch (error) {
            tell((error && error.message) || '', 'error');
        } finally {
            pending.resolve();
        }
    });
};

/**
 * Build the body of the card image dialog for one activity.
 *
 * While editing, Moodle's own activity list is left exactly as Moodle draws it, so moving
 * and reordering stay as quick as on any other course. The image tools live in a dialog opened
 * from the activity's own menu instead of in a row under every activity.
 *
 * The dialog carries a preview drawn like the real card, and the same four tools the section
 * cards have, each with its name written out.
 *
 * @param {Number} cmid Course module id.
 * @param {Object} data Its entry in the page data.
 * @returns {HTMLElement}
 */
const buildCmDialog = (cmid, data) => {
    const svg = (paths) => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="18" '
        + 'height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
        + 'stroke-linecap="round" stroke-linejoin="round">' + paths + '</svg>';
    const tool = (action, label, text, hint, icon, extra = '') => {
        const button = el('button', {
            'type': 'button',
            'class': 'drf-tools__btn drf-cmdialog__tool' + extra,
            'data-action': action,
            'aria-label': label,
        });
        button.innerHTML = icon;
        const words = el('span', {'class': 'drf-cmdialog__words'}, [el('span', {'class': 'drf-cmdialog__label', 'text': text})]);
        if (hint) {
            words.appendChild(el('span', {'class': 'drf-cmdialog__hint', 'text': hint}));
        }
        button.appendChild(words);
        return button;
    };

    const top = el('div', {'class': 'drf-media__top'});
    if (data.iconurl) {
        top.appendChild(el('span', {'class': 'drf-media__icon'}, [el('img', {'src': data.iconurl, 'alt': ''})]));
    }
    const panel = el('div', {'class': 'drf-media__panel', 'aria-hidden': 'true'}, [
        top,
        el('span', {'class': 'drf-media__title', 'text': data.name}),
    ]);
    const thumb = el('div', {
        'class': 'drf-media' + (data.url ? ' drf-media--image' : '') + (isLight(data.colour) ? ' drf-media--light' : ''),
        'data-cardtype': 'cm',
        'data-cardid': String(cmid),
        'data-courseid': String(config.courseid),
        'data-cardname': data.name,
        'data-imagesource': data.source || '',
        'data-colour': data.colour || '',
    }, [panel]);
    if (data.url) {
        thumb.appendChild(el('img', {'class': 'drf-media__img', 'src': data.url, 'alt': ''}));
    }

    const tools = el('div', {'class': 'drf-cmdialog__tools'}, [
        tool('upload', fill('cardimage_uploadfor', data.name), str.cardimage_upload, '',
            svg('<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/>'
                + '<path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>')),
        tool('ai', fill('cardimage_aifor', data.name), str.cardimage_ai, fill('cardimage_cost', config.cost),
            svg('<path d="M9.94 14.06 8 20l-1.94-5.94L0 12l6.06-1.94L8 4l1.94 6.06L16 12z" '
                + 'transform="translate(2 0) scale(.9)"/><path d="M20 3v4"/><path d="M22 5h-4"/>'),
            ' drf-tools__btn--ai'),
        tool('colour', fill('cardimage_colourfor', data.name), str.cardimage_colour, str.cardimage_colour_desc,
            svg('<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" '
                + 'fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" '
                + 'cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 '
                + '1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.13a1.64 1.64 0 0 1 '
                + '1.67-1.67h2c3.05 0 5.55-2.5 5.55-5.55C21.97 6.01 17.46 2 12 2z"/>')),
        tool('remove', fill('cardimage_removefor', data.name), str.cardimage_remove, '',
            svg('<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>'
                + '<path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>'),
            ' drf-tools__btn--remove'),
    ]);

    // .drf-cmrow is what colourHost(), toolScope() and mediaForTool() look for, so every tool
    // works on this preview exactly as it does on a card.
    if (config.aiimages === false) {
        // No AI image provider is enabled on this site, or AI is off for this course.
        tools.querySelector('[data-action="ai"]').remove();
    }
    const row = el('div', {'class': 'drf-cmrow drf-cmdialog', 'data-cmid': String(cmid)}, [thumb, tools]);
    tools.querySelector('[data-action="remove"]').hidden = data.source !== 'card';
    if (data.colour) {
        row.style.setProperty('--drf-card-colour', data.colour);
    }
    return row;
};

/**
 * Open the card image dialog for an activity.
 *
 * @param {Number} cmid Course module id.
 */
const openCmDialog = async(cmid) => {
    const data = config.cms[cmid];
    if (!data) {
        return;
    }
    const modal = await Modal.create({
        title: fill('cardimage_dialogtitle', data.name),
        body: buildCmDialog(cmid, data),
        removeOnClose: true,
        show: true,
    });
    modal.getRoot()[0].classList.add('dari-cardimage-dialog');
};

/**
 * Add "Card image" to the menu of every activity in Moodle's editor that does not have it yet.
 *
 * Placed straight after "Edit settings", where a teacher looks for what they can change about an
 * activity. The item has no data-action, so core's editor leaves its clicks alone.
 */
/**
 * Put a visible "Card image" button on a core activity row in edit mode.
 *
 * It shows the activity card's current image (or an image icon) and opens the same image dialog
 * as the menu item: upload, generate with AI, colour and remove. Activity cards are not drawn
 * while editing, so without this the tools were only reachable from the activity's menu.
 *
 * @param {HTMLElement} item The core activity row.
 * @param {HTMLElement} menu The row's edit menu.
 * @param {Number} cmid Course module id.
 */
const addCmImageButton = (item, menu, cmid) => {
    if (item.querySelector('.drf-cmimage-btn')) {
        return;
    }
    const data = config.cms[cmid] || {};
    const button = el('button', {
        'type': 'button',
        'class': 'drf-cmimage-btn' + (data.url ? ' has-image' : ''),
        'data-drf-action': 'cardimage',
        'data-id': String(cmid),
        'title': str.cardimage_menu,
        'aria-label': str.cardimage_menu + (data.name ? ': ' + data.name : ''),
    });
    if (data.url) {
        button.appendChild(el('img', {'src': data.url, 'alt': ''}));
    } else {
        button.innerHTML = '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            + 'stroke-width="1.6" stroke-linecap="butt" stroke-linejoin="round"><rect x="3.75" y="4.75" width="16.5" '
            + 'height="14.5" rx="3" fill="currentColor" fill-opacity=".14"/><circle cx="9" cy="10" r="1.6"/>'
            + '<path d="m20 16-4.2-4.2a1.5 1.5 0 0 0-2.1 0L6.5 19"/></svg>';
    }
    if (data.colour) {
        button.style.setProperty('--drf-card-colour', data.colour);
    }
    const toggle = menu.parentElement && menu.parentElement.querySelector('[data-bs-toggle="dropdown"], [data-toggle="dropdown"]');
    const host = (toggle && toggle.parentElement) || menu.parentElement;
    if (host && host.parentElement) {
        host.parentElement.insertBefore(button, host);
    }
};

const decorateCmMenus = () => {
    document.querySelectorAll(SELECTORS.CMITEM).forEach((item) => {
        // Only core's rows: the plugin's own activity cards carry the same attributes.
        if (item.closest('.drf-grid')) {
            return;
        }
        const cmid = parseInt(item.dataset.id, 10);
        // The activity's own edit menu, not the completion dropdown that some rows also carry.
        const menu = [...item.querySelectorAll('.dropdown-menu')].find((m) => m.querySelector('.cm-edit-action'));
        if (!config.cms[cmid] || !menu) {
            return;
        }
        addCmImageButton(item, menu, cmid);
        if (menu.querySelector('[data-drf-action="cardimage"]')) {
            return;
        }
        const link = el('a', {
            'href': '#',
            'class': 'dropdown-item menu-action',
            'role': 'menuitem',
            'tabindex': '-1',
            'data-drf-action': 'cardimage',
            'data-id': String(cmid),
        });
        // The trailing space matches the whitespace core's own menu items carry between icon and text.
        link.innerHTML = '<i class="icon fa fa-image fa-fw" aria-hidden="true"></i> ';
        link.appendChild(el('span', {'class': 'menu-action-text', 'text': str.cardimage_menu}));
        const first = menu.querySelector('[data-action="update"]') || menu.querySelector('.dropdown-item');
        if (first) {
            first.after(link);
        } else {
            menu.prepend(link);
        }
    });
};

/**
 * Find the media element a tool acts on.
 *
 * @param {HTMLElement} tool The clicked tool.
 * @returns {HTMLElement|null}
 */
const mediaForTool = (tool) => {
    const row = tool.closest('.drf-cmrow');
    if (row) {
        return row.querySelector(SELECTORS.MEDIA);
    }
    return tool.closest(SELECTORS.MEDIA);
};

/**
 * Wire the page up.
 *
 * @returns {Promise<void>}
 */
export const init = async() => {
    if (initialised) {
        return;
    }
    initialised = true;

    const holder = document.querySelector(SELECTORS.DATA);
    if (holder) {
        try {
            config = Object.assign(config, JSON.parse(holder.dataset.json || '{}'));
        } catch (e) {
            // Malformed data only loses the activity rows; section cards still work.
        }
    }
    if (!config.courseid) {
        const media = document.querySelector(SELECTORS.MEDIA);
        config.courseid = media ? parseInt(media.dataset.courseid, 10) : 0;
    }

    await loadStrings();

    document.addEventListener('click', (e) => {
        const all = e.target.closest(SELECTORS.ALLBUTTON);
        if (all) {
            e.preventDefault();
            if (all.dataset.aiready === '0' || config.aiready === false) {
                showAiHint(all.dataset.aireason || config.aireason);
                return;
            }
            ensureAiPolicy().then((accepted) => {
                if (accepted) {
                    generateAll();
                }
                return accepted;
            }).catch(Notification.exception);
            return;
        }
        const menuitem = e.target.closest('[data-drf-action="cardimage"]');
        if (menuitem) {
            e.preventDefault();
            openCmDialog(parseInt(menuitem.dataset.id, 10));
            return;
        }
        const tool = e.target.closest(SELECTORS.TOOL);
        if (!tool || tool.disabled) {
            return;
        }
        const media = mediaForTool(tool);
        if (!media) {
            return;
        }
        // The tools sit inside a card whose title link covers it; never let the click reach it.
        e.preventDefault();
        e.stopPropagation();
        switch (tool.dataset.action) {
            case 'upload':
                upload(media);
                break;
            case 'ai':
                if (config.aiready === false) {
                    showAiHint(config.aireason);
                    break;
                }
                // Moodle's AI usage policy (core_ai) first, then the generation dialogue.
                ensureAiPolicy().then((accepted) => {
                    if (accepted) {
                        generate(media);
                    }
                    return accepted;
                }).catch(Notification.exception);
                break;
            case 'colour':
                chooseColour(media);
                break;
            case 'remove':
                remove(media);
                break;
        }
    }, true);

    decorateCmMenus();
    // Core re-renders an activity row after most edits, replacing the element. Watch for that and
    // put the menu item back. Cheap: it only looks at menus without one.
    const region = document.querySelector('#region-main') || document.body;
    let queued = false;
    new MutationObserver(() => {
        if (queued) {
            return;
        }
        queued = true;
        window.requestAnimationFrame(() => {
            queued = false;
            decorateCmMenus();
        });
    }).observe(region, {childList: true, subtree: true});

    // Generations still running from before a reload carry on showing their progress.
    (config.running || []).forEach((t) => {
        const media = mediasFor(t.type, t.id)[0];
        if (media) {
            poll(t.type, t.id, targetOf(media).name);
        }
    });
};
