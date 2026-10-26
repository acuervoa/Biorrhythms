<?php

use Biorrhythms\DateInput;

if (!defined('BIORRHYTHMS_ENTRYPOINT')) {
    http_response_code(404);
    exit;
}
?>
<section id="lectura" data-workspace-panel="lectura" aria-labelledby="<?= $showWelcome ? 'welcomeTitle' : 'readingDateHeading' ?>">
<?php if ($showWelcome && !$embedMode): ?>
        <section class="welcome-sheet" aria-labelledby="welcomeTitle">
            <p class="section-label">Tu lectura personal</p>
            <h1 id="welcomeTitle">Empieza por tu fecha</h1>
            <p>Calcula tres ciclos para el día que elijas. Son resultados matemáticos, no mediciones ni predicciones.</p>
            <form class="welcome-form" method="get" id="welcomeForm">
                <label for="welcomeBirth">Fecha de nacimiento</label>
                <div class="welcome-form-row">
                    <input id="welcomeBirth" type="date" name="birth" required min="<?= DateInput::MIN ?>" max="<?= DateInput::MAX ?>">
                    <button type="submit" class="bar-submit">Ver mi lectura</button>
                </div>
                <small>Se guarda solo en este navegador.</small>
            </form>
        </section>
<?php endif; ?>
<?php if ($inputNotices !== [] && !$embedMode): ?>
        <div class="input-notice" role="alert">
<?php foreach ($inputNotices as $notice): ?>
            <p><?= htmlspecialchars($notice) ?></p>
<?php endforeach; ?>
        </div>
<?php endif; ?>

<?php if (!$showWelcome || $embedMode): ?>
        <section class="hero" id="reading-content" aria-labelledby="readingDateHeading">
            <div class="journal-layout">
                <nav class="date-rail" aria-label="Días cercanos">
                    <a class="date-rail-item" id="dateRailPrevious" href="<?= htmlspecialchars($readingDateLink($previousDayDate->format('Y-m-d'))) ?>" aria-label="Día anterior: <?= htmlspecialchars(\Biorrhythms\DateFormatter::long($previousDayDate)) ?>">
                        <strong><?= $previousDayDate->format('j') ?></strong>
                        <span><?= htmlspecialchars($readingMonths[(int) $previousDayDate->format('n')] . ' ' . $previousDayDate->format('Y')) ?></span>
                        <small><?= htmlspecialchars($readingWeekdaysShort[(int) $previousDayDate->format('N')]) ?></small>
                    </a>
                    <a class="date-rail-item is-selected" id="dateRailCurrent" href="<?= htmlspecialchars($readingDateLink($focusDate->format('Y-m-d'))) ?>" aria-current="date" aria-label="Día seleccionado: <?= htmlspecialchars(\Biorrhythms\DateFormatter::long($focusDate)) ?>">
                        <strong><?= $focusDate->format('j') ?></strong>
                        <span><?= htmlspecialchars($readingMonths[(int) $focusDate->format('n')] . ' ' . $focusDate->format('Y')) ?></span>
                        <small><?= htmlspecialchars($readingWeekdaysShort[(int) $focusDate->format('N')]) ?></small>
                    </a>
                    <a class="date-rail-item" id="dateRailNext" href="<?= htmlspecialchars($readingDateLink($nextDayDate->format('Y-m-d'))) ?>" aria-label="Día siguiente: <?= htmlspecialchars(\Biorrhythms\DateFormatter::long($nextDayDate)) ?>">
                        <strong><?= $nextDayDate->format('j') ?></strong>
                        <span><?= htmlspecialchars($readingMonths[(int) $nextDayDate->format('n')] . ' ' . $nextDayDate->format('Y')) ?></span>
                        <small><?= htmlspecialchars($readingWeekdaysShort[(int) $nextDayDate->format('N')]) ?></small>
                    </a>
                </nav>
                <article class="card hero-main">
                    <div class="reading-heading">
                        <div>
                            <p class="reading-weekday" id="readingWeekday"><?= htmlspecialchars($readingWeekday) ?></p>
                            <h1 id="readingDateHeading"><?= htmlspecialchars($readingDateLabel) ?></h1>
                        </div>
                        <p class="calendar-day-stamp" id="calendarDayStamp">Día <?= $readingDayOfYear ?> de <?= $readingYearLength ?></p>
                    </div>
                <div class="model-values-heading">
                        <h2>Valores del modelo</h2>
                    <p>Porcentajes calculados por un modelo matemático; no miden energía ni predicen resultados.</p>
                    </div>
                    <div class="hero-stats-inline" aria-label="Valores calculados para el día seleccionado">
                        <div class="stat stat-physical">
                            <small>Físico</small>
                            <strong id="stat-physical"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['physical'])) ?></strong>
                            <span>Ciclo de 23 días</span>
                        </div>
                        <div class="stat stat-emotional">
                            <small>Emocional</small>
                            <strong id="stat-emotional"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['emotional'])) ?></strong>
                            <span>Ciclo de 28 días</span>
                        </div>
                        <div class="stat stat-intellectual">
                            <small>Intelectual</small>
                            <strong id="stat-intellectual"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['intellectual'])) ?></strong>
                            <span>Ciclo de 33 días</span>
                        </div>
                    </div>
                <section class="chart-wrap" aria-labelledby="chartTitle">
                    <div class="panel-head">
                        <div>
                            <h2 id="chartTitle">Ciclo de 90 días</h2>
                        </div>
                        <div class="button-row" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <div class="zoom-btns">
                                <button type="button" class="zoom-btn" data-zoom="7">7 días</button>
                                <button type="button" class="zoom-btn" data-zoom="30">30 días</button>
                                <button type="button" class="zoom-btn" data-zoom="60">60 días</button>
                                <button type="button" class="zoom-btn is-active" data-zoom="91">90 días</button>
                            </div>
                            <button id="exportPngBtn" type="button">Exportar PNG</button>
                            <button id="jumpToTodayBtn" type="button" class="secondary-btn">Volver al día foco</button>
                        </div>
                    </div>
                    <svg class="chart" viewBox="0 0 1100 315" role="img" aria-label="Gráfico de líneas de los ritmos físico, emocional e intelectual a lo largo del tiempo. La línea central representa cero; por encima, valores positivos; por debajo, valores negativos.">
                        <defs>
                            <linearGradient id="bgFade" x1="0" x2="1">
                                <stop offset="0%" stop-color="rgba(255,255,255,0.05)"></stop>
                                <stop offset="100%" stop-color="rgba(255,255,255,0.01)"></stop>
                            </linearGradient>
                        </defs>
                        <rect x="0" y="0" width="1100" height="315" rx="0" fill="none"></rect>
                        <g id="grid"></g>
                        <g id="paths"></g>
                        <g id="markers"></g>
                    </svg>
                    <div class="legend">
                        <span><i class="dot" style="background: var(--physical)"></i> Físico</span>
                        <span><i class="dot" style="background: var(--emotional)"></i> Emocional</span>
                        <span><i class="dot" style="background: var(--intellectual)"></i> Intelectual</span>
                    </div>
                </section>
                <section class="reflection-note" aria-labelledby="reflectionTitle">
                    <h2 id="reflectionTitle">Sugerencia para observar</h2>
                    <p>¿Qué notas hoy, más allá de los porcentajes?</p>
                </section>
                <div class="controls-bar">
                    <form method="get" id="focusDateForm" class="date-control-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>#lectura">
                        <input type="hidden" name="preset" id="compatPresetInput" value="<?= htmlspecialchars($compatPresetInput) ?>">
                        <input type="hidden" name="birth" value="<?= htmlspecialchars($birthInput) ?>">
                        <input type="hidden" name="partner_birth" value="<?= htmlspecialchars($partnerBirthInput) ?>">
