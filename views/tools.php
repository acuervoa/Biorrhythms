<?php

use Biorrhythms\DateInput;
use Biorrhythms\Numerology;

if (!defined('BIORRHYTHMS_ENTRYPOINT')) {
    http_response_code(404);
    exit;
}
?>
        <h2 class="tools-group-heading">Comparar ciclos</h2>
        <section class="section card compat-card" id="compatibility">
            <div class="compat-head">
                <div>
                    <h2>Solapamiento de patrones</h2>
                    <p>Compara los valores matemáticos de dos perfiles para explorar cómo varían en el tiempo.</p>
                </div>
                <div class="status-pill" id="compatSummaryPill">Listo para comparar</div>
            </div>
            <div class="compat-grid">
                <div class="compat-score">
                    <small>Solapamiento matemático entre ciclos</small>
                    <strong id="compatScore"><?= htmlspecialchars(number_format($focusCompatibility * 100, 1, '.', '')) ?>%</strong>
                    <p id="compatNarrative">
                        <?= htmlspecialchars('Se calculó una comparación inicial usando ' . $focusInput . ' y ' . $partnerBirthInput . '.') ?> No evalúa compatibilidad interpersonal.
                    </p>
                    <div class="compat-inputs">
                        <label>
                            Otra persona
                            <input id="partnerBirthInput" type="date" value="<?= htmlspecialchars($partnerBirthInput) ?>">
                        </label>
                    </div>
                    <div class="compat-export-row">
                        <button id="exportCompatBtn" type="button" class="secondary-btn">Exportar compatibilidad</button>
                    </div>
                </div>
                <div>
                    <div class="compat-strip" id="compatStrip" role="region" aria-label="Comparación diaria desplazable" tabindex="0"></div>
                    <details class="tools-disclosure compat-heatmap-disclosure">
                        <summary>Ver comparación semanal (7 días)</summary>
                        <div class="compat-heatmap">
                            <div class="compat-heatmap-grid" id="compatHeatmap" role="region" aria-label="Comparación semanal desplazable" tabindex="0"></div>
                        </div>
                    </details>
                    <div class="compat-footer" id="compatFooter"></div>
                </div>
            </div>
        </section>

        <h2 class="tools-group-heading">Contexto simbólico</h2>
        <section class="section card extreme-card" id="context">
            <div class="extreme-head">
                <div>
                    <h2>Contexto del día</h2>
                    <p>Marco simbólico para la fecha de foco (<?= htmlspecialchars($focusInput) ?>). La astrología y la numerología no son evidencia empírica ni predicciones.</p>
                </div>
            </div>
            <div class="context-grid">
                <div class="context-item">
                    <small>Signo solar</small>
                    <strong><?= htmlspecialchars($sunSign['name']) ?></strong>
                    <p><?= htmlspecialchars($sunSign['element'] . ' · ' . $sunSign['modality']) ?><?= $sunSign['cusp'] ? ' · cúspide: el cambio de signo varía ±1 día según el año' : '' ?></p>
                </div>
                <div class="context-item">
                    <small>Camino de vida</small>
                    <strong><?= (int) $lifePath ?> · <?= htmlspecialchars($lifePathInfo['name']) ?></strong>
                    <p><?= htmlspecialchars($lifePathInfo['keywords']) ?></p>
                </div>
                <div class="context-item">
                    <small>Día personal</small>
                    <strong><?= (int) $personal['day'] ?> · <?= htmlspecialchars(Numerology::meaning($personal['day'])['name']) ?></strong>
                    <p>Mes <?= (int) $personal['month'] ?> · año <?= (int) $personal['year'] ?>. <?= htmlspecialchars(Numerology::meaning($personal['day'])['keywords']) ?></p>
                </div>
                <div class="context-item">
                    <small>Fase lunar</small>
                    <strong><?= htmlspecialchars($moon['name']) ?></strong>
                    <p>Iluminación ≈ <?= (int) round($moon['illumination'] * 100) ?>% · edad ≈ <?= number_format($moon['age_days'], 1, '.', '') ?> días (precisión de ±1 día).</p>
                </div>
            </div>
        </section>

        <h2 class="tools-group-heading">Cronotipo y horario</h2>
        <section class="section card extreme-card" id="chrono">
            <div class="extreme-head">
                <div>
                    <h2>Cronotipo y ritmo del día</h2>
                    <p>Plantilla orientativa a partir de tu hora de despertar, y un cuestionario corto de cronotipo.</p>
                </div>
                <div class="status-pill" id="chronoPill">Sin datos</div>
            </div>
            <form class="diary-form" id="wakeForm" autocomplete="off">
                <label>Hora a la que te despiertas
                    <input type="time" id="chronoWake" required>
                </label>
                <button type="submit" class="bar-submit">Ver mi día</button>
            </form>
            <div id="chronoSchedule"></div>
            <h3 style="margin:22px 0 8px;font-size:1rem">Cuestionario de cronotipo</h3>
            <form id="chronoForm" autocomplete="off"></form>
            <div class="footer-note" id="chronoStatus" role="status"></div>
            <div id="chronoResult"></div>
            <p class="diary-caveat">
                Las horas son reglas generales (luz por la mañana, valle tras la comida unas 8 h después de despertar, café hasta unas 8 h antes de dormir),
                no mediciones de tu reloj biológico. El cuestionario es una adaptación propia inspirada en el rMEQ (Adan y Almirall, 1991):
                mantiene su rango (4–25) y sus tres grupos, pero no es el instrumento validado ni un diagnóstico.
                Los bloques de 90 min parten del ciclo básico reposo-actividad; la evidencia de que sea el ritmo ideal de trabajo es limitada.
            </p>
        </section>

        <details class="tools-disclosure" id="prefs">
            <summary>Perfil y preferencias</summary>
            <section class="section card extreme-card">
            <div class="extreme-head">
                <div>
                    <h2>Avisos y datos guardados</h2>
                    <p>Todo se guarda solo en este navegador; no hay cuenta ni servidor.</p>
                </div>
            </div>
            <form method="get" id="profileBirthForm" class="diary-form" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>#herramientas">
                <label for="profileBirthInput">Fecha de nacimiento
                    <input id="profileBirthInput" type="date" name="birth" value="<?= htmlspecialchars($birthInput) ?>" min="<?= DateInput::MIN ?>" max="<?= DateInput::MAX ?>" required>
                </label>
                <input type="hidden" name="focus" id="profileFocusInput" value="<?= htmlspecialchars($focusInput) ?>">
                <input type="hidden" name="partner_birth" value="<?= htmlspecialchars($partnerBirthInput) ?>">
                <input type="hidden" name="preset" value="<?= htmlspecialchars($compatPresetInput) ?>">
