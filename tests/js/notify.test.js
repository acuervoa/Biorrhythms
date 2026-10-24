'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const N = require('../../assets/notify.js');

const day = (date, kind = 'single', rhythms = [{ key: 'physical', direction: 'falling' }]) => ({ date, kind, rhythms });

test('addDay crosses month and year boundaries', () => {
    assert.equal(N.addDay('2026-12-31', 1), '2027-01-01');
    assert.equal(N.addDay('2024-02-28', 1), '2024-02-29');
    assert.equal(N.addDay('2026-03-01', -1), '2026-02-28');
});

test('no critical days -> no notice', () => {
    assert.equal(N.pendingNotice([], '2026-10-24', []), null);
    assert.equal(N.pendingNotice(null, '2026-10-24', null), null);
});

test('today wins over tomorrow', () => {
    const n = N.pendingNotice([day('2026-10-25'), day('2026-10-24')], '2026-10-24', []);
    assert.equal(n.date, '2026-10-24');
    assert.match(n.title, /hoy/);
});

test('tomorrow is announced when today is not critical', () => {
    const n = N.pendingNotice([day('2026-10-25', 'double', [
        { key: 'physical', direction: 'rising' }, { key: 'emotional', direction: 'falling' },
    ])], '2026-10-24', []);
    assert.equal(n.date, '2026-10-25');
    assert.match(n.title, /doble mañana/);
    assert.match(n.body, /Físico ↑, Emocional ↓/);
});

test('days further than tomorrow are ignored', () => {
    assert.equal(N.pendingNotice([day('2026-10-26')], '2026-10-24', []), null);
});

test('an already notified date is not repeated, and does not ping-pong', () => {
    const critical = [day('2026-10-24'), day('2026-10-25')];

    const first = N.pendingNotice(critical, '2026-10-24', []);
    assert.equal(first.date, '2026-10-24');
    const second = N.pendingNotice(critical, '2026-10-24', [first.date]);
    assert.equal(second.date, '2026-10-25');
    assert.equal(N.pendingNotice(critical, '2026-10-24', [first.date, second.date]), null);
    // Reopening on the next day: its own date was already notified yesterday.
    assert.equal(N.pendingNotice(critical, '2026-10-25', [first.date, second.date]), null);
});

test('the notice carries the disclaimer', () => {
    assert.match(N.pendingNotice([day('2026-10-24')], '2026-10-24', []).body, /sin validez predictiva demostrada/);
});
