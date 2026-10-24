'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const P = require('../../assets/profile.js');

function memoryStorage(initial = {}) {
    const data = { ...initial };
    return {
        getItem: (k) => (k in data ? data[k] : null),
        setItem: (k, v) => { data[k] = String(v); },
        removeItem: (k) => { delete data[k]; },
    };
}

test('validIso: real dates inside 1900-2100 only', () => {
    assert.ok(P.validIso('2024-02-29'));
    assert.ok(P.validIso('1900-01-01'));
    assert.ok(P.validIso('2100-12-31'));
    assert.equal(P.validIso('2023-02-29'), false);
    assert.equal(P.validIso('1899-12-31'), false);
    assert.equal(P.validIso('2101-01-01'), false);
    assert.equal(P.validIso('2026-13-01'), false);
    assert.equal(P.validIso(null), false);
    assert.equal(P.validIso(20260101), false);
});

test('sanitize drops invalid fields and keeps valid ones', () => {
    const clean = P.sanitize({
        birth: '1972-09-19',
        notify: 'yes',
        wake: '25:00',
        chronotype: { score: 30, key: 'morning' },
        notified: ['2026-10-24', 'bad', '2026-02-31'],
        extra: 1,
    });
    assert.deepEqual(clean, { birth: '1972-09-19', chronotype: null, wake: null, notify: false, notified: ['2026-10-24'] });

    assert.deepEqual(P.sanitize({ chronotype: { score: 18, key: 'morning' } }).chronotype, { score: 18, key: 'morning' });
    assert.equal(P.sanitize({ chronotype: { score: 18, key: 'owl' } }).chronotype, null);
    assert.equal(P.sanitize({ chronotype: { score: 3, key: 'evening' } }).chronotype, null);
    assert.equal(P.sanitize({ wake: '07:30' }).wake, '07:30');
    assert.equal(P.sanitize({ wake: '7:30' }).wake, null);
    assert.deepEqual(P.sanitize('junk'), { birth: null, chronotype: null, wake: null, notify: false, notified: [] });
});

test('sanitize keeps only the last 10 notified dates', () => {
    const dates = Array.from({ length: 15 }, (_, i) => `2026-01-${String(i + 1).padStart(2, '0')}`);
    assert.equal(P.sanitize({ notified: dates }).notified.length, 10);
    assert.equal(P.sanitize({ notified: dates }).notified[9], '2026-01-15');
});

test('save merges patches and load roundtrips', () => {
    const storage = memoryStorage();
    assert.equal(P.save(storage, { birth: '1972-09-19' }), true);
    assert.equal(P.save(storage, { notify: true }), true);
    const profile = P.load(storage);
    assert.equal(profile.birth, '1972-09-19');
    assert.equal(profile.notify, true);
});

test('save refuses to store an invalid birth', () => {
    const storage = memoryStorage();
    P.save(storage, { birth: '2026-02-31' });
    assert.equal(P.load(storage).birth, null);
});

test('clear removes everything; broken storage never throws', () => {
    const storage = memoryStorage();
    P.save(storage, { birth: '1972-09-19' });
    assert.equal(P.clear(storage), true);
    assert.equal(P.load(storage).birth, null);

    const broken = { getItem() { throw new Error('x'); }, setItem() { throw new Error('x'); }, removeItem() { throw new Error('x'); } };
    assert.equal(P.load(broken).birth, null);
    assert.equal(P.save(broken, { birth: '1972-09-19' }), false);
    assert.equal(P.clear(broken), false);
    assert.equal(P.load(null).birth, null);
});

test('load tolerates corrupted JSON', () => {
    assert.equal(P.load(memoryStorage({ [P.STORAGE_KEY]: '{{' })).birth, null);
});

test('resolveUrl fills a missing birth from the saved profile', () => {
    const next = P.resolveUrl('http://x/?focus=2026-10-24', { birth: '1972-09-19' }, '2026-10-24', '2026-10-24');
    assert.equal(new URL(next).searchParams.get('birth'), '1972-09-19');
    assert.equal(new URL(next).searchParams.get('focus'), '2026-10-24');
});

test('resolveUrl never overrides a birth that is already in the URL', () => {
    assert.equal(P.resolveUrl('http://x/?birth=1990-01-01&focus=2026-10-24', { birth: '1972-09-19' }, '2026-10-24', '2026-10-24'), null);
    // An invalid but present birth is left for the server to report.
    assert.equal(P.resolveUrl('http://x/?birth=2026-02-31&focus=2026-10-24', { birth: '1972-09-19' }, '2026-10-24', '2026-10-24'), null);
});

test('resolveUrl treats an empty birth as missing', () => {
    const next = P.resolveUrl('http://x/?birth=&focus=2026-10-24', { birth: '1972-09-19' }, '2026-10-24', '2026-10-24');
    assert.equal(new URL(next).searchParams.get('birth'), '1972-09-19');
});

test('resolveUrl sets the local day only when it differs from the server day', () => {
    const differs = P.resolveUrl('http://x/?birth=1990-01-01', null, '2026-10-25', '2026-10-24');
    assert.equal(new URL(differs).searchParams.get('focus'), '2026-10-25');
    assert.equal(P.resolveUrl('http://x/?birth=1990-01-01', null, '2026-10-24', '2026-10-24'), null);
});

test('resolveUrl keeps other parameters and is idempotent (no redirect loop)', () => {
    const first = P.resolveUrl('http://x/?preset=work&partner_birth=1971-05-30', { birth: '1972-09-19' }, '2026-10-25', '2026-10-24');
    const url = new URL(first);
    assert.equal(url.searchParams.get('preset'), 'work');
    assert.equal(url.searchParams.get('partner_birth'), '1971-05-30');
    assert.equal(P.resolveUrl(first, { birth: '1972-09-19' }, '2026-10-25', '2026-10-24'), null);
});

test('resolveUrl with no profile and no difference changes nothing', () => {
    assert.equal(P.resolveUrl('http://x/', null, '2026-10-24', '2026-10-24'), null);
});

test('rememberBirth stores only a real birth from the URL', () => {
    const storage = memoryStorage();
    assert.equal(P.rememberBirth(storage, 'http://x/?birth=2026-02-31'), false);
    assert.equal(P.rememberBirth(storage, 'http://x/'), false);
    assert.equal(P.rememberBirth(storage, 'http://x/?birth=1972-09-19'), true);
    assert.equal(P.load(storage).birth, '1972-09-19');
});

test('localDay formats the local calendar date', () => {
    assert.equal(P.localDay(new Date(2026, 9, 5, 23, 59)), '2026-10-05');
});
