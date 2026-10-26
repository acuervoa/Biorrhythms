<?php

use Biorrhythms\DateInput;

if (!defined('BIORRHYTHMS_ENTRYPOINT')) {
    http_response_code(404);
    exit;
}
?>
        <section class="section card extreme-card" id="diary">
            <div class="extreme-head">
                <div>
                    <h2>Diario personal</h2>
                    <p>Registra cómo te encuentras. Tus notas se guardan únicamente en este navegador y no se envían al servidor.</p>
                </div>
                <div class="status-pill" id="diaryCount">0 registros</div>
            </div>
            <form class="diary-form" id="diaryForm" autocomplete="off">
                <label>Día
                    <input type="date" id="diaryDate" value="<?= htmlspecialchars($focusInput) ?>" min="<?= DateInput::MIN ?>" max="<?= DateInput::MAX ?>" required>
                </label>
                <label>Energía física
                    <select id="diaryEnergy"><?php foreach ([1, 2, 3, 4, 5] as $n): ?><option value="<?= $n ?>"<?= $n === 3 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select>
                </label>
                <label>Estado de ánimo
                    <select id="diaryMood"><?php foreach ([1, 2, 3, 4, 5] as $n): ?><option value="<?= $n ?>"<?= $n === 3 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select>
                </label>
                <label>Claridad mental
                    <select id="diaryFocus"><?php foreach ([1, 2, 3, 4, 5] as $n): ?><option value="<?= $n ?>"<?= $n === 3 ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select>
                </label>
                <button type="submit" class="bar-submit">Guardar día</button>
            </form>
            <div class="footer-note" id="diaryStatus" role="status"></div>
<?php if ($birthIsExample): ?>
            <p class="diary-caveat">Estás usando la fecha de ejemplo (<?= htmlspecialchars($defaultBirth) ?>): la correlación solo tiene sentido con tu fecha real.</p>
<?php endif; ?>
            <div class="diary-table-scroll" role="region" aria-label="Resultados de correlación" tabindex="0">
            <table class="diary-table" id="diaryTable">
                <thead><tr><th>Curva ↔ lo que anotas</th><th>Registros</th><th>Correlación (r)</th><th>IC 95%</th><th>Lectura</th></tr></thead>
                <tbody id="diaryRows"></tbody>
            </table>
            </div>
            <p class="diary-table-hint">Desliza la tabla para consultar todas las columnas.</p>
            <p class="diary-caveat">
                La tabla compara tus registros con los valores del modelo; no demuestra causa ni capacidad predictiva. Se necesitan al menos 14 registros por ritmo. Los días consecutivos no son independientes,
                así que el intervalo es optimista. Con pocos datos, el resultado es sobre todo ruido: «sin relación apreciable» es un resultado válido.
            </p>
            <div class="diary-data-section">
                <h3>Gestionar mis datos</h3>
                <p class="diary-caveat">Puedes exportarlos o importar una copia en este navegador. «Olvidar mis datos» elimina el diario local y no se puede deshacer.</p>
            <div class="diary-actions">
                <button type="button" class="secondary-btn" id="diaryExport">Exportar JSON</button>
                <label class="secondary-btn" style="cursor:pointer">Importar JSON
                    <input type="file" id="diaryImport" accept="application/json,.json" hidden>
                </label>
                <button type="button" class="secondary-btn" id="diaryForget">Olvidar mis datos</button>
            </div>
            </div>
        </section>
