/*
 * Self-observation diary: stores energy / mood / focus (1-5) per day in localStorage and
 * correlates them with the biorhythm curves. Pure functions are exported for tests (node --test).
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.BiorrhythmsDiary = api;
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const STORAGE_KEY = 'biorrhythms-diary-v1';
    const MIN_N = 14;
    const PERIODS = { physical: 23, emotional: 28, intellectual: 33 };
    const METRIC_FOR = { physical: 'energy', emotional: 'mood', intellectual: 'focus' };
    const METRICS = ['energy', 'mood', 'focus'];
    const ISO = /^\d{4}-\d{2}-\d{2}$/;

    function parseIso(iso) {
        if (typeof iso !== 'string' || !ISO.test(iso)) return null;
        const d = new Date(iso + 'T00:00:00Z');
        return Number.isNaN(d.getTime()) || d.toISOString().slice(0, 10) !== iso ? null : d;
    }

    function rhythmValue(birthIso, dateIso, period) {
        const birth = parseIso(birthIso);
        const date = parseIso(dateIso);
        if (!birth || !date) return null;
        const days = Math.round((date.getTime() - birth.getTime()) / 86400000);
        return Math.sin((2 * Math.PI * days) / period);
    }

    function pearson(xs, ys) {
        const n = xs.length;
        if (n < 2 || n !== ys.length) return null;
        const mx = xs.reduce((a, b) => a + b, 0) / n;
        const my = ys.reduce((a, b) => a + b, 0) / n;
        let sxy = 0, sxx = 0, syy = 0;
        for (let i = 0; i < n; i++) {
            const dx = xs[i] - mx;
            const dy = ys[i] - my;
            sxy += dx * dy;
            sxx += dx * dx;
            syy += dy * dy;
        }
        if (sxx === 0 || syy === 0) return null;
        return Math.max(-1, Math.min(1, sxy / Math.sqrt(sxx * syy)));
    }

    /** 95% interval via Fisher z. Optimistic for serially correlated data such as daily logs. */
    function confidenceInterval(r, n) {
        if (r === null || n < 4) return null;
        if (Math.abs(r) >= 1) return [r, r];
        const z = Math.atanh(r);
        const se = 1 / Math.sqrt(n - 3);
        return [Math.tanh(z - 1.96 * se), Math.tanh(z + 1.96 * se)];
    }

    function strengthLabel(r) {
        const a = Math.abs(r);
        if (a < 0.2) return 'sin relación apreciable';
        if (a < 0.4) return 'relación débil';
        if (a < 0.6) return 'relación moderada';
        return 'relación fuerte';
    }

    function normalizeEntry(entry) {
        if (!entry || typeof entry !== 'object') return null;
        const out = {};
        for (const metric of METRICS) {
            const v = entry[metric];
            if (!Number.isInteger(v) || v < 1 || v > 5) return null;
            out[metric] = v;
        }
        return out;
    }

    function correlations(entries, birthIso) {
        const dates = Object.keys(entries).filter((d) => parseIso(d)).sort();
        return Object.keys(PERIODS).map((rhythm) => {
            const metric = METRIC_FOR[rhythm];
            const xs = [];
            const ys = [];
            for (const date of dates) {
                const x = rhythmValue(birthIso, date, PERIODS[rhythm]);
                if (x === null) continue;
                xs.push(x);
                ys.push(entries[date][metric]);
            }
            const n = xs.length;
            if (n < MIN_N) return { rhythm, metric, n, status: 'insufficient', missing: MIN_N - n };
            const r = pearson(xs, ys);
            if (r === null) return { rhythm, metric, n, status: 'flat' };
            return { rhythm, metric, n, status: 'ok', r, ci: confidenceInterval(r, n), label: strengthLabel(r) };
        });
    }

    function parseImport(text) {
        let raw;
        try {
            raw = JSON.parse(text);
        } catch (e) {
            return { entries: {}, accepted: 0, rejected: 0, error: 'JSON no válido' };
        }
        const source = raw && typeof raw === 'object' && raw.entries && typeof raw.entries === 'object' ? raw.entries : null;
        if (!source) return { entries: {}, accepted: 0, rejected: 0, error: 'Falta el objeto "entries"' };

        const entries = {};
        let rejected = 0;
        for (const [date, value] of Object.entries(source)) {
            const clean = parseIso(date) ? normalizeEntry(value) : null;
            if (clean) entries[date] = clean;
            else rejected++;
        }
        return { entries, accepted: Object.keys(entries).length, rejected, error: null };
    }

    function serialize(entries) {
        return JSON.stringify({ version: 1, entries }, null, 2);
    }

    function load(storage) {
        try {
            const parsed = parseImport(storage.getItem(STORAGE_KEY) || '{"entries":{}}');
            return parsed.entries;
        } catch (e) {
            return {};
        }
    }

    function save(storage, entries) {
        try {
            storage.setItem(STORAGE_KEY, serialize(entries));
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

    return {
        STORAGE_KEY, MIN_N, PERIODS, METRIC_FOR,
        parseIso, rhythmValue, pearson, confidenceInterval, strengthLabel,
        normalizeEntry, correlations, parseImport, serialize, load, save, clear,
    };
});
