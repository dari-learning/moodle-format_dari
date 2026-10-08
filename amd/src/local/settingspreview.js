const captionOr = (caption, fallback) => caption || fallback;
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
 * Setting previews for the plugin's settings page.
 *
 * Every setting gets a small, accurate drawing of the course page it changes: real breadcrumbs,
 * real tabs, a banner with a title and a ring, a course index with ticks and time chips, cards
 * with chips and a progress bar. The part the setting changes is ringed in the theme primary and
 * named in a callout. Settings that switch something on or off draw both states side by side, and
 * the panel matching the current value is marked as the setting changes.
 *
 * Everything is inline SVG built here, with no images. Colours come from CSS custom properties
 * (see the settings page section of styles.css), so the drawings follow light and dark admin themes.
 * The drawings are decorative (aria-hidden); a visually hidden caption beside each one says in
 * words what it shows.
 *
 * @module     format_dari/local/settingspreview
 * @copyright  2026 Dari Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {Number} Drawing width. */
const W = 360;
/** @type {Number} Drawing height. */
const H = 214;
/** @type {Number} Counter that keeps gradient ids unique on the page. */
let uid = 0;

/**
 * Escape text for SVG.
 *
 * @param {String} s Text.
 * @returns {String}
 */
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/**
 * Rough text width for DM Sans at a size, good enough to place the next word.
 *
 * @param {String} s Text.
 * @param {Number} size Font size.
 * @param {Boolean} [bold] Semibold or bold.
 * @returns {Number}
 */
const tw = (s, size, bold) => s.length * size * (bold ? 0.6 : 0.56);

/**
 * A rectangle.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} c Class.
 * @param {Number} [r] Corner radius.
 * @returns {String}
 */
const r = (x, y, w, h, c, r) =>
    '<rect class="' +
    c +
    '" x="' +
    x +
    '" y="' +
    y +
    '" width="' +
    w +
    '" height="' +
    h +
    '"' +
    (r ? ' rx="' + r + '"' : '') +
    '/>';

/**
 * A line.
 *
 * @param {Number} x1 Start x.
 * @param {Number} y1 Start y.
 * @param {Number} x2 End x.
 * @param {Number} y2 End y.
 * @param {String} c Class.
 * @returns {String}
 */
const l = (x1, y1, x2, y2, c) => '<path class="' + c + '" d="M' + x1 + ' ' + y1 + 'L' + x2 + ' ' + y2 + '"/>';

/**
 * Text.
 *
 * @param {Number} x Left (or centre/end with an anchor).
 * @param {Number} y Baseline.
 * @param {String} s The words.
 * @param {String} [c] Classes: size (x8..x22), weight (b), colour (k2, k3, kw, kb, kok, kred).
 * @param {String} [a] Anchor: 'm' middle, 'e' end.
 * @returns {String}
 */
const t = (x, y, s, c, a) =>
    '<text class="x ' +
    (c || '') +
    '" x="' +
    x +
    '" y="' +
    y +
    '"' +
    (() => {
        if (a === 'm') {
            return ' text-anchor="middle"';
        }
        return a === 'e' ? ' text-anchor="end"' : '';
    })() +
    '>' +
    esc(s) +
    '</text>';

/**
 * A circle.
 *
 * @param {Number} cx Centre x.
 * @param {Number} cy Centre y.
 * @param {Number} r Radius.
 * @param {String} c Class.
 * @returns {String}
 */
const c = (cx, cy, r, c) => '<circle class="' + c + '" cx="' + cx + '" cy="' + cy + '" r="' + r + '"/>';

/**
 * A group, optionally moved.
 *
 * @param {String} inner Content.
 * @param {Number} [x] Shift x.
 * @param {Number} [y] Shift y.
 * @param {String} [c] Class.
 * @param {Number} [s] Scale.
 * @returns {String}
 */
const g = (inner, x, y, c, s) =>
    '<g' +
    (c ? ' class="' + c + '"' : '') +
    (x || y || s
        ? ' transform="translate(' + (x || 0) + ' ' + (y || 0) + ')' + (s ? ' scale(' + s + ')' : '') + '"'
        : '') +
    '>' +
    inner +
    '</g>';

/* --------------------------------------------------------------------------
   Icons, drawn on a 12 unit grid and placed with a translate.
   -------------------------------------------------------------------------- */
const ICON = {
    home: 'M1.5 5.5 6 1.8l4.5 3.7M2.8 4.6v5.9h6.4V4.6M5 10.5V7.6h2v2.9',
    grades: 'M2 10.5h8M3.3 9V6.5M6 9V3.5M8.7 9V5',
    spark: 'M6 1.2c.3 2.4 1.5 3.6 3.9 3.9-2.4.3-3.6 1.5-3.9 3.9-.3-2.4-1.5-3.6-3.9-3.9 2.4-.3 3.6-1.5 3.9-3.9z',
    clock: 'M6 2.9a3.95 3.95 0 1 0 0 7.9 3.95 3.95 0 1 0 0-7.9zM6 4.9v2l1.4 1M4.8 1.2h2.4',
    page: 'M3 1.5h4l2 2v7H3zM7 1.5v2h2M4.5 6h3M4.5 8h3',
    forum: 'M2 2.5h8v5.5H6l-2.5 2V8H2z',
    quiz: 'M2.5 3.5l1 1 2-2M2.5 8l1 1 2-2M7 4h3M7 8.5h3',
    assign: 'M3 1.5h6v9H3zM4.5 7.2l1.2 1.2 2-2.4M4.5 4h3',
    list: 'M4.5 3h6M4.5 6h6M4.5 9h4M1.5 3h1M1.5 6h1M1.5 9h1',
    chevr: 'M4.5 2.5 8 6l-3.5 3.5',
    chevl: 'M7.5 2.5 4 6l3.5 3.5',
    chevd: 'M2.5 4.5 6 8l3.5-3.5',
    close: 'M3 3l6 6M9 3 3 9',
    check: 'M2.8 6.2 5 8.4l4.2-4.6',
    send: 'M10.5 1.5 1.5 5.2l3.8 1.5 1.5 3.8z',
    speaker: 'M1.8 4.5h2l2.5-2.2v7.4L3.8 7.5h-2z',
    waves: 'M8 4.3c.8.9.8 2.5 0 3.4M9.6 2.9c1.6 1.8 1.6 4.4 0 6.2',
    mute: 'M8 4.5l3 3M11 4.5l-3 3',
    bell: 'M3 8.5V5.5a3 3 0 0 1 6 0v3l1 1H2zM5 10.5h2',
    grid: 'M1.5 1.5h3.5v3.5H1.5zM7 1.5h3.5v3.5H7zM1.5 7h3.5v3.5H1.5zM7 7h3.5v3.5H7z',
    dots: 'M6 2.5v.01M6 6v.01M6 9.5v.01',
    book: 'M6 3C4.8 2 3.2 1.8 1.5 2v7.5c1.7-.2 3.3 0 4.5 1 1.2-1 2.8-1.2 4.5-1V2C8.8 1.8 7.2 2 6 3zM6 3v7.5',
    target: 'M6 1.8a4.2 4.2 0 1 0 0 8.4 4.2 4.2 0 1 0 0-8.4zM6 4a2 2 0 1 0 0 4 2 2 0 1 0 0-4z',
    building: 'M2 10.5h8M3 10.5V1.8h4v8.7M7 4.5h2v6M4.3 3.6h1.4M4.3 5.6h1.4M4.3 7.6h1.4',
    arrowr: 'M2 6h8M7 3l3 3-3 3',
    cloud: 'M3.5 9.5h5.3a2.2 2.2 0 0 0 .3-4.4A3.2 3.2 0 0 0 3 5.3a2.1 2.1 0 0 0 .5 4.2z',
    lock: 'M3 5.5h6v5H3zM4.3 5.5V4a1.7 1.7 0 0 1 3.4 0v1.5',
    user: 'M6 6a2 2 0 1 0 0-4 2 2 0 1 0 0 4zM2.5 10.5c.5-2 1.8-3 3.5-3s3 1 3.5 3',
    heart: 'M6 10S1.5 7.3 1.5 4.4A2.2 2.2 0 0 1 6 3.3a2.2 2.2 0 0 1 4.5 1.1C10.5 7.3 6 10 6 10z',
    image: 'M1.5 2h9v8h-9zM1.5 8.2l2.6-2.4 2 1.8 1.5-1.2 2.9 2.4',
    flag: 'M2.5 11V1.5M2.5 2h6.5l-1.5 2.3L9 6.5H2.5'
};

/**
 * An icon.
 *
 * @param {String} k Key into ICON.
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} s Size in units (12 = 1:1).
 * @param {String} c Class (ic-2, ic-w, ic-b, ic-ok, plus ic-f for a filled glyph).
 * @returns {String}
 */
const i = (k, x, y, s, c) =>
    '<path class="ik ' +
    (c || 'ic-2') +
    '" transform="translate(' +
    x +
    ' ' +
    y +
    ') scale(' +
    s / 12 +
    ')" d="' +
    ICON[k] +
    '"/>';

/* --------------------------------------------------------------------------
   Annotation: the ring round the part a setting changes, the callout naming
   it, and the Off / On labels for two-state settings.
   -------------------------------------------------------------------------- */

/**
 * Ring a region in the primary colour.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Number} [r] Radius.
 * @returns {String}
 */
const hl = (x, y, w, h, r) => r(x, y, w, h, 'hl', r === undefined ? 4 : r);

/**
 * A callout: a primary pill with the name of what changes, and a leader to it.
 *
 * @param {Number} x Pill left.
 * @param {Number} y Pill top.
 * @param {String} s Label.
 * @param {Number} [tx] Leader end x.
 * @param {Number} [ty] Leader end y.
 * @returns {String}
 */
const co = (x, y, s, tx, ty) => {
    const w = Math.round(tw(s, 9, true) + 14);
    let out = '';
    if (tx !== undefined) {
        const sx = (() => {
            if (tx < x) {
                return x;
            }
            return tx > x + w ? x + w : x + w / 2;
        })();
        const sy = (() => {
            if (ty < y) {
                return y;
            }
            return ty > y + 15 ? y + 15 : y + 7.5;
        })();
        out += l(sx, sy, tx, ty, 'co-ln') + c(tx, ty, 2.2, 'co-dot');
    }
    return out + r(x, y, w, 15, 'co-bg', 7.5) + t(x + w / 2, y + 10.5, s, 'x9 b kw', 'm');
};

/**
 * One of the two states of a setting.
 *
 * @param {Number} x Left.
 * @param {Number} w Width.
 * @param {String} label "Off", "On", "Grid", ...
 * @param {Boolean} on Draw the label in the primary colour.
 * @param {String} values The setting values this panel stands for, space separated.
 * @param {String} inner Content, drawn from y=20 down.
 * @returns {String}
 */
