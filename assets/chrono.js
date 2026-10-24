/*
 * Chronotype questionnaire and a day template anchored on the wake-up time.
 * The questions are an own adaptation inspired by the reduced Morningness-Eveningness Questionnaire
 * (Adan & Almirall, 1991): same score range (4-25) and three groups, but NOT the validated instrument.
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.BiorrhythmsChrono = api;
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const QUESTIONS = [
        {
            id: 'rise',
            text: 'Si pudieras organizar tu día con total libertad, ¿a qué hora te levantarías?',
            options: [['05:00 – 06:30', 5], ['06:30 – 07:45', 4], ['07:45 – 09:45', 3], ['09:45 – 11:00', 2], ['11:00 – 12:00', 1]],
        },
        {
            id: 'alert',
            text: '¿Cómo de despierto te sientes la primera media hora tras levantarte?',
            options: [['Muy despierto', 4], ['Bastante', 3], ['Poco', 2], ['Nada', 1]],
        },
        {
            id: 'tired',
            text: '¿A qué hora de la noche empiezas a notar cansancio y necesidad de dormir?',
            options: [['20:00 – 21:00', 5], ['21:00 – 22:15', 4], ['22:15 – 00:45', 3], ['00:45 – 02:00', 2], ['02:00 – 03:00', 1]],
        },
        {
            id: 'peak',
            text: '¿A qué hora del día sientes que estás en tu mejor momento?',
            options: [['05:00 – 08:00', 5], ['08:00 – 10:00', 4], ['10:00 – 17:00', 3], ['17:00 – 22:00', 2], ['22:00 – 05:00', 1]],
        },
        {
            id: 'type',
            text: '¿Te consideras una persona más matutina o más vespertina?',
            options: [['Claramente matutina', 6], ['Más matutina que vespertina', 4], ['Más vespertina que matutina', 2], ['Claramente vespertina', 0]],
        },
    ];

    const MIN_SCORE = 4;
    const MAX_SCORE = 25;
    const HHMM = /^([01]\d|2[0-3]):([0-5]\d)$/;

    const TYPES = {
        morning: {
            label: 'Matutino',
            advice: 'Tiendes a rendir pronto: reserva lo más exigente para las primeras horas y evita citas críticas muy tarde.',
        },
        intermediate: {
            label: 'Intermedio',
            advice: 'Perfil flexible: ajusta el día a tu horario real y mantén horas regulares de sueño y de luz.',
        },
        evening: {
            label: 'Vespertino',
            advice: 'Tiendes a activarte más tarde: si puedes, no pongas lo más difícil nada más despertar; la luz natural de la mañana ayuda a adelantar el reloj.',
        },
    };

    /** Sum of the chosen option values, or null when any question is unanswered or the value is not an option. */
    function score(answers) {
        let total = 0;
        for (const q of QUESTIONS) {
            const value = answers ? answers[q.id] : undefined;
            if (!q.options.some((o) => o[1] === value)) return null;
            total += value;
        }
        return total;
    }

    /** 4-11 evening, 12-17 intermediate, 18-25 morning (the three-group cut-offs of the reduced scale). */
    function classify(total) {
        if (!Number.isInteger(total) || total < MIN_SCORE || total > MAX_SCORE) return null;
        const key = total <= 11 ? 'evening' : total <= 17 ? 'intermediate' : 'morning';
        return { key, label: TYPES[key].label, advice: TYPES[key].advice };
    }

    function toMinutes(hhmm) {
        const m = HHMM.exec(hhmm || '');
        return m ? Number(m[1]) * 60 + Number(m[2]) : null;
    }

    function fromMinutes(total) {
        const t = ((Math.round(total) % 1440) + 1440) % 1440;
        return String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
    }

    /**
     * Day template relative to the wake-up time. These are rule-of-thumb offsets (light early, alertness peak
     * mid-morning, post-lunch dip ~8 h after waking, caffeine cut-off ~8 h before bed), not measurements.
     */
    function schedule(wake) {
        const w = toMinutes(wake);
        if (w === null) return null;
        const at = (hours) => fromMinutes(w + hours * 60);

        return {
            wake: fromMinutes(w),
            windows: [
                { key: 'light', label: 'Luz natural y desayuno', start: at(0), end: at(1) },
                { key: 'focus1', label: 'Primera ventana de foco', start: at(2), end: at(4) },
                { key: 'dip', label: 'Valle postprandial (tareas ligeras)', start: at(7), end: at(9) },
                { key: 'focus2', label: 'Segunda ventana de foco', start: at(10), end: at(12) },
                { key: 'caffeine', label: 'Último café antes de', start: at(8), end: at(8) },
                { key: 'winddown', label: 'Bajar luces y pantallas', start: at(14), end: at(14) },
                { key: 'bed', label: 'Acostarse (8 h de sueño)', start: at(16), end: at(16) },
            ],
            blocks: ultradianBlocks(wake),
        };
    }

    /** Four 90-minute work blocks with 20-minute breaks, starting one hour after waking. */
    function ultradianBlocks(wake, count = 4) {
        const w = toMinutes(wake);
        if (w === null) return null;
        const blocks = [];
        for (let i = 0; i < count; i++) {
            const start = w + 60 + i * 110;
            blocks.push({ index: i + 1, start: fromMinutes(start), end: fromMinutes(start + 90) });
        }
        return blocks;
    }

    return { QUESTIONS, MIN_SCORE, MAX_SCORE, TYPES, score, classify, toMinutes, fromMinutes, schedule, ultradianBlocks };
});
