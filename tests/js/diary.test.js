'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const D = require('../../assets/diary.js');

const near = (a, b, eps = 1e-9) => assert.ok(Math.abs(a - b) < eps, `${a} !~ ${b}`);

function memoryStorage(initial = {}) {
    const data = { ...initial };
    return {
        getItem: (k) => (k in data ? data[k] : null),
        setItem: (k, v) => { data[k] = String(v); },
        removeItem: (k) => { delete data[k]; },
    };
}

test('parseIso accepts real dates only', () => {
    assert.ok(D.parseIso('2024-02-29'));
    assert.equal(D.parseIso('2023-02-29'), null);
    assert.equal(D.parseIso('2026-13-01'), null);
    assert.equal(D.parseIso('26-01-01'), null);
    assert.equal(D.parseIso(20260101), null);
});

test('rhythmValue matches the PHP formula', () => {
    // 1972-09-19 -> 2026-06-26: PHP gives physical -0.8878852184023944, emotional 0.7818314824682941
    near(D.rhythmValue('1972-09-19', '2026-06-26', 23), -0.8878852184023944);
    near(D.rhythmValue('1972-09-19', '2026-06-26', 28), 0.7818314824682941);
    near(D.rhythmValue('1990-01-01', '1990-01-01', 23), 0);
    assert.equal(D.rhythmValue('bad', '2026-01-01', 23), null);
});

test('pearson: perfect, inverse, flat and degenerate cases', () => {
    near(D.pearson([1, 2, 3, 4], [2, 4, 6, 8]), 1);
    near(D.pearson([1, 2, 3, 4], [8, 6, 4, 2]), -1);
    assert.equal(D.pearson([1, 2, 3], [5, 5, 5]), null);
    assert.equal(D.pearson([1], [1]), null);
    assert.equal(D.pearson([1, 2], [1, 2, 3]), null);
    near(D.pearson([1, 2, 3, 4, 5], [2, 1, 4, 3, 5]), 0.8);
});

test('confidenceInterval is symmetric in z, bounded, and needs n >= 4', () => {
    assert.equal(D.confidenceInterval(0.5, 3), null);
    assert.equal(D.confidenceInterval(null, 30), null);
    const [lo, hi] = D.confidenceInterval(0.5, 30);
    assert.ok(lo < 0.5 && hi > 0.5 && lo > -1 && hi < 1);
    const [lo2, hi2] = D.confidenceInterval(0.5, 300);
    assert.ok(hi2 - lo2 < hi - lo, 'more data narrows the interval');
    assert.deepEqual(D.confidenceInterval(1, 20), [1, 1]);
});

test('strengthLabel bands', () => {
    assert.equal(D.strengthLabel(0.1), 'sin relación apreciable');
    assert.equal(D.strengthLabel(-0.3), 'relación débil');
    assert.equal(D.strengthLabel(0.5), 'relación moderada');
    assert.equal(D.strengthLabel(-0.9), 'relación fuerte');
});

test('normalizeEntry validates ranges and integers', () => {
    assert.deepEqual(D.normalizeEntry({ energy: 3, mood: 4, focus: 5, extra: 1 }), { energy: 3, mood: 4, focus: 5 });
    assert.equal(D.normalizeEntry({ energy: 0, mood: 4, focus: 5 }), null);
    assert.equal(D.normalizeEntry({ energy: 3, mood: 4.5, focus: 5 }), null);
    assert.equal(D.normalizeEntry({ energy: '3', mood: 4, focus: 5 }), null);
    assert.equal(D.normalizeEntry({ energy: 3, mood: 4 }), null);
    assert.equal(D.normalizeEntry(null), null);
});

function synthetic(birth, days, fn) {
    const entries = {};
    const start = Date.UTC(2026, 0, 1);
    for (let i = 0; i < days; i++) {
        const iso = new Date(start + i * 86400000).toISOString().slice(0, 10);
        entries[iso] = fn(iso);
    }
    return entries;
}

test('correlations: below the minimum sample reports what is missing', () => {
    const entries = synthetic('1990-01-01', 5, () => ({ energy: 3, mood: 3, focus: 3 }));
    const result = D.correlations(entries, '1990-01-01');
    assert.equal(result.length, 3);
    for (const row of result) {
        assert.equal(row.status, 'insufficient');
        assert.equal(row.n, 5);
        assert.equal(row.missing, D.MIN_N - 5);
    }
});