const panel = (x, w, label, on, values, inner) => {
    const lw = Math.round(tw(label, 9, true) + 14);
    return (
        '<g class="pv-panel" data-v="' +
        values +
        '">' +
        r(x + 0.5, 18.5, w - 1, H - 19, 'pv-frame', 7) +
        r(x + 0.5, 18.5, w - 1, H - 19, 'pv-cur', 7) +
        r(x, 0, lw, 14, on ? 'tag-on' : 'tag-off', 7) +
        t(x + lw / 2, 10, label, 'x9 b ' + (on ? 'kw' : 'k2'), 'm') +
        t(x + lw + 6, 10, '', 'x9 b kb pv-curlabel') +
        '<svg x="' +
        (x + 1) +
        '" y="19" width="' +
        (w - 2) +
        '" height="' +
        (H - 20) +
        '" viewBox="0 0 ' +
        (w - 2) +
        ' ' +
        (H - 20) +
        '" overflow="hidden">' +
        inner +
        '</svg></g>'
    );
};

/**
 * Two panels side by side.
 *
 * @param {Array} a [label, on, values, inner] for the left panel.
 * @param {Array} b The same for the right panel.
 * @returns {String}
 */
const two = (a, b) => panel(0, 174, a[0], a[1], a[2], a[3]) + panel(186, 174, b[0], b[1], b[2], b[3]);

/**
 * Three panels side by side.
 *
 * @param {Array} list Three [label, on, values, inner].
 * @returns {String}
 */
const three = (list) => list.map((p, i) => panel(i * 122, 114, p[0], p[1], p[2], p[3])).join('');

/* --------------------------------------------------------------------------
   Page parts.
   -------------------------------------------------------------------------- */

/**
 * Moodle's navbar.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {tabs: true to carry the course tabs beside the links}
 * @returns {String}
 */
const navbar = (x, y, w, o) => {
    o = o || {};
    let s = r(x, y, w, 20, 'p-surf0') + l(x, y + 20, x + w, y + 20, 'p-line') + t(x + 8, y + 13.5, 'dari', 'x11 b');
    let lx = x + 34;
    const narrow = w < 240;
    const fs = narrow ? 8 : 9;
    const links = (() => {
        if (o.tabs) {
            return narrow ? ['Home', 'Course', 'Grades'] : ['Home', 'Course', 'Grades', 'Participants'];
        }
        return ['Home', 'Dashboard', 'My courses'];
    })();
    links.forEach((l, i) => {
        s += t(lx, y + 13, l, 'x' + fs + (o.tabs && i > 0 ? ' kb b' : ''));
        lx += tw(l, fs, o.tabs && i > 0) + (narrow ? 7 : 10);
    });
    if (!narrow) {
        s +=
            i('bell', x + w - 36, y + 4, 11, 'ic-2') +
            c(x + w - 13, y + 10, 6.5, 'p-chip') +
            t(x + w - 13, y + 12.8, 'SR', 'x8 b k2', 'm');
    }
    return s;
};

/**
 * A theme's logo band above the navbar.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const logoband = (x, y, w) =>
    r(x, y, w, 26, 'p-brandsoft') +
    r(x + 8, y + 5, 16, 16, 'p-brand', 4) +
    t(x + 16, y + 16.5, 'D', 'x10 b kw', 'm') +
    t(x + 30, y + 16.5, 'Dari Academy', 'x11 b') +
    (w >= 240 ? t(x + w - 8, y + 16, 'Learning portal', 'x9 k3', 'e') : '');

/**
 * The breadcrumb trail.
 *
 * @param {Number} x Left.
 * @param {Number} y Baseline.
 * @param {Number} [n] How many crumbs.
 * @returns {String}
 */
const crumbs = (x, y, n) => {
    const parts = ['Dashboard', 'My courses', 'WHS101', 'Spotting hazards'].slice(0, n || 4);
    let s = '';
    let cx = x;
    parts.forEach((p, i) => {
        const last = i === parts.length - 1;
        s += t(cx, y, p, 'x9 ' + (last ? 'k2' : 'kb'));
        cx += tw(p, 9) + 4;
        if (!last) {
            s += t(cx, y, '›', 'x9 k3');
            cx += 8;
        }
    });
    return s;
};

/**
 * The course tabs.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const tabs = (x, y, w) => {
    let s = l(x, y + 16, x + w, y + 16, 'p-line');
    let cx = x + 2;
    ['Course', 'Settings', 'Participants', 'Grades', 'Reports'].forEach((l, i) => {
        const lw = tw(l, 9, i === 0);
        if (cx + lw > x + w) {
            return;
        }
        s += t(cx, y + 11, l, 'x9 ' + (i === 0 ? 'b' : 'kb'));
        if (i === 0) {
            s += r(cx - 2, y + 14.5, lw + 4, 2, 'p-brand');
        }
        cx += lw + 11;
    });
    return s;
};

/**
 * The photograph used for image banners and cards.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} [tone] 'vivid' for saturated colours.
 * @returns {String}
 */
const photo = (x, y, w, h, tone) => {
    const id = 'pvg' + ++uid;
    const sky = tone === 'vivid' ? ['#3b82f6', '#93c5fd'] : ['#6f93ad', '#c9d8e2'];
    const hill1 = tone === 'vivid' ? '#16a34a' : '#5f7d6a';
    const hill2 = tone === 'vivid' ? '#15803d' : '#4b6656';
    return (
        '<defs><linearGradient id="' +
        id +
        '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' +
        sky[0] +
        '"/><stop offset="1" stop-color="' +
        sky[1] +
        '"/></linearGradient></defs>' +
        '<rect x="' +
        x +
        '" y="' +
        y +
        '" width="' +
        w +
        '" height="' +
        h +
        '" fill="url(#' +
        id +
        ')"/>' +
        '<circle cx="' +
        (x + w * 0.78) +
        '" cy="' +
        (y + h * 0.32) +
        '" r="' +
        h * 0.12 +
        '" fill="' +
        (tone === 'vivid' ? '#fde047' : '#efe6c8') +
        '"/>' +
        '<path fill="' +
        hill1 +
        '" d="M' +
        x +
        ' ' +
        (y + h * 0.72) +
        'Q' +
        (x + w * 0.3) +
        ' ' +
        (y + h * 0.42) +
        ' ' +
        (x + w * 0.62) +
        ' ' +
        (y + h * 0.7) +
        'T' +
        (x + w) +
        ' ' +
        (y + h * 0.6) +
        'V' +
        (y + h) +
        'H' +
        x +
        'z"/>' +
        '<path fill="' +
        hill2 +
        '" d="M' +
        x +
        ' ' +
        (y + h * 0.86) +
        'Q' +
        (x + w * 0.45) +
        ' ' +
        (y + h * 0.66) +
        ' ' +
        (x + w) +
        ' ' +
        (y + h * 0.84) +
        'V' +
        (y + h) +
        'H' +
        x +
        'z"/>'
    );
};

/**
 * A progress ring.
 *
 * @param {Number} cx Centre x.
 * @param {Number} cy Centre y.
 * @param {Number} r Radius.
 * @param {Number} pct 0..100.
 * @param {Boolean} [onmedia] White, over a picture.
 * @returns {String}
 */
const ring = (cx, cy, r, pct, onmedia) => {
    const c = 2 * Math.PI * r;
    return (
        c(cx, cy, r, onmedia ? 'ring-tw' : 'ring-t') +
        '<circle class="' +
        (onmedia ? 'ring-aw' : 'ring-a') +
        '" cx="' +
        cx +
        '" cy="' +
        cy +
        '" r="' +
        r +
        '" stroke-dasharray="' +
        ((c * pct) / 100).toFixed(1) +
        ' ' +
        c.toFixed(1) +
        '" transform="rotate(-90 ' +
        cx +
        ' ' +
        cy +
        ')"/>' +
        t(cx, cy + 3, pct + '%', 'x8 b ' + (onmedia ? 'kw' : ''), 'm')
    );
};

/**
 * The course banner.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {image, scrim (0..1), fade (0..1), title, chevrons, compact}
 * @returns {String}
 */