<?php foreach ($preservedQueryParams as $queryName => $queryValue): ?>
                        <input type="hidden" name="<?= htmlspecialchars((string) $queryName, ENT_QUOTES) ?>" value="<?= htmlspecialchars((string) $queryValue, ENT_QUOTES) ?>">
<?php endforeach; ?>
                        <label for="focusDateInput">Día de lectura
                            <input id="focusDateInput" type="date" name="focus" value="<?= htmlspecialchars($focusInput) ?>" min="<?= DateInput::MIN ?>" max="<?= DateInput::MAX ?>" required>
                        </label>
                        <button type="submit" class="bar-submit">Abrir día</button>
                    </form>
                    <div class="reading-date-stepper" aria-label="Cambiar día de lectura">
                        <button id="previousReadingDay" type="button" class="secondary-btn" aria-label="Día anterior">←</button>
                        <button id="nextReadingDay" type="button" class="secondary-btn" aria-label="Día siguiente">→</button>
                        <button id="todayReadingDay" type="button" class="secondary-btn">Hoy</button>
                    </div>
                    <div class="bar-slider">
                        <label for="windowSlider">Recorrer fechas cercanas</label>
                        <input id="windowSlider" type="range" min="0" max="<?= (int) (count($window) - 1) ?>" value="<?= $selectedIndex ?>" aria-describedby="selectedLabel">
                        <div class="footer-note" id="selectedLabel" aria-live="polite">
                            <?= htmlspecialchars($selectedPoint['label']) ?>
                        </div>
                    </div>
                </div>
                <p class="disclaimer">
                    Los biorritmos son un modelo clásico sin validez predictiva demostrada (Hines, 1998, <em>Psychological Reports</em> 83).
                    Úsalos como marco de reflexión y autoobservación, no para decisiones médicas, financieras o legales.
                </p>
                </article>
            </div>
        </section>
<?php endif; ?>
        </section>
