'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const C = require('../../assets/chrono.js');

const best = Object.fromEntries(C.QUESTIONS.map((q) => [q.id, Math.max(...q.options.map((o) => o[1]))]));
const worst = Object.fromEntries(C.QUESTIONS.map((q) => [q.id, Math.min(...q.options.map((o) => o[1]))]));

test('score range is exactly 4-25', () => {
    assert.equal(C.score(best), C.MAX_SCORE);
    assert.equal(C.score(worst), C.MIN_SCORE);
    assert.equal(C.MAX_SCORE, 25);
    assert.equal(C.MIN_SCORE, 4);
});

test('every question has distinct option values', () => {
    for (const q of C.QUESTIONS) {
        const values = q.options.map((o) => o[1]);
        assert.equal(new Set(values).size, values.length, q.id);
    }
    assert.equal(C.QUESTIONS.length, 5);
});

test('score is null when incomplete or when a value is not an option', () => {
    assert.equal(C.score({}), null);
    assert.equal(C.score(null), null);
    assert.equal(C.score({ ...best, rise: 9 }), null);
    const partial = { ...best };
    delete partial.peak;
    assert.equal(C.score(partial), null);
});

test('classify uses the three-group cut-offs 4-11 / 12-17 / 18-25', () => {
    assert.equal(C.classify(4).key, 'evening');
    assert.equal(C.classify(11).key, 'evening');
    assert.equal(C.classify(12).key, 'intermediate');
    assert.equal(C.classify(17).key, 'intermediate');
    assert.equal(C.classify(18).key, 'morning');
    assert.equal(C.classify(25).key, 'morning');
    assert.equal(C.classify(3), null);
    assert.equal(C.classify(26), null);
    assert.equal(C.classify(12.5), null);
    assert.equal(C.classify(null), null);
});

test('all reachable totals classify', () => {
    for (let t = C.MIN_SCORE; t <= C.MAX_SCORE; t++) assert.ok(C.classify(t), String(t));
});

test('time helpers wrap around midnight', () => {
    assert.equal(C.toMinutes('07:30'), 450);
    assert.equal(C.toMinutes('7:30'), null);
    assert.equal(C.toMinutes('24:00'), null);
    assert.equal(C.toMinutes('12:60'), null);
    assert.equal(C.toMinutes(''), null);
    assert.equal(C.fromMinutes(1500), '01:00');
    assert.equal(C.fromMinutes(-30), '23:30');
    assert.equal(C.fromMinutes(0), '00:00');
});

test('schedule anchors every window on the wake-up time', () => {
    const s = C.schedule('07:00');
    const get = (key) => s.windows.find((w) => w.key === key);

    assert.equal(s.wake, '07:00');
    assert.deepEqual([get('light').start, get('light').end], ['07:00', '08:00']);
    assert.deepEqual([get('focus1').start, get('focus1').end], ['09:00', '11:00']);
    assert.deepEqual([get('dip').start, get('dip').end], ['14:00', '16:00']);
    assert.deepEqual([get('focus2').start, get('focus2').end], ['17:00', '19:00']);
    assert.equal(get('caffeine').start, '15:00');
    assert.equal(get('winddown').start, '21:00');
    assert.equal(get('bed').start, '23:00');
});

test('schedule wraps past midnight for late risers', () => {
    const s = C.schedule('11:30');
    assert.equal(s.windows.find((w) => w.key === 'bed').start, '03:30');
});

test('schedule rejects an invalid wake time', () => {
    assert.equal(C.schedule('25:00'), null);
    assert.equal(C.schedule(''), null);
    assert.equal(C.schedule(undefined), null);
});

test('ultradian blocks: four 90-minute blocks with 20-minute breaks, from wake + 1 h', () => {
    const blocks = C.ultradianBlocks('07:00');
    assert.deepEqual(blocks.map((b) => [b.start, b.end]), [
        ['08:00', '09:30'],
        ['09:50', '11:20'],
        ['11:40', '13:10'],
        ['13:30', '15:00'],
    ]);
    assert.deepEqual(blocks.map((b) => b.index), [1, 2, 3, 4]);
    assert.equal(C.ultradianBlocks('nope'), null);
    assert.equal(C.ultradianBlocks('07:00', 2).length, 2);
});