const banner = (x, y, w, h, o) => {
    o = o || {};
    const media = !!o.image;
    let s = '';
    if (media) {
        s +=
            photo(x, y, w, h) +
            '<rect x="' +
            x +
            '" y="' +
            y +
            '" width="' +
            w +
            '" height="' +
            h +
            '" fill="#020617" fill-opacity="' +
            (() => {
                if (o.scrim === undefined) {
                    return 0.5;
                }
                return o.scrim;
            })() +
            '"/>';
    } else {
        s += r(x, y, w, h, 'p-banner');
        if (o.fade) {
            s +=
                '<rect class="p-c" x="' +
                x +
                '" y="' +
                y +
                '" width="' +
                w +
                '" height="' +
                h +
                '" fill-opacity="' +
                o.fade +
                '"/>';
        }
    }
    s += r(x + 0.5, y + 0.5, w - 1, h - 1, 'p-edge');
    const kt = (() => {
        if (media) {
            return ' kw';
        }
        return '';
    })();
    const wide = w >= 260 && !o.compact;
    const title =
        o.title ||
        (() => {
            if (wide) {
                return 'Leading Safe Workplaces';
            }
            return (() => {
                if (w < 130) {
                    return 'WHS101';
                }
                return 'WHS for Supervisors';
            })();
        })();
    const btn = (bx, by, k, ai) =>
        r(
            bx,
            by,
            17,
            17,
            (() => {
                if (ai) {
                    return 'p-brand';
                }
                return media ? 'p-glass' : 'p-btn';
            })(),
            4
        ) + i(k, bx + 2.5, by + 2.5, 12, ai || media ? 'ic-w' : 'ic-b');
    if (wide) {
        const cy = y + h / 2;
        s += btn(x + 8, cy - 8.5, 'home') + btn(x + 28, cy - 8.5, 'grades') + btn(x + 48, cy - 8.5, 'spark', true);
        s +=
            t(x + w / 2 + 6, cy - 1, title, 'x11 b' + kt, 'm') +
            t(
                x + w / 2 + 6,
                cy + 11,
                '3 modules · 5 activities · 1 hr 10 min',
                'x8 ' +
                    (() => {
                        if (media) {
                            return 'kw';
                        }
                        return 'k2';
                    })(),
                'm'
            );
        s += ring(x + w - 20, cy, 11, 42, media);
    } else {
        s += t(x + 8, y + 16, title, 'x10 b' + kt);
        if (h >= 58) {
            s += t(
                x + 8,
                y + 28,
                '3 modules · 5 activities',
                'x8 ' +
                    (() => {
                        if (media) {
                            return 'kw';
                        }
                        return 'k2';
                    })()
            );
        }
        s +=
            btn(x + 8, y + h - 22, 'home') + btn(x + 28, y + h - 22, 'grades') + btn(x + 48, y + h - 22, 'spark', true);
        s += ring(x + w - 18, y + h - 15, 10, 42, media);
    }
    if (o.chevrons && !wide) {
        const cy2 = y + h - 13.5;
        s +=
            c(
                x + 82,
                cy2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            i(
                'chevl',
                x + 76,
                cy2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            ) +
            c(
                x + 102,
                cy2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            i(
                'chevr',
                x + 96,
                cy2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            );
    } else if (o.chevrons) {
        s +=
            c(
                x + w - 52,
                y + h / 2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            i(
                'chevl',
                x + w - 58,
                y + h / 2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            ) +
            c(
                x + w - 32,
                y + h / 2,
                8,
                (() => {
                    if (media) {
                        return 'p-glass';
                    }
                    return 'p-btn';
                })()
            ) +
            i(
                'chevr',
                x + w - 38,
                y + h / 2 - 6,
                12,
                (() => {
                    if (media) {
                        return 'ic-w';
                    }
                    return 'ic-b';
                })()
            );
    }
    return s;
};

/** @type {Array} The index rows. */
const INDEXROWS = [
    {
        sec: 'General',
        open: false,
        general: true
    },
    {
        sec: 'Your duty of care',
        open: true
    },
    {
        act: 'Why supervisors care',
        icon: 'page',
        time: '10 min',
        done: true
    },
    {
        sec: 'Spotting hazards',
        open: true
    },
    {
        act: 'Hierarchy of control',
        icon: 'page',
        time: '10 min',
        done: true
    },
    {
        act: 'Risk assessment',
        icon: 'assign',
        time: '30 min',
        done: false,
        current: true
    },
    {
        act: 'Hazard quiz',
        icon: 'quiz',
        time: '15 min',
        done: false
    },
    {
        sec: 'Consultation',
        open: false
    }
];

/**
 * The course index.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {plain, time, total, logo, nogeneral, head (colour class), heading, icons, bg, op, mark}
 * @returns {String}
 */
const index = (x, y, w, h, o) => {
    o = o || {};
    let s = '<svg x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" overflow="hidden">';
    s += r(0, 0, w, h, 'p-index');
    if (o.bg) {
        s +=
            '<rect class="p-c" x="0" y="0" width="' +
            w +
            '" height="' +
            h +
            '" fill-opacity="' +
            (o.op || 0.16) +
            '"/>';
    }
    let cy = 0;
    if (o.plain) {
        s += t(8, 14, 'Course index', 'x9 b k2');
        cy = 22;
        INDEXROWS.forEach((r) => {
            if (r.general && o.nogeneral) {
                return;
            }
            if (r.sec) {
                s += i(r.open ? 'chevd' : 'chevr', 5, cy + 3, 10, 'ic-2') + t(17, cy + 11, r.sec, 'x9 b');
            } else {
                s += (r.current ? r(0, cy, w, 17, 'p-brandsoft') : '') + t(17, cy + 11, r.act, 'x9 kb');
            }
            cy += 17;
        });
        return s + l(w - 0.5, 0, w - 0.5, h, 'p-line') + '</svg>';
    }
    // Player header: logo row, course title, total time, ring.
    if (o.logo) {
        s +=
            r(0, 0, w, 22, 'p-surf0') +
            r(7, 4, 14, 14, 'p-brand', 3) +
            t(14, 14.5, 'D', 'x9 b kw', 'm') +
            t(25, 14.5, 'Dari Academy', 'x9 b');
        cy = 22;
    } else {
        s += i('grid', w - 46, 4, 10, 'ic-2') + i('dots', w - 30, 4, 10, 'ic-2') + i('close', w - 15, 4, 10, 'ic-2');
        cy = 16;
    }
    s += r(0, cy, w, 34, o.head ? 'p-c' : 'p-head');
    const ink = o.head ? 'kw' : 'kb';
    s += t(7, cy + 14, w < 130 ? 'WHS101' : 'WHS for Supervisors', 'x9 b ' + ink + (o.head ? ' onc' : ''));
    if (o.total !== false) {
        s += t(7, cy + 26, '1 hr 10 min', 'x8 ' + (o.head ? 'kw onc' : 'k2'));
    }
    if (w >= 90) {
        s += ring(w - 16, cy + 17, 10, 42, !!o.head);
    }
    if (o.mark === 'total' && o.total !== false) {
        s += hl(4, cy + 18, 50, 12, 3);
    }
    if (o.mark === 'logo' && o.logo) {
        s += hl(3, 2, Math.min(w - 6, 84), 18, 3);
    }
    if (o.mark === 'header') {
        s += hl(1, cy + 1, w - 2, 32, 2);
    }
    cy += 34;
    INDEXROWS.forEach((r) => {
        if (r.general && o.nogeneral) {
            return;
        }
        if (r.sec) {
            s +=
                r(0, cy, w, 20, o.heading ? 'p-c' : 'p-brand') +
                l(0, cy + 20, w, cy + 20, 'p-sep') +
                i(r.open ? 'chevd' : 'chevr', 5, cy + 4, 11, 'ic-w' + (o.heading ? ' onc' : '')) +
                t(19, cy + 13.5, r.sec, 'x9 b kw' + (o.heading ? ' onc' : ''));
            cy += 20;
        } else {
            if (r.current) {
                s += r(0, cy, w, 20, 'p-brandsoft') + r(0, cy, 2.5, 20, 'p-brand');
            }
            s +=
                r(6, cy + 4, 12, 12, o.icons ? 'p-cs' : 'p-brandsoft') +
                i(r.icon, 7, cy + 5, 10, o.icons ? 'ic-c' : 'ic-b');
            const room = w - 24 - (o.time === false ? 16 : 52);
            let name = r.act;
            while (name.length > 4 && tw(name, 9.5) > room) {
                name = name.slice(0, -2).trim() + '…';
                name = name.replace(/…+$/, '…');
            }
            s += t(23, cy + 13.5, name, 'x9');
            if (o.time !== false) {
                s += r(w - 50, cy + 5, 32, 11, 'p-chip') + t(w - 34, cy + 13.2, r.time, 'x8 k2', 'm');
                if (o.mark === 'time') {
                    s += hl(w - 52, cy + 3.5, 36, 14, 3);
                }
            }
            s += r.done
                ? c(w - 9, cy + 10, 5, 'p-ok') + i('check', w - 14, cy + 5, 10, 'ic-w')
                : c(w - 9, cy + 10, 4.5, 'p-tick0');
            s += l(0, cy + 20, w, cy + 20, 'p-line');
            cy += 20;
        }
    });
    return s + l(w - 0.5, 0, w - 0.5, h, 'p-line') + '</svg>';
};

/**
 * A section card.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {colour, op, big, small, acts, limit, time, image, mark, title, eyebrow, cta}
 * @returns {String} Markup; the card is as tall as its content.
 */
const card = (x, y, w, o) => {
    o = o || {};
    const mh = o.mh || 56;
    let s = '';
    if (o.image) {
        s +=
            '<svg x="' +
            (x + 4) +
            '" y="' +
            (y + 4) +
            '" width="' +
            (w - 8) +
            '" height="' +
            mh +
            '">' +
            photo(0, 0, w - 8, mh) +
            '</svg>';
    } else {
        s +=
            r(x + 4, y + 4, w - 8, mh, 'p-surf0', 5) +
            '<rect class="' +
            (() => {
                if (o.colour) {
                    return 'p-c';
                }
                return 'p-brand';
            })() +
            '" x="' +
            (x + 4) +
            '" y="' +
            (y + 4) +
            '" width="' +
            (w - 8) +
            '" height="' +
            mh +
            '" rx="5" fill-opacity="' +
            (() => {
                if (o.op === undefined) {
                    return 1;
                }
                return o.op;
            })() +
            '"/>';
    }
    const onpanel = o.op === undefined || o.op > 0.45 || o.image;
    const onc = (() => {
        if (o.colour) {
            return ' onc';
        }
        return '';
    })();
    const mini = w < 110;
    if (mh >= 32) {
        s +=
            r(x + 9, y + 9, tw(captionOr(o.eyebrow, 'Module 2'), 8, true) + 10, 12, 'p-glass', 6) +
            t(
                x + 14,
                y + 17.5,
                captionOr(o.eyebrow, 'Module 2'),
                'x8 b ' +
                    (() => {
                        if (onpanel) {
                            return 'kw' + onc;
                        }
                        return '';
                    })()
            );
    }
    const ts = (() => {
        if (o.big) {
            return 'x13';
        }
        return (() => {
            if (o.small) {
                return 'x9';
            }
            return 'x11';
        })();
    })();
    s += t(
        x + 10,
        y + mh - 3,
        captionOr(o.title, 'Spotting hazards'),
        ts +
            ' b ' +
            (() => {
                if (onpanel) {
                    return 'kw' + onc;
                }
                return '';
            })()
    );
    if (o.mark === 'title') {
        s += hl(
            x + 7,
            y +
                mh -
                (() => {
                    if (o.big) {
                        return 16;
                    }
                    return 13;
                })(),
            tw(
                captionOr(o.title, 'Spotting hazards'),
                (() => {
                    if (o.big) {
                        return 13;
                    }
                    return (() => {
                        if (o.small) {
                            return 9;
                        }
                        return 11;
                    })();
                })(),
                true
            ) + 8,
            (() => {
                if (o.big) {
                    return 17;
                }
                return 14;
            })(),
            3
        );
    }
    if (o.mark === 'panel') {
        s += hl(x + 3, y + 3, w - 6, mh + 2, 6);
    }
    let cy = y + mh + 10;
    // Chips.
    let cx = x + 8;
    const chip = (k, label, hl) => {
        const cw = tw(label, 8) + 22;
        let c = r(cx, cy, cw, 13, 'p-chip', 4) + i(k, cx + 3, cy + 2, 9, 'ic-b') + t(cx + 15, cy + 9.5, label, 'x8 k2');
        if (hl) {
            c += hl(cx - 2, cy - 2, cw + 4, 17, 4);
        }
        cx += cw + 4;
        return c;
    };
    if (!mini) {
        s += chip('list', (o.acts || 4) + ' activities');
        if (o.time !== false) {
            s += chip('clock', '45 min', o.mark === 'time');
        }
        cy += 20;
    }
    if (o.list) {
        const names = ['Hierarchy of control', 'Risk assessment', 'Hazard quiz', 'Site walk', 'Toolbox talk'];
        const n = o.limit || names.length;
        names.slice(0, n).forEach((nm, i) => {
            s +=
                i(
                    (() => {
                        if (i === 2) {
                            return 'quiz';
                        }
                        return (() => {
                            if (i === 1) {
                                return 'assign';
                            }
                            return 'page';
                        })();
                    })(),
                    x + 8,
                    cy - 1,
                    10,
                    'ic-b'
                ) +
                t(x + 21, cy + 7, nm, 'x8') +
                (() => {
                    if (i < 2) {
                        return c(x + w - 14, cy + 4, 4, 'p-ok') + i('check', x + w - 18, cy, 8, 'ic-w');
                    }
                    return c(x + w - 14, cy + 4, 3.6, 'p-tick0');
                })();
            cy += 13;
        });
        if (o.limit && o.limit < names.length) {
            s += t(x + 21, cy + 7, '+ ' + (names.length - o.limit) + ' more', 'x8 kb b');
            cy += 13;
        }
        if (o.mark === 'list') {
            s += hl(x + 5, y + mh + 28, w - 10, cy - (y + mh + 28) + 2, 3);
        }
        cy += 9;
    }
    // Progress.
    if (mini) {
        s +=
            r(x + 8, cy, w - 16, 4, 'p-track', 2) +
            r(x + 8, cy, (w - 16) / 2, 4, 'p-brand', 2) +
            t(x + 8, cy + 15, '2 of 4 done', 'x8 b k2');
        cy += 22;
    } else {
        s +=
            r(x + 8, cy, w - 66, 4, 'p-track', 2) +
            r(x + 8, cy, (w - 66) / 2, 4, 'p-brand', 2) +
            t(x + w - 8, cy + 5, '2 of 4 done', 'x8 b k2', 'e');
        cy += 12;
    }
    if (o.cta !== false && !mini) {
        s +=
            l(x + 8, cy, x + w - 8, cy, 'p-line') +
            t(x + 8, cy + 14, 'In progress', 'x8 k3') +
            r(x + w - 66, cy + 4, 58, 15, 'p-brandsoft', 5) +
            t(x + w - 37, cy + 14.5, 'Continue →', 'x8 b kb', 'm');
        cy += 24;
    }
    const body = (() => {
        if (o.body) {
            return (
                '<rect class="p-c" x="' +
                x +
                '" y="' +
                y +
                '" width="' +
                w +
                '" height="' +
                (cy - y) +
                '" rx="8" fill-opacity="' +
                (() => {
                    if (o.bodyop === undefined) {
                        return 0.35;
                    }
                    return o.bodyop;
                })() +
                '"/>' +
                (() => {
                    if (o.mark === 'body') {
                        return hl(x - 2, y - 2, w + 4, cy - y + 4, 9);
                    }
                    return '';
                })()
            );
        }
        return '';
    })();
    return r(x, y, w, cy - y, 'p-card', 8) + body + s;
};

/**
 * An activity tile.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {time, title, kind, icon, mark}
 * @returns {String}
 */
const tile = (x, y, w, o) => {
    o = o || {};
    const mh = 40;
    let s =
        r(x, y, w, mh + 30, 'p-card', 7) +
        r(x + 3, y + 3, w - 6, mh, 'p-brand', 5) +
        r(x + 8, y + 8, 14, 14, 'p-glass', 3) +
        i(o.icon || 'page', x + 9, y + 9, 12, 'ic-w') +
        t(x + 8, y + mh - 4, w < 100 ? o.short || 'Controls' : o.title || 'Hierarchy of control', 'x9 b kw');
    let cx = x + 7;
    const cy = y + mh + 10;
    const kind = o.kind || 'Page';
    if (w >= 100) {
        s +=
            r(cx, cy, tw(kind, 8) + 20, 13, 'p-chip', 4) +
            i(o.icon || 'page', cx + 3, cy + 2, 9, 'ic-2') +
            t(cx + 14, cy + 9.5, kind, 'x8 k2');
        cx += tw(kind, 8) + 24;
    }
    if (o.time !== false) {
        s +=
            r(cx, cy, 44, 13, 'p-chip', 4) +
            i('clock', cx + 3, cy + 2, 9, 'ic-b') +
            t(cx + 15, cy + 9.5, o.timelabel || '10 min', 'x8 k2');
        if (o.mark === 'time') {
            s += hl(cx - 2, cy - 2, 48, 17, 4);
        }
    }
    return s;
};

/**
 * Moodle's standard activity list for a section.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const stdlist = (x, y, w) => {
    let s = t(x, y + 11, 'Spotting hazards', 'x11 b');
    const rows = [
        ['Hierarchy of control', 'page', 'Done: View', true],
        ['Risk assessment', 'assign', 'To do: Submit', false],
        ['Hazard quiz', 'quiz', 'To do: Receive a grade', false]
    ];
    let cy = y + 20;
    rows.forEach((r) => {
        s +=
            r(x, cy, w, 34, 'p-card', 5) +
            r(x + 7, cy + 7, 20, 20, 'p-modico', 4) +
            i(r[1], x + 11, cy + 11, 12, 'ic-w') +
            t(x + 33, cy + 15, r[0], 'x9 b kb');
        const bw = tw(r[2], 8) + 10;
        s +=
            r(x + 33, cy + 20, bw, 11, r[3] ? 'p-okbadge' : 'p-chip', 3) +
            t(x + 38, cy + 28, r[2], 'x8 ' + (r[3] ? 'kok b' : 'k2'));
        cy += 39;
    });
    return s;
};

/**
 * The site footer.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @returns {String}
 */
const footer = (x, y, w) => {
    let s = r(x, y, w, 40, 'p-foot') + t(x + 10, y + 14, 'Contact site support', 'x9 kfl');
    s += t(x + 10, y + 27, 'Data retention summary', 'x9 kfl');
    if (w > 220) {
        s += t(x + 130, y + 14, 'Get the mobile app', 'x9 kfl') + t(x + 130, y + 27, 'Powered by Moodle', 'x9 kf');
    } else {
        s += t(x + 10, y + 37, 'Powered by Moodle', 'x8 kf');
    }
    return s;
};

/**
 * The Ask Dari panel.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {Object} [o] {name (false = no first name), answer, tools, support, quiz, mark}
 * @returns {String}
 */
const chat = (x, y, w, h, o) => {
    o = o || {};
    let s =
        '<svg x="' +
        x +
        '" y="' +
        y +
        '" width="' +
        w +
        '" height="' +
        h +
        '" overflow="hidden">' +
        r(0.5, 0.5, w - 1, h - 1, 'p-card', 9) +
        r(0.5, 0.5, w - 1, 30, 'p-brand', 9) +
        r(0.5, 20, w - 1, 11, 'p-brand') +
        r(8, 7, 17, 17, 'p-glass', 4) +
        i('spark', 10.5, 9.5, 12, 'ic-wf') +
        t(31, 14, 'Ask Dari', 'x10 b kw') +
        t(31, 25, 'WHS for Supervisors', 'x8 kw') +
        i('close', w - 18, 9, 12, 'ic-w');
    let cy = 40;
    const bot = (lines, hl) => {
        const bh = lines.length * 11 + 8;
        let b =
            r(8, cy, 13, 13, 'p-brandsoft', 3) +
            i('spark', 9.5, cy + 1.5, 10, 'ic-bf') +
            r(25, cy, w - 33, bh, 'p-bubble', 6);
        lines.forEach((l, i) => {
            b += t(31, cy + 12 + i * 11, l, 'x8' + (i === 0 && o.bold ? ' b' : ''));
        });
        if (hl) {
            b += hl(23, cy - 2, w - 29, bh + 4, 6);
        }
        cy += bh + 6;
        return b;
    };
    const me = (l) => {
        const bw = Math.min(w - 40, tw(l, 8) + 14);
        const b = r(w - 8 - bw, cy, bw, 18, 'p-brand', 6) + t(w - 15, cy + 12, l, 'x8 kw', 'e');
        cy += 24;
        return b;
    };
    s += bot(
        [o.name === false ? 'Hi there, what are we working' : 'Hi Sam, what are we working', 'on today?'],
        o.mark === 'name'
    );
    if (o.question) {
        s += me(o.question);
    }
    if (o.answer) {
        s += bot(o.answer, o.mark === 'answer');
    }
    if (o.tools !== false) {
        s += t(8, cy + 8, 'Pick a tool, or type your own question', 'x8 k2');
        cy += 14;
        [
            ['book', 'Explain the concept simply'],
            ['target', 'Give me some practice questions'],
            ['building', 'Show me a real-world example']
        ].forEach((t) => {
            s +=
                r(8, cy, w - 16, 19, 'p-card', 5) +
                r(12, cy + 3.5, 12, 12, 'p-brandsoft', 3) +
                i(t[0], 13, cy + 4.5, 10, 'ic-b') +
                t(29, cy + 12.5, t[1], 'x8 b');
            cy += 22;
        });
    }
    s +=
        r(8, h - 26, w - 16, 19, 'p-card', 6) +
        t(15, h - 13.5, 'Ask about this course…', 'x8 k3') +
        r(w - 29, h - 24, 18, 15, 'p-brand', 4) +
        i('send', w - 26, h - 22.5, 11, 'ic-w');
    return s + '</svg>';
};

/**
 * The tour popover.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Object} [o] {voice: true (narrated) | false (muted) | undefined (no button), lang, mark}
 * @returns {String}
 */
const tour = (x, y, w, o) => {
    o = o || {};
    let s =
        r(x, y, w, 108, 'p-pop', 9) +
        r(x, y, w, 3, 'p-brand', 1.5) +
        t(x + 10, y + 17, '1 / 8', 'x9 b kb') +
        t(x + 10, y + 33, 'Welcome to your course', 'x11 b') +
        t(x + 10, y + 47, 'A one-minute look at how this', 'x8 k2') +
        t(x + 10, y + 58, 'course works. Skip any time.', 'x8 k2');
    if (o.voice !== undefined) {
        s +=
            r(x + w - 26, y + 7, 18, 16, 'p-btn', 4) +
            i('speaker', x + w - 24, y + 9, 12, 'ic-2') +
            (o.voice ? i('waves', x + w - 24, y + 9, 12, 'ic-b') : i('mute', x + w - 24, y + 9, 12, 'ic-2'));
        if (o.mark === 'voice') {
            s += hl(x + w - 29, y + 4, 24, 22, 4);
        }
    }
    let dx = x + 10;
    for (let i = 0; i < 8; i++) {
        s += r(dx, y + 68, i === 0 ? 12 : 4, 4, i === 0 ? 'p-brand' : 'p-track', 2);
        dx += i === 0 ? 16 : 7;
    }
    s +=
        t(x + 10, y + 95, 'End tour', 'x9 k2') +
        t(x + w - 74, y + 95, 'Back', 'x9 kb') +
        r(x + w - 46, y + 82, 38, 19, 'p-brand', 5) +
        t(x + w - 27, y + 95, 'Next', 'x9 b kw', 'm');
    if (o.lang) {
        s +=
            r(x + 10, y + 113, tw(o.lang, 8) + 26, 15, 'p-chip', 7) +
            i('speaker', x + 14, y + 115, 11, 'ic-b') +
            t(x + 28, y + 123.5, o.lang, 'x8 k2');
    }
    return s;
};

/**
 * The AI connection: Moodle, then the school's AI provider.
 *
 * @param {Object} [o] {mark: 'endpoint' | 'secret' | 'textmodel' | 'imagemodel' | 'content' | 'scene', direct}
 * @returns {String}
 */
const flow = (o) => {
    o = o || {};
    let s =
        r(8, 46, 110, 82, 'p-card', 9) +
        r(18, 56, 20, 20, 'p-brand', 5) +
        t(28, 70, 'D', 'x11 b kw', 'm') +
        t(44, 65, 'Moodle', 'x11 b') +
        t(44, 77, 'with Dari', 'x8 k2') +
        t(18, 96, 'Question', 'x8 k2') +
        t(18, 108, '+ course content', 'x8 k2') +
        t(18, 120, '+ learner first name', 'x8 k3');
    s +=
        r(242, 46, 110, 82, 'p-card', 9) +
        i('cloud', 252, 54, 24, 'ic-b') +
        t(282, 65, 'Your AI', 'x11 b') +
        t(282, 77, 'provider', 'x11 b') +
        t(252, 98, o.direct ? 'OpenAI-compatible' : 'Set in Site admin', 'x8 k2') +
        t(252, 110, o.direct ? 'endpoint' : '> General > AI', 'x8 k2');
    s +=
        l(122, 74, 236, 74, 'p-arrow') +
        '<path class="p-arrowh" d="M231 70l6 4-6 4"/>' +
        t(179, 68, 'question', 'x8 k2', 'm');
    s +=
        l(236, 100, 122, 100, 'p-arrow') +
        '<path class="p-arrowh" d="M127 96l-6 4 6 4"/>' +
        t(179, 114, 'answer or image', 'x8 k2', 'm');
    if (o.direct) {
        const rows = [
            ['endpoint', 'Endpoint', 'https://ai.school.edu/v1'],
            ['secret', 'API key', '•••••••• 4f2a'],
            ['textmodel', 'Text model', 'gpt-4o-mini'],
            ['imagemodel', 'Image model', 'gpt-image-1']
        ];
        rows.forEach((r, i) => {
            const ry = 140 + i * 17;
            s += t(150, ry + 9, r[1], 'x8 k2', 'e') + r(156, ry, 160, 13, 'p-chip', 3) + t(161, ry + 9.5, r[2], 'x8');
            if (o.mark === r[0]) {
                s += hl(154, ry - 2, 164, 17, 4);
            }
        });
        s += l(236, 134, 236, 205, 'p-dash');
    }
    if (o.mark === 'content') {
        s += hl(14, 100, 90, 12, 3) + co(14, 150, 'Course content, up to your limit', 40, 112);
    }
    s += t(
        8,
        24,
        o.direct ? 'Moodle 4.4: Dari calls your provider directly' : "Dari uses your site's own AI provider",
        'x10 b'
    );
    return s;
};

/**
 * The standard page: navbar, index, banner and two cards.
 *
 * @param {Object} [o] {index: INDEX options | false, banner: BANNER options | false, card: CARD options}
 * @returns {String}
 */
const page = (o) => {
    o = o || {};
    let s = r(0, 0, W, H, 'p-page', 0) + navbar(0, 0, W);
    const ix = o.index === false ? 0 : 118;
    if (o.index !== false) {
        s += index(0, 20, 118, H - 20, o.index || {});
    }
    const cx = ix + 8;
    const cw = W - cx - 8;
    let cy = 28 + (o.top || 0);
    if (o.banner !== false) {
        s += banner(cx, cy, cw, 46, o.banner || {});
        cy += 54;
    }
    const half = (cw - 8) / 2;
    const card = Object.assign(
        {
            cta: false
        },
        o.card || {}
    );
    s +=
        card(cx, cy, half, card) +
        card(
            cx + half + 8,
            cy,
            half,
            Object.assign({}, card, {
                title: 'Consultation',
                eyebrow: 'Module 3'
            })
        );
    return s;
};

/* --------------------------------------------------------------------------
   The drawings, by setting.
   -------------------------------------------------------------------------- */

/**
 * An image thumbnail in one of the card image styles.
 *
 * @param {Number} x Left.
 * @param {Number} y Top.
 * @param {Number} w Width.
 * @param {Number} h Height.
 * @param {String} style photo | illustration | render3d | flat | standard | hd | natural | vivid
 * @returns {String}
 */
const thumb = (x, y, w, h, style) => {
    let s = '<svg x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" overflow="hidden">';
    if (style === 'illustration') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#fde7c7"/><circle cx="' +
            w * 0.75 +
            '" cy="' +
            h * 0.3 +
            '" r="' +
            h * 0.13 +
            '" fill="#f59e0b" stroke="#7c2d12" stroke-width="1.2"/>' +
            '<path d="M0 ' +
            h * 0.75 +
            'Q' +
            w * 0.3 +
            ' ' +
            h * 0.4 +
            ' ' +
            w * 0.6 +
            ' ' +
            h * 0.7 +
            'T' +
            w +
            ' ' +
            h * 0.6 +
            'V' +
            h +
            'H0z" fill="#84cc16" stroke="#365314" stroke-width="1.2"/>' +
            '<path d="M' +
            w * 0.2 +
            ' ' +
            h * 0.8 +
            'l6-14 6 14z" fill="#0ea5e9" stroke="#0c4a6e" stroke-width="1.2"/>';
    } else if (style === 'render3d') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#e0e7ff"/><defs><radialGradient id="pvr' +
            ++uid +
            '" cx=".35" cy=".35" r=".7"><stop offset="0" stop-color="#fff"/><stop offset=".5" stop-color="#60a5fa"/>' +
            '<stop offset="1" stop-color="#1e3a8a"/></radialGradient></defs>' +
            '<ellipse cx="' +
            w * 0.5 +
            '" cy="' +
            h * 0.82 +
            '" rx="' +
            w * 0.32 +
            '" ry="5" fill="#94a3b8" opacity=".5"/>' +
            '<circle cx="' +
            w * 0.5 +
            '" cy="' +
            h * 0.5 +
            '" r="' +
            h * 0.3 +
            '" fill="url(#pvr' +
            uid +
            ')"/>';
    } else if (style === 'flat') {
        s +=
            '<rect width="' +
            w +
            '" height="' +
            h +
            '" fill="#dbeafe"/><rect x="' +
            w * 0.15 +
            '" y="' +
            h * 0.45 +
            '" width="' +
            w * 0.3 +
            '" height="' +
            h * 0.4 +
            '" fill="#2563eb"/><circle cx="' +
            w * 0.68 +
            '" cy="' +
            h * 0.55 +
            '" r="' +
            h * 0.2 +
            '" fill="#f97316"/><rect y="' +
            h * 0.85 +
            '" width="' +
            w +
            '" height="' +
            h * 0.15 +
            '" fill="#1e293b"/>';
    } else {
        s += photo(0, 0, w, h, style === 'vivid' ? 'vivid' : '');
        if (style === 'standard') {
            const drawStandardTexture = () => {
                for (let i = 0; i < w; i += 6) {
                    for (let j = 0; j < h; j += 6) {
                        if ((i + j) % 12 === 0) {
                            s +=
                                '<rect x="' +
                                i +
                                '" y="' +
                                j +
                                '" width="6" height="6" fill="#fff" fill-opacity=".1"/>';
                        }
                    }
                }
            };
            drawStandardTexture();
        }
    }
    return s + '</svg>' + r(x + 0.5, y + 0.5, w - 1, h - 1, 'p-edge', 3);
};

/**
 * Show/hide values as Moodle offers them.
 */
const drawings = {
    accentcolour: () => {
        return {
            cap: 'The accent colour on the course page: banner buttons, index headings, card panels and progress bars.',
            svg:
                page({
                    top: 16,
                    index: {
                        heading: true
                    },
                    card: {
                        colour: true
                    }
                }) +
                hl(171, 62, 22, 22, 4) +
                hl(1, 70, 116, 22, 2) +
                co(210, 24, 'Accent colour', 182, 62)
        };
    },
    herobannerfade: () => {
        return {
            cap: 'A banner without an image, untinted on the left and tinted with the accent colour on the right.',
            svg: two(
                [
                    'No tint',
                    false,
                    '0',
                    banner(6, 10, 160, 60, {
                        compact: true
                    }) +
                        card(6, 80, 160, {
                            cta: false
                        })
                ],
                [
                    'Tinted',
                    true,
                    '',
                    banner(6, 10, 160, 60, {
                        compact: true,
                        fade: 0.28
                    }) +
                        hl(5, 9, 162, 62, 2) +
                        card(6, 80, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    heroimageoverlay: () => {
        return {
            cap: 'A banner photograph darkened lightly, medium and strongly behind white title text.',
            svg: three([
                [
                    'Light',
                    false,
                    'light',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.25,
                        compact: true
                    }) + t(57, 156, 'Photo shows most', 'x8 k2', 'm')
                ],
                [
                    'Medium',
                    true,
                    'medium',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.45,
                        compact: true
                    }) +
                        hl(3, 9, 108, 122, 2) +
                        t(57, 156, 'Recommended', 'x8 kb b', 'm')
                ],
                [
                    'Strong',
                    false,
                    'strong',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.7,
                        compact: true
                    }) + t(57, 156, 'Text stands out most', 'x8 k2', 'm')
                ]
            ])
        };
    },
    scrimstrength: () => {
        return {
            cap: 'A banner photograph darkened lightly, medium and strongly behind white title text.',
            svg: three([
                [
                    'Light',
                    false,
                    'light',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.25,
                        compact: true
                    }) + t(57, 156, 'Photo shows most', 'x8 k2', 'm')
                ],
                [
                    'Medium',
                    true,
                    'medium',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.45,
                        compact: true
                    }) +
                        hl(3, 9, 108, 122, 2) +
                        t(57, 156, 'Recommended', 'x8 kb b', 'm')
                ],
                [
                    'Strong',
                    false,
                    'strong',
                    banner(4, 10, 106, 120, {
                        image: true,
                        scrim: 0.7,
                        compact: true
                    }) + t(57, 156, 'Text stands out most', 'x8 k2', 'm')
                ]
            ])
        };
    },
    playerlogo: () => {
        return {
            cap: 'Your logo at the top of the course index, above the course title and progress ring.',
            svg:
                page({
                    top: 16,
                    index: {
                        logo: true,
                        mark: 'logo'
                    }
                }) + co(140, 24, 'Your logo here', 88, 31)
        };
    },
    indexheadingcolour: () => {
        return {
            cap: 'The section heading bands in the course index, drawn in the chosen colour.',
            svg:
                page({
                    index: {
                        heading: true
                    }
                }) +
                hl(1, 70, 116, 22, 2) +
                hl(1, 132, 116, 22, 2) +
                co(132, 190, 'Section headings', 112, 143)
        };
    },
    indexiconcolour: () => {
        return {
            cap: 'The activity icons in the course index, drawn in the chosen colour.',
            svg:
                page({
                    index: {
                        icons: true
                    }
                }) +
                hl(4, 92, 18, 16, 3) +
                hl(4, 154, 18, 56, 3) +
                co(132, 190, 'Activity icons', 22, 160)
        };
    },
    indexcolour: () => {
        return {
            cap: 'The course index background tinted with the chosen colour.',
            svg:
                page({
                    index: {
                        bg: true,
                        op: 0.22
                    }
                }) +
                hl(1, 21, 116, 192, 2) +
                co(132, 190, 'Index background', 112, 170)
        };
    },
    indexopacity: () => {
        return {
            cap: 'The course index colour at a weak strength on the left and a strong one on the right.',
            svg: two(
                [
                    'Weak',
                    false,
                    '',
                    index(0, 0, 172, 195, {
                        bg: true,
                        op: 0.1
                    })
                ],
                [
                    'Strong',
                    true,
                    '',
                    index(0, 0, 172, 195, {
                        bg: true,
                        op: 0.45
                    })
                ]
            )
        };
    },
    cardcolour: () => {
        return {
            cap: 'The background of each section card, in the chosen colour.',
            svg:
                page({
                    card: {
                        body: true,
                        bodyop: 0.3,
                        mark: 'body'
                    }
                }) + co(140, 196, 'Card background', 180, 176)
        };
    },
    cardopacity: () => {
        return {
            cap: 'The card background colour at a weak strength on the left and a strong one on the right.',
            svg: two(
                [
                    'Weak',
                    false,
                    '',
                    card(10, 8, 152, {
                        body: true,
                        bodyop: 0.12
                    })
                ],
                [
                    'Strong',
                    true,
                    '',
                    card(10, 8, 152, {
                        body: true,
                        bodyop: 0.45,
                        mark: 'body'
                    })
                ]
            )
        };
    },
    playerheadercolour: () => {
        return {
            cap: 'The band at the top of the course index, with the course title and ring, in the chosen colour.',
            svg:
                page({
                    top: 16,
                    index: {
                        head: true,
                        mark: 'header'
                    }
                }) + co(140, 26, 'Index header band', 116, 46)
        };
    },
    colourmode: () => {
        return {
            cap: 'The same course page in light mode on the left and dark mode on the right.',
            svg: two(
                [
                    'Light',
                    false,
                    'light',
                    g(
                        index(0, 0, 70, 195, {
                            time: false
                        }) +
                            banner(76, 6, 94, 46, {
                                compact: true
                            }) +
                            card(76, 58, 94, {
                                cta: false,
                                small: true
                            }),
                        0,
                        0,
                        'pv-light'
                    )
                ],
                [
                    'Dark',
                    true,
                    'dark',
                    g(
                        index(0, 0, 70, 195, {
                            time: false
                        }) +
                            banner(76, 6, 94, 46, {
                                compact: true
                            }) +
                            card(76, 58, 94, {
                                cta: false,
                                small: true
                            }),
                        0,
                        0,
                        'pv-dark'
                    )
                ]
            )
        };
    },
    fontfamily: () => {
        return {
            cap: 'A sample of the course page text in the chosen font.',
            svg:
                r(0, 0, W, H, 'p-page') +
                r(14, 14, 120, 120, 'p-card', 12) +
                '<text class="x pv-font x-aa" x="74" y="96" text-anchor="middle">Aa</text>' +
                '<text class="x pv-font x13 b" x="150" y="40">Leading Safe Workplaces</text>' +
                '<text class="x pv-font x10 k2" x="150" y="58">3 modules · 5 activities · 1 hr 10 min</text>' +
                '<text class="x pv-font x11 b" x="150" y="86">Spotting hazards</text>' +
                '<text class="x pv-font x9 k2" x="150" y="102">Find what could hurt someone,</text>' +
                '<text class="x pv-font x9 k2" x="150" y="114">then decide what to do about it.</text>' +
                r(150, 122, 70, 17, 'p-brandsoft', 5) +
                '<text class="x pv-font x9 b kb" x="185" y="134" text-anchor="middle">' +
                'Continue →</text>' +
                '<text class="x pv-fontname x10 b kb" x="14" y="160"></text>' +
                '<text class="x pv-fontnote x8 k3" x="14" y="176"></text>' +
                t(14, 200, 'ABCDEFGHIJKLM abcdefghijklm 0123456789', 'x9 k2 pv-font')
        };
    },
    showherobanner: () => {
        return {
            cap: 'The course page without the banner on the left and with it on the right.',
            svg: two(
                [
                    'Off',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        crumbs(6, 32, 2) +
                        t(6, 50, 'WHS for Supervisors', 'x11 b') +
                        card(6, 60, 160, {
                            cta: false
                        })
                ],
                [
                    'On',
                    true,
                    '1',
                    navbar(0, 0, 172) +
                        banner(6, 26, 160, 62) +
                        hl(5, 25, 162, 64, 2) +
                        card(6, 96, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    herosticky: () => {
        return {
            cap: 'After scrolling down: on the left the banner has scrolled away, on the right it stays pinned at the top.',
            svg: two(
                [
                    'Scrolls away',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        card(6, 26, 160, {
                            list: true,
                            limit: 3,
                            title: 'Consultation',
                            eyebrow: 'Module 3'
                        }) +
                        l(166, 40, 166, 140, 'p-scroll')
                ],
                [
                    'Stays at top',
                    true,
                    '1',
                    navbar(0, 0, 172) +
                        card(6, 70, 160, {
                            list: true,
                            limit: 3,
                            title: 'Consultation',
                            eyebrow: 'Module 3'
                        }) +
                        r(0, 20, 172, 58, 'p-shadow') +
                        banner(0, 20, 172, 58, {
                            compact: true
                        }) +
                        hl(1, 21, 170, 56, 2)
                ]
            )
        };
    },
    heroattop: () => {
        return {
            cap: 'The banner under the course tabs on the left, and moved above the tabs on the right.',
            svg: two(
                [
                    'Below the tabs',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        t(6, 36, 'WHS for Supervisors', 'x11 b') +
                        tabs(6, 42, 160) +
                        banner(6, 66, 160, 56, {
                            compact: true
                        }) +
                        card(6, 130, 160, {
                            cta: false
                        })
                ],
                [
                    'Above the tabs',
                    true,
                    '1',
                    navbar(0, 0, 172) +
                        banner(6, 26, 160, 56, {
                            compact: true
                        }) +
                        hl(5, 25, 162, 58, 2) +
                        tabs(6, 88, 160) +
                        card(6, 112, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    displayascards: () => {
        return {
            cap: "The course home as Moodle's long page of sections on the left, and as section cards on the right.",
            svg: two(
                ['Off: long page', false, '0', stdlist(6, 8, 160)],
                [
                    'On: cards',
                    true,
                    '1',
                    card(6, 8, 77, {
                        small: true,
                        cta: false,
                        title: 'Duty of care',
                        eyebrow: 'Module 1'
                    }) +
                        card(89, 8, 77, {
                            small: true,
                            cta: false,
                            title: 'Hazards'
                        }) +
                        card(6, 112, 77, {
                            small: true,
                            cta: false,
                            title: 'Consultation',
                            eyebrow: 'Module 3'
                        })
                ]
            )
        };
    },
    cardlayout: () => {
        return {
            cap: 'Section cards side by side in a grid on the left, and one per row in a list on the right.',
            svg: two(
                [
                    'Grid',
                    false,
                    '0',
                    card(6, 8, 77, {
                        small: true,
                        cta: false,
                        title: 'Duty of care',
                        eyebrow: 'Module 1'
                    }) +
                        card(89, 8, 77, {
                            small: true,
                            cta: false,
                            title: 'Hazards'
                        }) +
                        card(6, 112, 77, {
                            small: true,
                            cta: false,
                            title: 'Consultation',
                            eyebrow: 'Module 3'
                        })
                ],
                [
                    'List',
                    true,
                    '1',
                    card(6, 8, 160, {
                        mh: 34,
                        cta: false,
                        title: 'Your duty of care',
                        eyebrow: 'Module 1'
                    }) +
                        card(6, 98, 160, {
                            mh: 34,
                            cta: false
                        })
                ]
            )
        };
    },
    showactivitiesoncards: () => {
        return {
            cap: 'A section card without its activity list on the left, and listing its activities with ticks on the right.',
            svg: two(
                ['Off', false, '0', card(10, 8, 152, {})],
                [
                    'On',
                    true,
                    '1',
                    card(10, 8, 152, {
                        list: true,
                        limit: 4,
                        mark: 'list'
                    })
                ]
            )
        };
    },
    cardactivitylimit: () => {
        return {
            cap: 'A section card listing its first three activities, then how many more there are.',
            svg:
                r(0, 0, W, H, 'p-page') +
                card(10, 6, 196, {
                    list: true,
                    limit: 3,
                    mark: 'list',
                    cta: false
                }) +
                co(214, 60, 'Shows 3, then “+ 2 more”', 200, 96)
        };
    },
    cardtitlesize: () => {
        return {
            cap: 'The section card title at a small size on the left and a large size on the right.',
            svg: two(
                [
                    'Small',
                    false,
                    '',
                    card(10, 8, 152, {
                        small: true,
                        mark: 'title'
                    })
                ],
                [
                    'Large',
                    true,
                    '',
                    card(10, 8, 152, {
                        big: true,
                        mark: 'title'
                    })
                ]
            )
        };
    },
    cardimagestyle: () => {
        return {
            cap: 'The four looks for AI card images: photographic, illustration, 3D render and flat illustration.',
            svg:
                r(0, 0, W, H, 'p-page') +
                ['photo', 'illustration', 'render3d', 'flat']
                    .map((st, i) => {
                        const x = 8 + i * 88;
                        const label = ['Photographic', 'Illustration', '3D render', 'Flat'][i];
                        return (
                            '<g class="pv-panel" data-v="' +
                            st +
                            '">' +
                            r(x - 2, 30, 84, 120, 'pv-cur', 8) +
                            thumb(x + 2, 34, 76, 76, st) +
                            t(x + 40, 126, label, 'x9 b', 'm') +
                            t(x + 40, 140, '', 'x8 b kb pv-curlabel', 'm') +
                            '</g>'
                        );
                    })
                    .join('') +
                t(8, 18, 'Generated card images', 'x10 b')
        };
    },
    activitydisplaymode: () => {
        return {
            cap: "A section page as Moodle's standard activity list on the left, and as activity tiles on the right.",
            svg: two(
                ['Standard list', false, '0', stdlist(6, 8, 160)],
                [
                    'Activity tiles',
                    true,
                    '1',
                    t(6, 19, 'Spotting hazards', 'x11 b') +
                        tile(6, 28, 78, {}) +
                        tile(88, 28, 78, {
                            title: 'Risk assessment',
                            "short": 'Assessment',
                            icon: 'assign',
                            kind: 'Assign'
                        }) +
                        tile(6, 104, 78, {
                            title: 'Hazard quiz',
                            "short": 'Hazard quiz',
                            icon: 'quiz',
                            kind: 'Quiz'
                        })
                ]
            )
        };
    },
    shownavchevrons: () => {
        return {
            cap: 'An activity page banner without, then with, the previous and next activity arrows.',
            svg: two(
                [
                    'Off',
                    false,
                    '0',
                    banner(6, 10, 160, 56, {
                        compact: true,
                        title: 'Hierarchy of control'
                    }) +
                        t(6, 88, 'Eliminate, substitute, isolate,', 'x9 k2') +
                        t(6, 101, 'engineer, administrate, PPE.', 'x9 k2')
                ],
                [
                    'On',
                    true,
                    '1',
                    banner(6, 10, 160, 56, {
                        compact: true,
                        chevrons: true,
                        title: 'Hierarchy of control'
                    }) +
                        hl(72, 44, 40, 21, 10) +
                        co(70, 74, 'Previous / next', 92, 65) +
                        t(6, 110, 'Eliminate, substitute, isolate,', 'x9 k2') +
                        t(6, 123, 'engineer, administrate, PPE.', 'x9 k2')
                ]
            )
        };
    },
    hidegeneral: () => {
        return {
            cap: 'The course index with the General section on the left, and without it on the right.',
            svg: two(
                ['Show', false, '0', index(0, 0, 172, 195, {}) + hl(1, 51, 170, 21, 2)],
                [
                    'Hide',
                    true,
                    '1 2',
                    index(0, 0, 172, 195, {
                        nogeneral: true
                    })
                ]
            )
        };
    },
    indexstate: () => {
        return {
            cap: 'A course opened with the index collapsed to a button on the left, and open beside the page on the right.',
            svg: two(
                [
                    'Start collapsed',
                    false,
                    '1',
                    navbar(0, 0, 172) +
                        r(4, 26, 18, 18, 'p-btn', 5) +
                        i('list', 7, 29, 12, 'ic-b') +
                        hl(2, 24, 22, 22, 6) +
                        banner(28, 26, 140, 52, {
                            compact: true
                        }) +
                        card(28, 86, 140, {
                            cta: false
                        })
                ],
                [
                    'Start open',
                    true,
                    '2',
                    navbar(0, 0, 172) +
                        index(0, 20, 82, 175, {
                            time: false
                        }) +
                        hl(1, 21, 80, 173, 2) +
                        banner(88, 26, 80, 52, {
                            compact: true
                        }) +
                        card(88, 86, 80, {
                            cta: false,
                            small: true
                        })
                ]
            )
        };
    },
    playerindex: () => {
        return {
            cap: "Moodle's plain course index on the left, and the player sidebar with progress, times and ticks on the right.",
            svg: two(
                [
                    'Plain course index',
                    false,
                    '0',
                    index(0, 0, 172, 195, {
                        plain: true
                    })
                ],
                ['Player sidebar', true, '1', index(0, 0, 172, 195, {})]
            )
        };
    },
    showcourseindex: () => {
        return {
            cap: 'The course index beside the course page; you choose on which pages it appears: '
                + 'course home, sections, activities.',
            svg: page({}) + hl(1, 21, 116, 192, 2) + co(130, 186, 'Course home · Sections · Activities', 112, 150)
        };
    },
    hidetimeindex: () => {
        return {
            cap: 'Course index rows with their estimated time chips on the left, and without them on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    index(0, 0, 172, 195, {
                        mark: 'time'
                    })
                ],
                [
                    'Hide',
                    true,
                    '1',
                    index(0, 0, 172, 195, {
                        time: false
                    })
                ]
            )
        };
    },
    hidetimetotal: () => {
        return {
            cap: 'The total course time under the course title in the index, shown on the left and hidden on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    index(0, 0, 172, 195, {
                        mark: 'total'
                    })
                ],
                [
                    'Hide',
                    true,
                    '1',
                    index(0, 0, 172, 195, {
                        total: false
                    })
                ]
            )
        };
    },
    hidetimesectioncards: () => {
        return {
            cap: 'A section card with its total time chip on the left, and without it on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    card(10, 8, 152, {
                        mark: 'time'
                    })
                ],
                [
                    'Hide',
                    true,
                    '1',
                    card(10, 8, 152, {
                        time: false
                    })
                ]
            )
        };
    },
    hidetimeactivitycards: () => {
        return {
            cap: 'An activity tile with its time chip on the left, and without it on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    tile(16, 20, 140, {
                        mark: 'time'
                    })
                ],
                [
                    'Hide',
                    true,
                    '1',
                    tile(16, 20, 140, {
                        time: false
                    })
                ]
            )
        };
    },
    minutes: () => {
        {
            let s = r(0, 0, W, H, 'p-page') + t(8, 18, 'Assumed time per activity type', 'x10 b');
            [
                ['page', 'Page', '10 min'],
                ['forum', 'Forum', '10 min'],
                ['assign', 'Assignment', '30 min'],
                ['book', 'Book', '15 min'],
                ['image', 'H5P', '15 min']
            ].forEach((r, i) => {
                const y = 30 + i * 21;
                s +=
                    r(8, y, 150, 18, 'p-card', 4) +
                    i(r[0], 13, y + 3, 12, 'ic-b') +
                    t(30, y + 12.5, r[1], 'x9') +
                    r(110, y + 3, 42, 12, 'p-chip', 3) +
                    i('clock', 113, y + 4.5, 9, 'ic-b') +
                    t(124, y + 12, r[2], 'x8 k2');
            });
            s += hl(108, 31, 46, 104, 4) + co(176, 40, 'Shown as time chips', 154, 50);
            return {
                cap: 'Each activity type with its assumed time, and where those times appear in the course index.',
                svg:
                    s +
                    index(176, 58, 176, 150, {
                        mark: 'time'
                    })
            };
        }
    },
    minutesperquestion: () => {
        return {
            cap: 'A 12 question quiz at 2 minutes a question gives a 24 minute time chip.',
            svg:
                r(0, 0, W, H, 'p-page') +
                r(8, 20, 156, 70, 'p-card', 8) +
                i('quiz', 16, 28, 16, 'ic-b') +
                t(38, 40, 'Hazard quiz', 'x11 b') +
                t(16, 60, '12 questions × 2 min', 'x10') +
                t(16, 78, '= 24 min', 'x11 b kb') +
                hl(13, 66, 60, 16, 4) +
                l(170, 55, 192, 55, 'p-arrow') +
                '<path class="p-arrowh" d="M188 51l6 4-6 4"/>' +
                index(200, 10, 152, 196, {}) +
                t(32, 120, 'Questions are counted when', 'x9 k2') +
                t(32, 133, 'the time is worked out.', 'x9 k2')
        };
    },
    minutesfallback: () => {
        return {
            cap: 'An activity type with no time of its own, shown with the fallback time.',
            svg:
                r(0, 0, W, H, 'p-page') +
                t(8, 18, 'Anything not listed', 'x10 b') +
                r(8, 30, 168, 20, 'p-card', 4) +
                i('image', 14, 34, 12, 'ic-b') +
                t(31, 43.5, 'Lesson (no time set)', 'x9') +
                r(130, 34, 40, 12, 'p-chip', 3) +
                t(150, 43, '20 min', 'x8 k2', 'm') +
                hl(127, 32, 46, 16, 4) +
                co(30, 64, 'Uses the fallback time', 140, 48) +
                tile(8, 96, 168, {
                    title: 'Lesson',
                    icon: 'image',
                    kind: 'Lesson',
                    mark: 'time',
                    timelabel: '20 min'
                }) +
                index(188, 10, 164, 196, {})
        };
    },
    hidesecondarynav: () => {
        return {
            cap: 'The course tabs (Course, Settings, Participants, Grades, Reports) shown on the left and hidden on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        t(6, 36, 'WHS for Supervisors', 'x11 b') +
                        tabs(6, 44, 160) +
                        hl(4, 42, 164, 20, 3) +
                        banner(6, 70, 160, 50, {
                            compact: true
                        }) +
                        card(6, 128, 160, {
                            cta: false
                        })
                ],
                [
                    'Hide',
                    true,
                    '1 2',
                    navbar(0, 0, 172) +
                        t(6, 36, 'WHS for Supervisors', 'x11 b') +
                        banner(6, 44, 160, 50, {
                            compact: true
                        }) +
                        card(6, 102, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    coursenavplace: () => {
        return {
            cap: 'The course tabs under the page title on the left, and moved into the site header beside Home on the right.',
            svg: two(
                [
                    'Below the banner',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        banner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        tabs(6, 82, 160) +
                        hl(4, 80, 164, 20, 3) +
                        card(6, 106, 160, {
                            cta: false
                        })
                ],
                [
                    'In the site header',
                    true,
                    '1',
                    navbar(0, 0, 172, {
                        tabs: true
                    }) +
                        hl(58, 2, 112, 16, 3) +
                        banner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        card(6, 84, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    immersive: () => {
        return {
            cap: 'The site logo band above the navbar, shown on the left and hidden on the right so the course starts higher.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    logoband(0, 0, 172) +
                        hl(1, 1, 170, 24, 2) +
                        navbar(0, 26, 172) +
                        banner(6, 52, 160, 50, {
                            compact: true
                        }) +
                        card(6, 110, 160, {
                            cta: false
                        })
                ],
                [
                    'Hide',
                    true,
                    '1 2',
                    navbar(0, 0, 172) +
                        banner(6, 26, 160, 50, {
                            compact: true
                        }) +
                        card(6, 84, 160, {
                            cta: false
                        })
                ]
            )
        };
    },
    hidefooter: () => {
        return {
            cap: 'The site footer with its links at the bottom of a course page, shown on the left and hidden on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    card(6, 6, 160, {
                        cta: false
                    }) +
                        footer(0, 150, 172) +
                        hl(1, 151, 170, 38, 2)
                ],
                [
                    'Hide',
                    true,
                    '1 2',
                    card(6, 6, 160, {
                        cta: true
                    })
                ]
            )
        };
    },
    hidebreadcrumb: () => {
        return {
            cap:
                'The breadcrumb trail, Dashboard \u203A My courses \u203A WHS101 \u203A' +
                ' Spotting hazards, shown on the left and hidden on the right.',
            svg: two(
                [
                    'Show',
                    false,
                    '0',
                    navbar(0, 0, 172) +
                        crumbs(6, 36, 3) +
                        t(6, 36 + 13, '› Spotting hazards', 'x9 k2') +
                        hl(3, 26, 166, 28, 3) +
                        t(6, 72, 'Spotting hazards', 'x13 b') +
                        banner(6, 82, 160, 48, {
                            compact: true
                        })
                ],
                [
                    'Hide',
                    true,
                    '1 2',
                    navbar(0, 0, 172) +
                        t(6, 44, 'Spotting hazards', 'x13 b') +
                        banner(6, 54, 160, 48, {
                            compact: true
                        }) +
                        card(6, 110, 160, {
                            cta: false,
                            mh: 30
                        })
                ]
            )
        };
    },
    enabletutor: () => {
        return {
            cap: 'The Ask Dari panel: a greeting, a learner question, an answer, and three study tools.',
            svg:
                r(0, 0, W, H, 'p-page') +
                banner(6, 8, 150, 60, {
                    compact: true
                }) +
                hl(54, 32, 20, 20, 4) +
                co(18, 82, 'Opens Ask Dari', 64, 52) +
                chat(170, 4, 184, 206, {})
        };
    },
    sendfirstname: () => {
        return {
            cap: 'Ask Dari greeting a learner without their name on the left, and by first name on the right.',
            svg: two(
                [
                    'Off',
                    false,
                    '0',
                    chat(4, 4, 164, 186, {
                        name: false,
                        tools: true,
                        mark: 'name'
                    })
                ],
                [
                    'On',
                    true,
                    '1',
                    chat(4, 4, 164, 186, {
                        mark: 'name'
                    })
                ]
            )
        };
    },
    supportcontacts: () => {
        return {
            cap: 'Ask Dari answering a learner who is struggling with your wellbeing contacts.',
            svg:
                r(0, 0, W, H, 'p-page') +
                chat(70, 4, 220, 206, {
                    tools: false,
                    question: 'I feel really stressed',
                    answer: [
                        'That sounds hard. You can talk to:',
                        'Student wellbeing · ext 2040',
                        'Lifeline · 13 11 14'
                    ],
                    mark: 'answer'
                }) +
                co(4, 150, 'Your contacts', 92, 120)
        };
    },
    maxcontextchars: () => {
        return {
            cap: "Dari sends the question and course content, up to your limit, to your site's AI provider.",
            svg:
                r(0, 0, W, H, 'p-page') +
                flow({
                    direct: !!document.getElementById('id_s_format_dari_directendpoint'),
                    mark: 'content'
                })
        };
    },
    shareassessmentanswers: () => {
        return {
            cap: "Ask Dari explaining a quiz result without the learner's answers on the left, and using them on the right.",
            svg: two(
                [
                    'Never share',
                    false,
                    '0',
                    chat(4, 4, 164, 186, {
                        tools: false,
                        question: 'Why did I get Q3 wrong?',
                        answer: [
                            "I can't see your answers. Tell me",
                            "what you chose and we'll work",
                            'through it together.'
                        ]
                    })
                ],
                [
                    'Share',
                    true,
                    '1',
                    chat(4, 4, 164, 186, {
                        tools: false,
                        question: 'Why did I get Q3 wrong?',
                        answer: [
                            'You chose B (PPE first). The',
                            'hierarchy starts with removing',
                            'the hazard: A, elimination.'
                        ],
                        mark: 'answer'
                    })
                ]
            )
        };
    },
    imagequality: () => {
        return {
            cap: 'An AI image at standard quality on the left and high definition on the right.',
            svg: two(
                [
                    'Standard',
                    false,
                    'standard',
                    thumb(8, 8, 156, 120, 'standard') + t(86, 150, 'Faster, cheaper', 'x9 k2', 'm')
                ],
                [
                    'High definition',
                    true,
                    'hd',
                    thumb(8, 8, 156, 120, 'hd') + t(86, 150, 'Sharper detail', 'x9 k2', 'm')
                ]
            )
        };
    },
    imagestyle: () => {
        return {
            cap: 'An AI image rendered natural on the left and vivid on the right.',
            svg: two(
                [
                    'Natural',
                    false,
                    'natural',
                    thumb(8, 8, 156, 120, 'natural') + t(86, 150, 'True-to-life colour', 'x9 k2', 'm')
                ],
                [
                    'Vivid',
                    true,
                    'vivid',
                    thumb(8, 8, 156, 120, 'vivid') + t(86, 150, 'Bolder, more dramatic', 'x9 k2', 'm')
                ]
            )
        };
    },
    aiscenewriter: () => {
        return {
            cap: 'The art director turns the course name into a detailed scene before the image is made.',
            svg:
                r(0, 0, W, H, 'p-page') +
                r(4, 30, 106, 50, 'p-card', 8) +
                t(11, 48, 'Course name', 'x8 k2') +
                t(11, 64, 'WHS for Supervisors', 'x9 b') +
                l(112, 55, 124, 55, 'p-arrow') +
                '<path class="p-arrowh" d="M120 51l6 4-6 4"/>' +
                r(130, 22, 110, 70, 'p-brandsoft', 8) +
                i('spark', 138, 30, 14, 'ic-bf') +
                t(156, 41, 'Art director', 'x9 b kb') +
                t(138, 56, '“A supervisor and', 'x8') +
                t(138, 67, 'worker reviewing a', 'x8') +
                t(138, 78, 'site plan at dawn…”', 'x8') +
                hl(128, 20, 114, 74, 9) +
                l(244, 55, 262, 55, 'p-arrow') +
                '<path class="p-arrowh" d="M258 51l6 4-6 4"/>' +
                thumb(268, 22, 84, 70, 'photo') +
                t(310, 108, 'Card image', 'x8 k2', 'm') +
                t(8, 140, 'Without it, the course name goes to the image model', 'x9 k2') +
                t(8, 153, 'as it is, and pictures come out more generic.', 'x9 k2')
        };
    },
    directendpoint: () => {
        return {
            cap: 'Moodle 4.4: Dari sends requests straight to your OpenAI-compatible endpoint.',
            svg:
                r(0, 0, W, H, 'p-page') +
                flow({
                    direct: true,
                    mark: 'endpoint'
                })
        };
    },
    directapikey: () => {
        return {
            cap: 'Moodle 4.4: the API key Dari uses with your endpoint.',
            svg:
                r(0, 0, W, H, 'p-page') +
                flow({
                    direct: true,
                    mark: 'secret'
                })
        };
    },
    directtextmodel: () => {
        return {
            cap: "Moodle 4.4: the model that writes Ask Dari's answers.",
            svg:
                r(0, 0, W, H, 'p-page') +
                flow({
                    direct: true,
                    mark: 'textmodel'
                })
        };
    },
    directimagemodel: () => {
        return {
            cap: 'Moodle 4.4: the model that draws banners and card images.',
            svg:
                r(0, 0, W, H, 'p-page') +
                flow({
                    direct: true,
                    mark: 'imagemodel'
                })
        };
    },
    tourvoiceover: () => {
        return {
            cap: 'The tour step with narration muted on the left and read aloud on the right.',
            svg: two(
                [
                    'Off',
                    false,
                    '0',
                    r(0, 0, 172, 195, 'p-dim') +
                        tour(8, 30, 156, {
                            voice: false,
                            mark: 'voice'
                        })
                ],
                [
                    'On',
                    true,
                    '1',
                    r(0, 0, 172, 195, 'p-dim') +
                        tour(8, 30, 156, {
                            voice: true,
                            mark: 'voice'
                        })
                ]
            )
        };
    },
    tourvoice: () => {
        return {
            cap: 'A tour step read aloud in the chosen language.',
            svg:
                r(0, 0, W, H, 'p-page') +
                banner(8, 8, 344, 52) +
                r(7, 7, 346, 54, 'pv-spot', 2) +
                tour(96, 72, 200, {
                    voice: true,
                    lang: 'Narration: English (Australia)'
                }) +
                hl(104, 183, 160, 19, 8) +
                co(276, 188, 'Voice language', 264, 192)
        };
    }
};
/**
 * The drawing and caption for one setting.
 *
 * @param {String} base The setting name without its default/force prefix.
 * @returns {Object|null} {svg, cap}
 */
const draw = (base) => {
    const drawing = drawings[base];
    if (Object.prototype.hasOwnProperty.call(drawings, base) && typeof drawing === 'function') {
        return drawing();
    }
    return null;
};

/**
 * The colour a colour setting has now, or '' when it has none.
 *
 * @param {Element} item The setting row.
 * @returns {String}
 */
const colourOf = (item) => {
    const input = item.querySelector('input[type="text"], input[type="color"]');
    const v = input ? input.value.trim() : '';
    return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v) ? v : '';
};

