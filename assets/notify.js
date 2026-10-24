/* Decides whether to show a critical-day notice when the app is opened. Pure function, tested with node. */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.BiorrhythmsNotify = api;
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const LABELS = { physical: 'Físico', emotional: 'Emocional', intellectual: 'Intelectual' };

    function addDay(iso, n) {
        const d = new Date(iso + 'T00:00:00Z');
        d.setUTCDate(d.getUTCDate() + n);
        return d.toISOString().slice(0, 10);
    }

    /**
     * @param critical  critical days from the server, each {date, kind, rhythms:[{key, direction}]}
     * @param today     the visitor's local day (YYYY-MM-DD)
     * @param notified  dates already notified
     * @returns {{date: string, title: string, body: string}|null} today's critical day first, then tomorrow's
     */
    function pendingNotice(critical, today, notified) {
        const done = new Set(notified || []);
        const byDate = new Map((critical || []).map((c) => [c.date, c]));

        for (const [offset, when] of [[0, 'hoy'], [1, 'mañana']]) {
            const date = addDay(today, offset);
            const day = byDate.get(date);
            if (!day || done.has(date)) continue;

            const parts = day.rhythms.map((r) => LABELS[r.key] + (r.direction === 'rising' ? ' ↑' : ' ↓')).join(', ');
            const kind = { single: 'simple', double: 'doble', triple: 'triple' }[day.kind] || '';
            return {
                date,
                title: 'Día crítico ' + kind + ' ' + when,
                body: 'Cruzan cero: ' + parts + '. Ve con calma; es una herramienta de reflexión, sin validez predictiva demostrada.',
            };
        }
        return null;
    }

    return { pendingNotice, addDay };
});
