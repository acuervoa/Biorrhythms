<?php

if (!defined('BIORRHYTHMS_ENTRYPOINT')) {
    http_response_code(404);
    exit;
}
?>
        <section class="section card forecast-card" id="forecast">
            <div class="events-head">
                <div>
                    <h2>Horizonte de exploración</h2>
                    <p>Elige cuánto futuro quieres revisar desde la fecha seleccionada.</p>
                </div>
                <div class="explore-horizon" role="group" aria-label="Horizonte de eventos">
                    <button type="button" data-explore-horizon="7" aria-pressed="false">7 días</button>
                    <button type="button" data-explore-horizon="30" aria-pressed="true">30 días</button>
                    <button type="button" data-explore-horizon="90" aria-pressed="false">90 días</button>
                </div>
            </div>
        </section>

        <section class="section card events-card" id="events">
            <div class="events-head">
                <div>
                    <h2>Días especiales</h2>
                    <p>Picos, valles y cruces por cero agrupados por fecha y ciclo.</p>
                </div>
                <div class="status-pill" id="eventsPill">—</div>
            </div>
            <div class="events-list" id="eventsList"></div>
        </section>

        <section class="section card extreme-card" id="extremeDays">
            <div class="extreme-head">
                <div>
                    <h2>Días extremos</h2>
                    <p>Días futuros con media superior al +<?= $extremePercent ?>% o inferior al −<?= $extremePercent ?>%.</p>
                </div>
                <div class="status-pill" id="extremePill">—</div>
            </div>
            <div class="extreme-grid" id="extremeGrid"></div>
            <div class="extreme-actions">
                <button type="button" id="cycleExtremeReveal" data-explore-horizon="cycle" aria-pressed="false">Ver ciclo completo</button>
                <button type="button" id="cycleExtremeMore" hidden>Mostrar 10 más</button>
                <button type="button" id="cycleExtremeLess" hidden>Mostrar menos</button>
            </div>
        </section>

        <section class="section card extreme-card" id="monthCalendar">
            <div class="extreme-head">
                <div>
                    <h2>Calendario de <?= htmlspecialchars($month['label']) ?></h2>
                    <p>Media diaria de los tres ritmos y días críticos (cruces por cero).</p>
                </div>
                <div class="month-nav">
                    <a class="secondary-btn" href="<?= htmlspecialchars($monthLink($prevMonth)) ?>">← Mes anterior</a>
                    <a class="secondary-btn" href="<?= htmlspecialchars($monthLink($nextMonth)) ?>">Mes siguiente →</a>
                    <a class="secondary-btn" id="icsExportLink" href="<?= htmlspecialchars($icsLink) ?>" download>Días críticos (.ics)</a>
                </div>
            </div>
            <div class="month-calendar-scroll" role="region" aria-label="Calendario mensual desplazable" tabindex="0">
            <table class="month-grid" aria-label="Calendario de <?= htmlspecialchars($month['label']) ?>">
                <thead><tr>
<?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dow): ?>
                    <th class="month-dow" scope="col"><?= $dow ?></th>
<?php endforeach; ?>
                </tr></thead>
                <tbody>
<?php foreach ($month['weeks'] as $week): ?>
                <tr>
<?php foreach ($week as $cell): ?>
<?php if ($cell === null): ?>
                    <td class="month-cell is-pad" aria-hidden="true"></td>
<?php else: ?>
                    <td class="month-cell" data-level="<?= htmlspecialchars($cell['level']) ?>">
                        <a class="month-cell-link<?= $cell['is_focus'] ? ' is-focus' : '' ?>"
                           href="<?= htmlspecialchars($monthLink($cell['date'])) ?>"
                           aria-label="Día <?= (int) $cell['day'] ?>, media <?= round($cell['avg'] * 100) ?>%<?= $cell['critical'] > 0 ? ', día crítico ' . ['', 'simple', 'doble', 'triple'][$cell['critical']] : '' ?>">
                            <strong><?= (int) $cell['day'] ?></strong>
                            <span class="month-average" aria-hidden="true"><?= (int) round($cell['avg'] * 100) ?>%</span><?= $cell['critical'] > 0 ? ' ' . str_repeat('●', $cell['critical']) : '' ?>
                        </a>
                    </td>
<?php endif; ?>
<?php endforeach; ?>
                </tr>
<?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="month-legend">
                Verde: media ≥ +35% · Ámbar: entre −15% y +35% · Rojo: por debajo de −15%. ● = ritmos que cruzan cero ese día (1 simple, 2 doble, 3 triple).
            </p>
        </section>
