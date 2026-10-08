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
 * Records browser errors on Dari course pages in Dari's diagnostics log.
 *
 * Loaded only for people who can edit the course. Captures uncaught errors, unhandled promise
 * rejections, console.error calls and failed Dari web-service calls (reported by other Dari
 * modules through report()). At most 20 events are sent per page, each distinct message once.
 * The log is on the course's Image plan preview page.
 *
 * @module     format_dari/diagnostics
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/** @var {Number} Most events sent from one page. */
const MAX_EVENTS = 20;

let courseid = 0;
let sent = 0;
const seen = new Set();

/**
 * Send one event to the server. Never throws and never reports its own failure.
 *
 * @param {String} type error, rejection, console, ajax or timeout.
 * @param {String} message What happened.
 * @param {String} source Where it happened.
 */
export const report = (type, message, source = '') => {
    if (!courseid || sent >= MAX_EVENTS) {
        return;
    }
    const text = String(message || '').slice(0, 1500);
    const key = type + '|' + text;
    if (!text || seen.has(key)) {
        return;
    }
    seen.add(key);
    sent++;
    try {
        Ajax.call([{
            methodname: 'format_dari_log_client_event',
            args: {courseid, type, message: text, source: String(source || '').slice(0, 300),
                page: (window.location.pathname + window.location.search).slice(0, 300)},
        }])[0].catch(() => null);
    } catch (e) {
        // Diagnostics must never break the page.
    }
};

/**
 * Turn anything thrown into a message.
 *
 * @param {*} value Error, event reason or value.
 * @returns {String}
 */
const describe = (value) => {
    if (!value) {
        return '';
    }
    if (value instanceof Error) {
        return value.message + (value.stack ? '\n' + value.stack.split('\n').slice(0, 4).join('\n') : '');
    }
    if (typeof value === 'object') {
        if (value.message) {
            return String(value.message) + (value.errorcode ? ' [' + value.errorcode + ']' : '');
        }
        try {
            return JSON.stringify(value).slice(0, 1500);
        } catch (e) {
            return String(value);
        }
    }
    return String(value);
};

/**
 * Start recording.
 *
 * @param {Number} course The course id.
 */
export const init = (course) => {
    if (courseid) {
        return;
    }
    courseid = course;
    window.addEventListener('error', (e) => {
        const where = e.filename ? e.filename + ':' + e.lineno + ':' + e.colno : '';
        report('error', describe(e.error) || e.message, where);
    });
    window.addEventListener('unhandledrejection', (e) => {
        report('rejection', describe(e.reason));
    });
    const original = window.console && window.console.error;
    if (typeof original === 'function') {
        window.console.error = (...args) => {
            try {
                report('console', args.map(describe).join(' '));
            } catch (e) {
                // Ignore.
            }
            return original.apply(window.console, args);
        };
    }
};
