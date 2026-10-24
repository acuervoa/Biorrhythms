/*
 * Saved profile (birth date, chronotype result, wake-up time, notification opt-in) in localStorage, plus the
 * URL rules used on page load. Pure functions are exported for tests (node --test).
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.BiorrhythmsProfile = api;
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const STORAGE_KEY = 'biorrhythms-profile-v1';
    const MIN = '1900-01-01';
    const MAX = '2100-12-31';
    const ISO = /^\d{4}-\d{2}-\d{2}$/;
    const CHRONO_KEYS = ['evening', 'intermediate', 'morning'];
    const HHMM = /^([01]\d|2[0-3]):[0-5]\d$/;

    function validIso(value) {
        if (typeof value !== 'string' || !ISO.test(value) || value < MIN || value > MAX) return false;
        const d = new Date(value + 'T00:00:00Z');
        return !Number.isNaN(d.getTime()) && d.toISOString().slice(0, 10) === value;
    }

    function sanitize(raw) {
        const out = { birth: null, chronotype: null, wake: null, notify: false, notified: [] };
        if (!raw || typeof raw !== 'object') return out;

        if (validIso(raw.birth)) out.birth = raw.birth;
        out.notify = raw.notify === true;

        const c = raw.chronotype;
        if (c && typeof c === 'object' && Number.isInteger(c.score) && c.score >= 4 && c.score <= 25 && CHRONO_KEYS.includes(c.key)) {
            out.chronotype = { score: c.score, key: c.key };
        }
        if (typeof raw.wake === 'string' && HHMM.test(raw.wake)) out.wake = raw.wake;
        if (Array.isArray(raw.notified)) out.notified = raw.notified.filter(validIso).slice(-10);
        return out;
    }

    function load(storage) {
        try {
            return sanitize(JSON.parse(storage.getItem(STORAGE_KEY) || 'null'));
        } catch (e) {
            return sanitize(null);
        }
    }

    /** Merges $patch into the stored profile; invalid fields are dropped by sanitize(). */
    function save(storage, patch) {
        try {
            storage.setItem(STORAGE_KEY, JSON.stringify(sanitize({ ...load(storage), ...patch })));
            return true;
        } catch (e) {
            return false;
        }
    }

    function clear(storage) {
        try {
            storage.removeItem(STORAGE_KEY);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * URL to load instead of $href, or null when it already has what it needs.
     * Fills a missing birth from the saved profile and a missing focus with the visitor's local day
     * (only when that differs from the server's day, to avoid a pointless redirect).
     */
    function resolveUrl(href, profile, localToday, serverToday) {
        const url = new URL(href);
        let changed = false;

        const birth = url.searchParams.get('birth');
        if ((birth === null || birth === '') && profile && profile.birth) {
            url.searchParams.set('birth', profile.birth);
            changed = true;
        }

        const focus = url.searchParams.get('focus');
        if ((focus === null || focus === '') && validIso(localToday) && localToday !== serverToday) {
            url.searchParams.set('focus', localToday);
            changed = true;
        }

        return changed ? url.toString() : null;
    }

    /** Saves the birth date found in the URL, if it is a real date. Returns true when saved. */
    function rememberBirth(storage, href) {
        const birth = new URL(href).searchParams.get('birth');
        return validIso(birth) ? save(storage, { birth }) : false;
    }

    function localDay(date) {
        const d = date || new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    return { STORAGE_KEY, validIso, sanitize, load, save, clear, resolveUrl, rememberBirth, localDay };
});
