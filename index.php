<?php

declare(strict_types=1);

require_once __DIR__ . '/src/BiorrhythmsProduct.php';
require_once __DIR__ . '/src/BiorrhythmsProductApp.php';

$context = \Biorrhythms\BiorrhythmsProductApp::buildContext($_GET);
$selectedPoint = $context['selectedPoint'];
$decision = $context['decision'];
$ritual = $context['ritual'];
$widgetSnippet = $context['snippets']['widget'];
$apiSnippet = $context['snippets']['api'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biorrhythms Product</title>
    <style>
        :root {
            --bg: #050914;
            --panel: rgba(11, 19, 34, 0.76);
            --panel-strong: rgba(14, 24, 42, 0.94);
            --stroke: rgba(255, 255, 255, 0.12);
            --text: #ecf2ff;
            --muted: #9fb2d3;
            --accent: #f8c95d;
            --accent-2: #7dd3fc;
            --accent-3: #6ee7b7;
            --physical: #ff7b54;
            --emotional: #6ee7b7;
            --intellectual: #7dd3fc;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(248, 201, 93, 0.16), transparent 28%),
                radial-gradient(circle at top right, rgba(125, 211, 252, 0.16), transparent 26%),
                radial-gradient(circle at bottom center, rgba(110, 231, 183, 0.12), transparent 24%),
                linear-gradient(160deg, #040712 0%, #08101c 42%, #0d1728 100%);
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
        }

        a { color: inherit; text-decoration: none; }

        .shell {
            width: min(1240px, calc(100% - 32px));
            margin: 24px auto 56px;
        }

        .topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(248, 201, 93, 0.18);
            background: rgba(248, 201, 93, 0.08);
            color: #ffe7a8;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav a,
        .nav button,
        .secondary-btn,
        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 14px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .primary-btn {
            background: linear-gradient(135deg, #f8c95d, #ffb36f);
            color: #06111f;
            border-color: transparent;
        }

        .hero {
            display: grid;
            gap: 18px;
            grid-template-columns: 1.2fr 0.8fr;
        }

        .card {
            padding: 22px;
            border-radius: 24px;
            border: 1px solid var(--stroke);
            background: linear-gradient(180deg, var(--panel), var(--panel-strong));
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
            backdrop-filter: blur(14px);
        }

        .hero-copy h1 {
            margin: 14px 0 10px;
            font-size: clamp(2.4rem, 5vw, 4.8rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .lead {
            margin: 0;
            color: var(--muted);
            max-width: 62ch;
            line-height: 1.65;
            font-size: 1.05rem;
        }

        .chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font-size: 0.82rem;
        }

        .chip strong {
            color: #ffe7a8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.74rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-side {
            display: grid;
            gap: 18px;
        }

        .control-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .control-grid label {
            display: grid;
            gap: 8px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .control-grid input,
        .control-grid select,
        textarea {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            font: inherit;
        }

        .stats-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .stat {
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .stat small {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .stat strong {
            display: block;
            font-size: 1.6rem;
            line-height: 1;
        }

        .stat span {
            display: block;
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .section {
            margin-top: 18px;
        }

        .section-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .section-head p {
            margin: 0;
            color: var(--muted);
        }

        .grid-2 {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-3 {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 10px;
            line-height: 1.55;
        }

        .list li::marker { color: #f8c95d; }

        .panel {
            display: grid;
            gap: 14px;
            padding: 18px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
        }

        .panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .panel h3 {
            margin: 0;
            font-size: 1.15rem;
        }

        .pill {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(248, 201, 93, 0.08);
            border: 1px solid rgba(248, 201, 93, 0.16);
            color: #ffe7a8;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .forecast-strip,
        .compat-strip {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .day-card {
            display: grid;
            gap: 8px;
            padding: 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            min-height: 130px;
        }

        .day-card strong {
            font-size: 0.94rem;
        }

        .day-card span {
            color: var(--muted);
            font-size: 0.78rem;
        }

        .meter {
            margin-top: auto;
            display: grid;
            gap: 6px;
        }

        .track {
            position: relative;
            height: 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .fill {
            position: absolute;
            inset: 0 auto 0 0;
            border-radius: inherit;
        }

        .physical { background: var(--physical); }
        .emotional { background: var(--emotional); }
        .intellectual { background: var(--intellectual); }

        .metric-row {
            display: grid;
            gap: 10px;
        }

        .metric {
            display: grid;
            gap: 6px;
            grid-template-columns: 64px 1fr 62px;
            align-items: center;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .metric .track { height: 8px; }

        .metric strong {
            text-align: right;
            font-size: 0.8rem;
            color: var(--text);
        }

        .best {
            border-color: rgba(248, 201, 93, 0.5);
        }

        .worst {
            border-color: rgba(255, 123, 84, 0.5);
        }

        .snippet-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        textarea {
            min-height: 128px;
            resize: vertical;
            font: 0.84rem/1.5 "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .note {
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid rgba(248, 201, 93, 0.16);
            background: rgba(248, 201, 93, 0.08);
            color: #ffe7a8;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .compact {
            color: var(--muted);
            line-height: 1.55;
        }

        @media (max-width: 1020px) {
            .hero,
            .grid-2,
            .grid-3,
            .snippet-grid {
                grid-template-columns: 1fr;
            }

            .forecast-strip,
            .compat-strip,
            .stats-grid,
            .control-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div class="brand">Biorrhythms Product</div>
            <nav class="nav">
                <a href="/demo/?birth=<?= htmlspecialchars($context['birthInput']) ?>&focus=<?= htmlspecialchars($context['focusInput']) ?>&partner_birth=<?= htmlspecialchars($context['partnerBirthInput']) ?><?= $context['compatPresetInput'] !== 'custom' ? '&preset=' . htmlspecialchars($context['compatPresetInput']) : '' ?>">Demo</a>
                <a href="/api/?birth=<?= htmlspecialchars($context['birthInput']) ?>&focus=<?= htmlspecialchars($context['focusInput']) ?>&partner_birth=<?= htmlspecialchars($context['partnerBirthInput']) ?><?= $context['compatPresetInput'] !== 'custom' ? '&preset=' . htmlspecialchars($context['compatPresetInput']) : '' ?>&pretty=1">API</a>
                <a href="#widget">Widget</a>
                <a href="#ritual">Ritual</a>
            </nav>
        </header>

        <section class="hero">
            <article class="card hero-copy">
                <div class="pill">Producto server-rendered</div>
                <h1>Biorrhythms, pero como app.</h1>
                <p class="lead">
                    Una capa de producto ligera para lectura diaria, ritual y enlaces compartibles. La demo conserva
                    el forecast, la compatibilidad y las escenas visuales más pesadas.
                </p>
                <div class="chip-row">
                    <span class="chip"><strong>Daily</strong> foco accionable de hoy</span>
                    <span class="chip"><strong>Share</strong> widget, demo y JSON</span>
                    <span class="chip"><strong>Simple</strong> PHP server-rendered</span>
                </div>
                <div class="hero-actions">
                    <a class="primary-btn" href="/demo/">Ver demo visual</a>
                    <a class="secondary-btn" href="/api/?birth=<?= htmlspecialchars($context['birthInput']) ?>&focus=<?= htmlspecialchars($context['focusInput']) ?>&partner_birth=<?= htmlspecialchars($context['partnerBirthInput']) ?><?= $context['compatPresetInput'] !== 'custom' ? '&preset=' . htmlspecialchars($context['compatPresetInput']) : '' ?>&pretty=1">Abrir API</a>
                </div>
            </article>

            <aside class="hero-side">
                <section class="card">
                    <div class="section-head" style="margin-bottom: 12px;">
                        <div>
                            <h2>Configura tu lectura</h2>
                            <p>Sin autenticación, sin base de datos.</p>
                        </div>
                    </div>
                    <form method="get">
                        <div class="control-grid">
                            <label>
                                Nacimiento
                                <input type="date" name="birth" value="<?= htmlspecialchars($context['birthInput']) ?>">
                            </label>
                            <label>
                                Foco
                                <input type="date" name="focus" value="<?= htmlspecialchars($context['focusInput']) ?>">
                            </label>
                            <label>
                                Compatibilidad
                                <select name="preset">
                                    <option value="custom"<?= $context['compatPresetInput'] === 'custom' ? ' selected' : '' ?>>Personalizado</option>
                                    <option value="pair"<?= $context['compatPresetInput'] === 'pair' ? ' selected' : '' ?>>Pareja</option>
                                    <option value="friend"<?= $context['compatPresetInput'] === 'friend' ? ' selected' : '' ?>>Amistad</option>
                                    <option value="work"<?= $context['compatPresetInput'] === 'work' ? ' selected' : '' ?>>Trabajo</option>
                                </select>
                            </label>
                            <label>
                                Persona 2
                                <input type="date" name="partner_birth" value="<?= htmlspecialchars($context['partnerBirthInput']) ?>">
                            </label>
                        </div>
                        <div class="hero-actions" style="margin-top: 14px;">
                            <button class="primary-btn" type="submit">Actualizar</button>
                            <a class="secondary-btn" href="/?birth=<?= htmlspecialchars($context['birthInput']) ?>&focus=<?= htmlspecialchars($context['focusInput']) ?>&partner_birth=<?= htmlspecialchars($context['partnerBirthInput']) ?><?= $context['compatPresetInput'] !== 'custom' ? '&preset=' . htmlspecialchars($context['compatPresetInput']) : '' ?>">Compartir vista</a>
                        </div>
                    </form>
                </section>

                <section class="card">
                    <div class="stats-grid">
                        <div class="stat">
                            <small>Físico</small>
                            <strong><?= number_format($selectedPoint['physical'] * 100, 1, '.', '') ?>%</strong>
                            <span>Periodo 23 días</span>
                        </div>
                        <div class="stat">
                            <small>Emocional</small>
                            <strong><?= number_format($selectedPoint['emotional'] * 100, 1, '.', '') ?>%</strong>
                            <span>Periodo 28 días</span>
                        </div>
                        <div class="stat">
                            <small>Intelectual</small>
                            <strong><?= number_format($selectedPoint['intellectual'] * 100, 1, '.', '') ?>%</strong>
                            <span>Periodo 33 días</span>
                        </div>
                    </div>
                </section>
            </aside>
        </section>

        <section class="section grid-2">
            <article class="card panel">
                <div class="section-head">
                    <div>
                        <h2>Lectura de hoy</h2>
                        <p><?= htmlspecialchars($context['selectedPoint']['label']) ?> · foco <?= htmlspecialchars($context['focusInput']) ?></p>
                    </div>
                    <div class="pill"><?= htmlspecialchars($decision['badge']) ?></div>
                </div>
                <h3><?= htmlspecialchars($decision['title']) ?></h3>
                <p class="compact"><?= htmlspecialchars($decision['why']) ?></p>
                <div class="metric-row">
                    <div class="metric">
                        <span>Físico</span>
                        <div class="track"><div class="fill physical" style="width: <?= max(6, round(($selectedPoint['physical'] + 1) * 50)) ?>%;"></div></div>
                        <strong><?= number_format($selectedPoint['physical'] * 100, 1, '.', '') ?>%</strong>
                    </div>
                    <div class="metric">
                        <span>Emocional</span>
                        <div class="track"><div class="fill emotional" style="width: <?= max(6, round(($selectedPoint['emotional'] + 1) * 50)) ?>%;"></div></div>
                        <strong><?= number_format($selectedPoint['emotional'] * 100, 1, '.', '') ?>%</strong>
                    </div>
                    <div class="metric">
                        <span>Intelectual</span>
                        <div class="track"><div class="fill intellectual" style="width: <?= max(6, round(($selectedPoint['intellectual'] + 1) * 50)) ?>%;"></div></div>
                        <strong><?= number_format($selectedPoint['intellectual'] * 100, 1, '.', '') ?>%</strong>
                    </div>
                </div>
            </article>

            <article class="card panel" id="ritual">
                <div class="section-head">
                    <div>
                        <h2>Ritual diario</h2>
                        <p>Convierte la lectura en una rutina concreta.</p>
                    </div>
                    <div class="pill"><?= htmlspecialchars($ritual['badge']) ?></div>
                </div>
                <h3><?= htmlspecialchars($ritual['focus']) ?></h3>
                <p class="compact"><?= htmlspecialchars($ritual['why']) ?></p>
                <ol class="list">
                    <?php foreach ($ritual['lines'] as $line): ?>
                        <li><?= htmlspecialchars($line) ?></li>
                    <?php endforeach; ?>
                </ol>
                <div class="chip-row">
                    <?php foreach ($ritual['tags'] as $tag): ?>
                        <span class="chip"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="note"><?= htmlspecialchars($ritual['note']) ?></div>
            </article>
        </section>

        <section class="section grid-2">
            <article class="card panel">
                <div class="section-head">
                    <div>
                        <h2>Producto resumido</h2>
                        <p>La interfaz principal se centra en leer, decidir y compartir.</p>
                    </div>
                    <div class="pill"><?= htmlspecialchars($decision['badge']) ?></div>
                </div>
                <h3><?= htmlspecialchars($decision['title']) ?></h3>
                <p class="compact"><?= htmlspecialchars($decision['why']) ?></p>
                <ul class="list">
                    <li>Entrada corta para revisar el día actual.</li>
                    <li>Ritual diario para convertir la lectura en acción.</li>
                    <li>Acceso directo a demo, widget y JSON.</li>
                </ul>
                <div class="hero-actions">
                    <a class="primary-btn" href="/demo/">Ir a la demo visual</a>
                    <a class="secondary-btn" href="<?= htmlspecialchars($context['links']['api']) ?>" target="_blank" rel="noopener">Ver JSON</a>
                </div>
            </article>

            <article class="card panel" id="widget">
                <div class="section-head">
                    <div>
                        <h2>Integraciones</h2>
                        <p>Widget embebible, API pública y demo como escaparate.</p>
                    </div>
                    <div class="pill">shareable</div>
                </div>

                <div class="snippet-grid">
                    <div>
                        <h3>Widget</h3>
                        <textarea id="widgetSnippet" readonly><?= htmlspecialchars($widgetSnippet) ?></textarea>
                        <div class="hero-actions">
                            <button type="button" class="secondary-btn" data-copy="widgetSnippet">Copiar iframe</button>
                            <a class="secondary-btn" href="<?= htmlspecialchars($context['links']['widget']) ?>" target="_blank" rel="noopener">Abrir widget</a>
                        </div>
                    </div>
                    <div>
                        <h3>API</h3>
                        <textarea id="apiSnippet" readonly><?= htmlspecialchars($apiSnippet) ?></textarea>
                        <div class="hero-actions">
                            <button type="button" class="secondary-btn" data-copy="apiSnippet">Copiar curl</button>
                            <a class="secondary-btn" href="<?= htmlspecialchars($context['links']['api']) ?>" target="_blank" rel="noopener">Abrir JSON</a>
                        </div>
                    </div>
                </div>

                <div class="hero-actions" style="margin-top: 16px;">
                    <a class="primary-btn" href="<?= htmlspecialchars($context['links']['demo']) ?>" target="_blank" rel="noopener">Abrir demo visual</a>
                    <a class="secondary-btn" href="/demo/">Ir al showcase</a>
                </div>
            </article>
        </section>
    </main>

    <script>
        function copyTextarea(id, button) {
            const textarea = document.getElementById(id);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);
            navigator.clipboard.writeText(textarea.value).then(() => {
                const original = button.textContent;
                button.textContent = 'Copiado';
                window.setTimeout(() => {
                    button.textContent = original;
                }, 1400);
            });
        }

        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', () => copyTextarea(button.dataset.copy, button));
        });
    </script>
</body>
</html>