<?php foreach ($preservedQueryParams as $queryName => $queryValue): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $queryName, ENT_QUOTES) ?>" value="<?= htmlspecialchars((string) $queryValue, ENT_QUOTES) ?>">
<?php endforeach; ?>
                <button type="submit" class="bar-submit">Guardar fecha</button>
            </form>
            <div class="diary-actions">
                <button type="button" class="secondary-btn" id="notifyToggle">Activar avisos de días críticos</button>
                <button type="button" class="secondary-btn" id="forgetProfile" hidden>Olvidar mi fecha y preferencias</button>
            </div>
            <div class="footer-note" id="prefsStatus" role="status"></div>
            <p class="diary-caveat">
                El aviso aparece al abrir la app el día crítico o el anterior. No hay servidor que envíe notificaciones en segundo plano,
                así que si no abres la app, no se avisa.
            </p>
            </section>
        </details>

        <h2 class="tools-group-heading">Compartir e integrar</h2>
        <details class="tools-disclosure integration-disclosure">
            <summary>Tarjeta compartible</summary>
        <section class="section card share-card" id="share">
            <div class="share-preview" id="sharePreview">
                <div class="share-badge">Tarjeta para compartir</div>
                <h2 class="share-title" id="shareTitle"><?= htmlspecialchars($profileLabel) ?></h2>
                <p class="share-subtitle" id="shareSubtitle">
                    <?= htmlspecialchars($selectedPoint['label']) ?> · nacimiento <?= htmlspecialchars($birthInput) ?> · foco <?= htmlspecialchars($focusInput) ?>
                </p>
                <div class="share-metrics">
                    <div class="share-metric">
                        <small>Físico</small>
                        <strong id="sharePhysical"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['physical'])) ?></strong>
                    </div>
                    <div class="share-metric">
                        <small>Emocional</small>
                        <strong id="shareEmotional"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['emotional'])) ?></strong>
                    </div>
                    <div class="share-metric">
                        <small>Intelectual</small>
                        <strong id="shareIntellectual"><?= htmlspecialchars(valueToSignedPercent($selectedPoint['intellectual'])) ?></strong>
                    </div>
                </div>
                <div class="status-pill" id="shareStatus"><?= htmlspecialchars($focusSummary) ?></div>
            </div>
            <div class="share-side">
                <div class="panel-head" style="margin:0;">
                    <div>
                        <h2>Tarjeta compartible</h2>
                        <p>Lista para exportar como PNG sin instalar nada más.</p>
                    </div>
                </div>
                <div class="button-row">
                    <button id="copySummaryBtn" type="button" class="secondary-btn">Copiar resumen</button>
                    <button id="copyLinkBtn" type="button" class="secondary-btn">Copiar enlace</button>
                </div>
                <div class="footer-note">
                    Exporta los valores seleccionados de los tres ritmos. La tarjeta es informativa, no predictiva.
                </div>
            </div>
        </section>
        </details>

        <section class="section card widget-card" id="widget">
            <details class="widget-details" id="widgetDetails"<?= $embedMode ? ' open' : '' ?>>
            <summary>Ver widget y código para insertar</summary>
            <div class="widget-head">
                <div>
                    <h2>Widget embebible</h2>
                    <p>Puedes insertarlo en un README, una web personal o un dashboard interno.</p>
                </div>
                <div class="status-pill">iframe listo</div>
            </div>
            <div class="widget-grid">
                <article class="widget-panel">
                    <div class="widget-preview-mini">
                        <div class="share-badge">Widget</div>
                        <strong><?= htmlspecialchars($profileLabel) ?></strong>
                        <p><?= htmlspecialchars($selectedPoint['label']) ?> · nacimiento <?= htmlspecialchars($birthInput) ?> · foco <?= htmlspecialchars($focusInput) ?></p>
                    </div>
                    <div class="widget-chart-shell">
                        <div class="widget-chart-title">
                            <strong>Curvas centradas en hoy</strong>
                            <span>31 días · detalle al pasar el puntero</span>
                        </div>
                        <svg id="widgetChart" class="widget-chart" viewBox="0 0 640 190" role="img" aria-label="Curvas de biorritmos centradas en hoy">
                            <g id="widgetChartGrid"></g>
                            <g id="widgetChartPaths"></g>
                            <g id="widgetChartMarkers"></g>
                        </svg>
                        <p class="chart-text-alternative">El gráfico compara los tres ritmos durante 31 días. Sus valores del día de foco aparecen en las tarjetas que siguen.</p>
                        <details class="widget-data-details">
                            <summary>Consultar los valores diarios del gráfico</summary>
                            <table class="widget-data-table">
                                <caption>Valores diarios de los ritmos durante los 31 días centrados en hoy</caption>
                                <thead>
                                    <tr><th scope="col">Día</th><th scope="col">Físico</th><th scope="col">Emocional</th><th scope="col">Intelectual</th></tr>
                                </thead>
                                <tbody id="widgetChartData"></tbody>
                            </table>
                        </details>
                        <div id="widgetChartTooltip" class="widget-chart-tooltip" aria-hidden="true"></div>
                    </div>
                    <div class="widget-mini-stats">
                        <div class="widget-mini-stat">
                            <small>Físico</small>
                            <strong><?= htmlspecialchars(valueToPercent($selectedPoint['physical'])) ?></strong>
                        </div>
                        <div class="widget-mini-stat">
                            <small>Emocional</small>
                            <strong><?= htmlspecialchars(valueToPercent($selectedPoint['emotional'])) ?></strong>
                        </div>
                        <div class="widget-mini-stat">
                            <small>Intelectual</small>
                            <strong><?= htmlspecialchars(valueToPercent($selectedPoint['intellectual'])) ?></strong>
                        </div>
                    </div>
                    <div class="widget-note">
                        <?= htmlspecialchars('Incluye el estado de la URL, así que el widget se puede compartir y rehidratar con la misma lectura.') ?>
                    </div>
                </article>
                <div class="widget-panel">
                    <div class="widget-code">
                        <small>Snippet</small>
                        <textarea id="widgetSnippet" readonly><?= htmlspecialchars($widgetSnippet) ?></textarea>
                        <div class="button-row">
                            <button id="copyWidgetBtn" type="button" class="secondary-btn">Copiar iframe</button>
                            <a id="openWidgetBtn" href="<?= htmlspecialchars($widgetUrl) ?>" target="_blank" rel="noopener" class="secondary-btn">Abrir widget</a>
                        </div>
                    </div>
                    <iframe
                        id="widgetFrame"
                        class="widget-frame"
                        title="Biorrhythms widget preview"
                        loading="lazy"
                        src="<?= htmlspecialchars($widgetPreviewUrl) ?>"
                    ></iframe>
                </div>
            </div>
            </details>
        </section>

        <details class="tools-disclosure integration-disclosure">
            <summary>Usar la API</summary>
        <section class="section card api-card" id="api">
            <div class="api-head">
                <div>
                    <h2>API pública</h2>
                    <p>JSON listo para integrar en scripts, dashboards o automatizaciones.</p>
                </div>
                <div class="status-pill" id="apiStatus">Sin consultar</div>
            </div>
            <div class="api-grid">
                <article class="api-panel">
                    <small>Endpoint</small>
                    <strong>/api/</strong>
                    <p>Devuelve el perfil actual, el forecast de 7 días, la compatibilidad y los enlaces canónicos en JSON.</p>
                    <div class="api-snippet">
                        <small>curl</small>
                        <textarea id="apiSnippet" readonly><?= htmlspecialchars($apiSnippet) ?></textarea>
                        <div class="button-row">
                            <button id="copyApiBtn" type="button" class="secondary-btn">Copiar curl</button>
                            <a id="openApiBtn" href="<?= htmlspecialchars($apiUrl) ?>" target="_blank" rel="noopener" class="secondary-btn">Abrir JSON</a>
                        </div>
                    </div>
                </article>
                <article class="api-panel">
                    <small>Preview JSON</small>
                    <pre id="apiPreview" class="api-preview">Cargando preview...</pre>
                    <button class="api-preview-toggle" id="apiPreviewToggle" onclick="(function(){const p=document.getElementById('apiPreview'),b=document.getElementById('apiPreviewToggle');p.classList.toggle('expanded');b.textContent=p.classList.contains('expanded')?'▲ Ocultar resto':'▼ Ver completo';})()">▼ Ver completo</button>
                </article>
            </div>
        </section>
        </details>