test('correlations: data that follows the curve gives a strong positive r', () => {
    const birth = '1990-01-01';
    const entries = synthetic(birth, 60, (iso) => {
        const to5 = (v) => Math.max(1, Math.min(5, Math.round(3 + 2 * v)));
        return {
            energy: to5(D.rhythmValue(birth, iso, 23)),
            mood: to5(D.rhythmValue(birth, iso, 28)),
            focus: to5(D.rhythmValue(birth, iso, 33)),
        };
    });
    for (const row of D.correlations(entries, birth)) {
        assert.equal(row.status, 'ok');
        assert.ok(row.r > 0.9, `${row.rhythm} r=${row.r}`);
        assert.equal(row.label, 'relación fuerte');
        assert.ok(row.ci[0] <= row.r && row.r <= row.ci[1]);
    }
});

test('correlations: constant answers are reported as flat, not as r=0', () => {
    const entries = synthetic('1990-01-01', 30, () => ({ energy: 3, mood: 3, focus: 3 }));
    for (const row of D.correlations(entries, '1990-01-01')) assert.equal(row.status, 'flat');
});

test('correlations: data unrelated to the curve stays small', () => {
    const entries = synthetic('1990-01-01', 90, (iso) => {
        // Deterministic pseudo-noise from the date, independent of any 23/28/33-day cycle.
        const h = [...iso].reduce((a, c) => (a * 31 + c.charCodeAt(0)) % 9973, 7);
        return { energy: (h % 5) + 1, mood: ((h * 7) % 5) + 1, focus: ((h * 13) % 5) + 1 };
    });
    for (const row of D.correlations(entries, '1990-01-01')) {
        if (row.status === 'ok') assert.ok(Math.abs(row.r) < 0.4, `${row.rhythm} r=${row.r}`);
    }
});

test('parseImport accepts valid rows and counts the rejected ones', () => {
    const text = JSON.stringify({
        version: 1,
        entries: {
            '2026-01-01': { energy: 3, mood: 3, focus: 3 },
            '2026-02-30': { energy: 3, mood: 3, focus: 3 },
            '2026-01-02': { energy: 9, mood: 3, focus: 3 },
            '2026-01-03': { energy: 1, mood: 2, focus: 3 },
        },
    });
    const result = D.parseImport(text);
    assert.equal(result.accepted, 2);
    assert.equal(result.rejected, 2);
    assert.deepEqual(Object.keys(result.entries).sort(), ['2026-01-01', '2026-01-03']);
    assert.equal(result.error, null);
});

test('parseImport rejects malformed input without throwing', () => {
    assert.equal(D.parseImport('not json').error, 'JSON no válido');
    assert.equal(D.parseImport('{"foo":1}').error, 'Falta el objeto "entries"');
    assert.equal(D.parseImport('null').error, 'Falta el objeto "entries"');
});

test('parseImport ignores prototype pollution keys', () => {
    const result = D.parseImport('{"entries":{"__proto__":{"energy":1,"mood":1,"focus":1}}}');
    assert.equal(result.accepted, 0);
    assert.equal({}.energy, undefined);
});

test('save / load / clear roundtrip through storage', () => {
    const storage = memoryStorage();
    const entries = { '2026-01-01': { energy: 3, mood: 4, focus: 5 } };

    assert.equal(D.save(storage, entries), true);
    assert.deepEqual(D.load(storage), entries);
    assert.equal(D.clear(storage), true);
    assert.deepEqual(D.load(storage), {});
});

test('storage failures never throw', () => {
    const broken = {
        getItem() { throw new Error('denied'); },
        setItem() { throw new Error('quota'); },
        removeItem() { throw new Error('denied'); },
    };
    assert.deepEqual(D.load(broken), {});
    assert.equal(D.save(broken, {}), false);
    assert.equal(D.clear(broken), false);
});

test('load tolerates corrupted stored data', () => {
    const storage = memoryStorage({ [D.STORAGE_KEY]: '{{{' });
    assert.deepEqual(D.load(storage), {});
});