/**
 * Relative luminance of a hex colour, 0 (black) to 1 (white).
 *
 * @param {String} hex #rgb or #rrggbb.
 * @returns {Number}
 */
const luminance = (hex) => {
    let h = hex.replace('#', '');
    if (h.length === 3) {
        h = h
            .split('')
            .map((c) => c + c)
            .join('');
    }
    const ch = [0, 2, 4].map((i) => {
        const v = parseInt(h.substr(i, 2), 16) / 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * ch[0] + 0.7152 * ch[1] + 0.0722 * ch[2];
};

/**
 * Build the preview for one setting, and keep it in step with the setting's control.
 *
 * @param {String} name The setting's own name.
 * @param {Element} item The setting row.
 * @param {Object} t Localised labels {appliestoall, allcourses, current}.
 * @returns {Array} [figure, caption] elements, or [] when the setting has no drawing.
 */
export const buildSettingPreview = (name, item, t) => {
    const isforce = /^force/.test(name);
    const base = name.replace(/^(default|force)/, '');
    const d = draw(base);
    if (!d) {
        return [];
    }
    const fig = document.createElement('figure');
    fig.className = 'dset-fig' + (isforce ? ' dset-fig--all' : '');
    fig.setAttribute('aria-hidden', 'true');
    let svg;
    if (isforce) {
        svg =
            '<svg class="dset-svg" viewBox="0 0 ' +
            (W + 16) +
            ' ' +
            (H + 18) +
            '" focusable="false" role="presentation">' +
            r(16.5, 0.5, W - 1, H - 1, 'pv-sheet back2', 8) +
            r(8.5, 6.5, W - 1, H - 1, 'pv-sheet back1', 8) +
            '<svg x="0" y="18" width="' +
            W +
            '" height="' +
            H +
            '" viewBox="0 0 ' +
            W +
            ' ' +
            H +
            '" overflow="hidden">' +
            r(0, 0, W, H, 'p-page', 8) +
            d.svg +
            '</svg>' +
            r(0.5, 18.5, W - 1, H - 1, 'pv-sheet-edge', 8) +
            r(W - 70, 0, 70, 15, 'co-bg', 7.5) +
            t(W - 35, 10.5, t.allcourses || 'All courses', 'x9 b kw', 'm') +
            '</svg>';
    } else {
        svg =
            '<svg class="dset-svg" viewBox="0 0 ' +
            W +
            ' ' +
            H +
            '" focusable="false" role="presentation">' +
            '<svg width="' +
            W +
            '" height="' +
            H +
            '" overflow="hidden">' +
            d.svg +
            '</svg>' +
            r(0.5, 0.5, W - 1, H - 1, 'pv-sheet-edge', 8) +
            '</svg>';
    }
    fig.innerHTML = svg;
    if (isforce) {
        const cap = document.createElement('figcaption');
        cap.className = 'dset-figcap';
        cap.textContent = t.appliestoall;
        fig.appendChild(cap);
    }
    const sr = document.createElement('p');
    sr.className = 'dset-figsr visually-hidden sr-only';
    sr.textContent = (isforce ? t.appliestoall + '. ' : '') + d.cap;

    // Keep the drawing in step with the control.
    const control = item.querySelector('select, input[type="checkbox"], input[type="text"], input[type="color"]');
    const sync = () => {
        if (!control) {
            return;
        }
        let value = control.value;
        if (control.type === 'checkbox') {
            value = (() => {
                if (control.checked) {
                    return '1';
                }
                return '0';
            })();
        }
        // Colour settings: paint the drawing in the chosen colour.
        const colour = colourOf(item);
        if (colour) {
            fig.style.setProperty('--pv-c', colour);
        } else {
            fig.style.removeProperty('--pv-c');
        }
        // A near-white choice would show nothing; draw the primary as the sample instead.
        if (colour && luminance(colour) > 0.85) {
            fig.style.removeProperty('--pv-c');
        }
        fig.classList.toggle('pv-c-light', !!colour && luminance(colour) > 0.6 && luminance(colour) <= 0.85);
        // Panels: mark the one matching the current value.
        fig.querySelectorAll('.pv-panel').forEach((p) => {
            const on = (p.getAttribute('data-v') || '').split(' ').indexOf(String(value)) !== -1;
            p.classList.toggle('is-current', on);
            const lab = p.querySelector('.pv-curlabel');
            if (lab) {
                lab.textContent = (() => {
                    if (on) {
                        return t.current || 'Current';
                    }
                    return '';
                })();
            }
        });
        // The font.
        if (base === 'fontfamily' && control.tagName === 'SELECT') {
            const opt = control.options[control.selectedIndex];
            const label = (() => {
                if (opt) {
                    return opt.textContent.trim();
                }
                return '';
            })();
            const family = label.replace(/\s*\(.*\)\s*$/, '').trim();
            const theme = value === 'theme';
            let note = '';
            let use = '';
            if (theme) {
                note = "Uses the theme's own font.";
            } else if (
                document.fonts &&
                document.fonts.check &&
                document.fonts.check('12px "' + family + '"') &&
                (family === 'DM Sans' || Array.from(document.fonts).some((f) => f.family.replace(/"/g, '') === family))
            ) {
                use = '"' + family + '", ';
                note = 'Shown in ' + family + '.';
            } else {
                note = 'Sample in a similar font; courses load ' + family + ' from Google Fonts.';
            }
            fig.style.setProperty(
                '--pv-font',
                use + 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'
            );
            const n = fig.querySelector('.pv-fontname');
            const m = fig.querySelector('.pv-fontnote');
            if (n) {
                n.textContent = (() => {
                    if (theme) {
                        return 'Follow the theme';
                    }
                    return family;
                })();
            }
            if (m) {
                m.textContent = note;
            }
        }
    };
    if (control) {
        control.addEventListener('change', sync);
        control.addEventListener('input', sync);
    }
    // Moodle's colour picker writes the text input without an input event; watch it.
    if (/colour/.test(base)) {
        item.addEventListener('click', () => window.setTimeout(sync, 50));
        item.addEventListener('keyup', sync);
    }
    sync();
    return [fig, sr];
};
