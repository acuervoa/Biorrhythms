<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Biorrhythms.php';
require_once __DIR__ . '/src/Compatibility.php';
require_once __DIR__ . '/src/ExtremeDays.php';

use Biorrhythms\Biorrhythms;
use Biorrhythms\Compatibility;
use Biorrhythms\ExtremeDays;

function clampDateInput(?string $value, string $fallback): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($date === false) {
        return $fallback;
    }

    return $date->format('Y-m-d');
}

function daysBetween(DateTimeImmutable $from, DateTimeImmutable $to): float
{
    return ($to->getTimestamp() - $from->getTimestamp()) / 86400;
}

function valueToPercent(float $value): string
{
    return number_format($value * 100, 1, '.', '') . '%';
}

function valueToSignedPercent(float $value): string
{
    $sign = $value > 0 ? '+' : '';

    return $sign . number_format($value * 100, 1, '.', '') . '%';
}


$birthInput = clampDateInput($_GET['birth'] ?? null, '1990-01-01');
$focusInput = clampDateInput($_GET['focus'] ?? null, (new DateTimeImmutable('today'))->format('Y-m-d'));
$compatPresetInput = $_GET['preset'] ?? 'custom';
$compatPresetInput = in_array($compatPresetInput, ['pair', 'friend', 'work', 'custom'], true) ? $compatPresetInput : 'custom';
$embedMode = ($_GET['embed'] ?? '') === '1' || ($_GET['view'] ?? '') === 'widget';
$widgetTheme = ($_GET['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';

$birthDate = new DateTimeImmutable($birthInput);
$focusDate = new DateTimeImmutable($focusInput);
$partnerBirthInput = clampDateInput($_GET['partner_birth'] ?? null, '1991-01-01');
$partnerBirthDate = new DateTimeImmutable($partnerBirthInput);
$bio = new Biorrhythms();

$windowRadius = 45;
$window = [];

for ($offset = -$windowRadius; $offset <= $windowRadius; $offset++) {
    $date = $focusDate->modify(($offset >= 0 ? '+' : '') . $offset . ' day');
    $daysSinceBirth = daysBetween($birthDate, $date);

    $window[] = [
        'date' => $date->format('Y-m-d'),
        'label' => $date->format('D j M'),
        'offset' => $offset,
        'physical' => $bio->calculatePhysical($daysSinceBirth),
        'emotional' => $bio->calculateEmotional($daysSinceBirth),
        'intellectual' => $bio->calculateIntellectual($daysSinceBirth),
    ];
}

$focusDaysSinceBirth = daysBetween($birthDate, $focusDate);
$focusValues = [
    'physical' => $bio->calculatePhysical($focusDaysSinceBirth),
    'emotional' => $bio->calculateEmotional($focusDaysSinceBirth),
    'intellectual' => $bio->calculateIntellectual($focusDaysSinceBirth),
];

$partnerFocusValues = [
    'physical' => $bio->calculatePhysical(daysBetween($partnerBirthDate, $focusDate)),
    'emotional' => $bio->calculateEmotional(daysBetween($partnerBirthDate, $focusDate)),
    'intellectual' => $bio->calculateIntellectual(daysBetween($partnerBirthDate, $focusDate)),
];

$selectedIndex = $windowRadius;
$selectedPoint = $window[$selectedIndex];

$profileLabel = 'Balanced';
if ($selectedPoint['physical'] >= $selectedPoint['emotional'] && $selectedPoint['physical'] >= $selectedPoint['intellectual']) {
    $profileLabel = 'Physical peak';
} elseif ($selectedPoint['emotional'] >= $selectedPoint['physical'] && $selectedPoint['emotional'] >= $selectedPoint['intellectual']) {
    $profileLabel = 'Emotional peak';
} elseif ($selectedPoint['intellectual'] >= $selectedPoint['physical'] && $selectedPoint['intellectual'] >= $selectedPoint['emotional']) {
    $profileLabel = 'Mental peak';
}

$focusSummary = sprintf(
    '%s · %s · %s',
    valueToPercent($focusValues['physical']),
    valueToPercent($focusValues['emotional']),
    valueToPercent($focusValues['intellectual'])
);

$focusCompatibility = Compatibility::pointScore($focusValues, $partnerFocusValues);

$widgetUrlParams = [
    'birth' => $birthInput,
    'focus' => $focusInput,
    'partner_birth' => $partnerBirthInput,
    'preset' => $compatPresetInput,
    'embed' => '1',
];

if ($compatPresetInput === 'custom') {
    unset($widgetUrlParams['preset']);
}

$widgetUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($widgetUrlParams, '', '&', PHP_QUERY_RFC3986);
$widgetSnippet = '<iframe src="' . $widgetUrl . '" title="Biorrhythms widget" width="420" height="320" loading="lazy" style="border:0;border-radius:20px;overflow:hidden;"></iframe>';
$widgetPreviewUrl = $embedMode ? 'about:blank' : $widgetUrl;

$apiLinkParams = [
    'birth' => $birthInput,
    'focus' => $focusInput,
    'partner_birth' => $partnerBirthInput,
];
if ($compatPresetInput !== 'custom') {
    $apiLinkParams['preset'] = $compatPresetInput;
}
$apiUrl = '/api/?' . http_build_query($apiLinkParams + ['pretty' => '1'], '', '&', PHP_QUERY_RFC3986);
$apiSnippet = 'curl -s "' . $apiUrl . '" | jq';

$extremeDays = ExtremeDays::find($bio, $birthDate, $focusDate);

$seriesData = [
    'windowRadius' => $windowRadius,
    'window' => $window,
    'selectedIndex' => $selectedIndex,
    'selectedPoint' => $selectedPoint,
    'profileLabel' => $profileLabel,
    'birthInput' => $birthInput,
    'focusInput' => $focusInput,
    'partnerBirthInput' => $partnerBirthInput,
    'compatPresetInput' => $compatPresetInput,
    'focusCompatibility' => $focusCompatibility,
    'extremeDays' => $extremeDays,
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biorrhythms</title>
    <style>
        :root {
            --bg: #07111f;
            --panel: rgba(10, 18, 32, 0.72);
            --panel-strong: rgba(14, 24, 42, 0.92);
            --stroke: rgba(255, 255, 255, 0.12);
            --text: #ecf2ff;
            --muted: #9bb0d0;
            --physical: #ff7b54;
            --emotional: #6ee7b7;
            --intellectual: #7dd3fc;
            --accent: #f8c95d;
        }

        body[data-theme="light"] {
            --bg: #f5f3ee;
            --panel: rgba(255, 255, 255, 0.74);
            --panel-strong: rgba(255, 255, 255, 0.92);
            --stroke: rgba(16, 24, 40, 0.12);
            --text: #18202b;
            --muted: #516174;
            --physical: #f05f3b;
            --emotional: #249a79;
            --intellectual: #1976d2;
            --accent: #c78827;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(255, 123, 84, 0.18), transparent 30%),
                radial-gradient(circle at top right, rgba(125, 211, 252, 0.18), transparent 28%),
                radial-gradient(circle at bottom center, rgba(110, 231, 183, 0.12), transparent 25%),
                linear-gradient(160deg, #040812 0%, #07111f 45%, #0c172a 100%);
            font-family: "Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif;
        }

        body[data-theme="light"] {
            background:
                radial-gradient(circle at top left, rgba(240, 95, 59, 0.18), transparent 26%),
                radial-gradient(circle at top right, rgba(25, 118, 210, 0.18), transparent 28%),
                radial-gradient(circle at bottom center, rgba(36, 154, 121, 0.14), transparent 24%),
                linear-gradient(160deg, #fdf8f2 0%, #f5f3ee 45%, #e7ecf6 100%);
        }

        .shell {
            width: min(1180px, calc(100% - 32px));
            margin: 14px auto 32px;
        }

        .hero {
            display: block;
        }

        .card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--stroke);
            border-radius: 24px;
            background: linear-gradient(180deg, var(--panel), var(--panel-strong));
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(14px);
        }

        body[data-theme="light"] .card {
            box-shadow: 0 16px 40px rgba(17, 24, 39, 0.12);
        }

        .hero-main,
        .controls,
        .panel,
        .detail,
        .share-card {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity 700ms ease, transform 700ms ease;
        }

        body.ready .hero-main,
        body.ready .controls,
        body.ready .panel,
        body.ready .detail,
        body.ready .share-card {
            opacity: 1;
            transform: translateY(0);
        }

        body.ready .controls:nth-child(1) { transition-delay: 80ms; }
        body.ready .controls:nth-child(2) { transition-delay: 140ms; }
        body.ready .panel { transition-delay: 220ms; }
        body.ready .detail:nth-child(1) { transition-delay: 300ms; }
        body.ready .detail:nth-child(2) { transition-delay: 360ms; }
        body.ready .detail:nth-child(3) { transition-delay: 420ms; }
        body.ready .detail:nth-child(4) { transition-delay: 480ms; }

        .hero-main {
            padding: 18px 24px;
        }

        .hero-body {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            flex-wrap: wrap;
        }

        .hero-text {
            flex: 1 1 320px;
        }

        .hero-right {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 4px;
        }

        .hero-stats-inline {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .controls-bar {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            flex-wrap: wrap;
            padding: 12px 20px;
            margin-top: 10px;
        }

        .controls-bar label {
            flex: 1 1 140px;
            min-width: 120px;
        }

        .controls-bar .bar-submit {
            flex: 0 0 auto;
            margin-top: 0;
            align-self: flex-end;
        }

        .controls-bar .bar-slider {
            flex: 1 1 180px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .controls-bar .bar-slider small {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .hero-topline {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border: 1px solid rgba(248, 201, 93, 0.22);
            border-radius: 999px;
            background: rgba(248, 201, 93, 0.08);
            color: #ffe7a8;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 10px 0 6px;
            font-size: clamp(1.8rem, 3.5vw, 3rem);
            line-height: 0.97;
            letter-spacing: -0.03em;
        }

        .lead {
            max-width: 60ch;
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .hero-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .hero-callout .hero-chips {
            margin-top: 0;
            padding: 0 14px 10px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.09);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font-size: 0.8rem;
            letter-spacing: 0.02em;
        }

        .hero-chip strong {
            color: #ffe7a8;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-callout {
            margin-top: 12px;
            border-radius: 16px;
            border: 1px solid rgba(248, 201, 93, 0.16);
            background:
                linear-gradient(135deg, rgba(248, 201, 93, 0.08), rgba(125, 211, 252, 0.05)),
                rgba(255, 255, 255, 0.03);
        }

        .hero-callout summary {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            cursor: pointer;
            list-style: none;
            color: #ffe7a8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
            user-select: none;
        }

        .hero-callout summary::after {
            content: '▸';
            margin-left: auto;
            transition: transform 180ms;
        }

        .hero-callout[open] summary::after {
            transform: rotate(90deg);
        }

        .hero-callout p {
            margin: 0;
            padding: 0 14px 12px;
            color: var(--text);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .hero-side {
            display: grid;
            gap: 10px;
        }

        .stat-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, 1fr);
            margin-top: 12px;
        }

        .stat {
            padding: 10px 12px 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.03);
        }

        .stat small {
            display: block;
            margin-bottom: 4px;
            color: var(--muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat strong {
            display: block;
            font-size: 1.3rem;
            line-height: 1;
        }

        .stat span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .controls {
            padding: 16px 20px;
        }

        .controls-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        label {
            display: grid;
            gap: 5px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        input[type="date"] {
            width: 100%;
            padding: 10px 12px;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            font: inherit;
        }

        button {
            margin-top: 10px;
            padding: 10px 18px;
            border: 0;
            border-radius: 14px;
            color: #06111f;
            background: linear-gradient(135deg, #f8c95d, #ffb36f);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .section {
            margin-top: 10px;
        }

        .panel {
            padding: 14px 18px;
        }

        .chart-wrap {
            position: relative;
        }

        .zoom-btns {
            display: flex;
            gap: 4px;
        }

        .zoom-btn {
            padding: 5px 11px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(255,255,255,0.04);
            color: var(--muted);
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.04em;
        }

        .zoom-btn.is-active {
            background: rgba(248,201,93,0.14);
            border-color: rgba(248,201,93,0.35);
            color: #ffe7a8;
        }

        .hero-forecast-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hero-forecast-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            font-size: 0.82rem;
            color: var(--muted);
        }

        .hero-forecast-chip strong {
            color: var(--text);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .hero-forecast-chip.is-best { border-color: rgba(110,231,183,0.25); }
        .hero-forecast-chip.is-best strong { color: var(--emotional); }
        .hero-forecast-chip.is-worst { border-color: rgba(255,123,84,0.25); }
        .hero-forecast-chip.is-worst strong { color: var(--physical); }

        .panel-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }

        .panel-head h2 {
            margin: 0;
            font-size: 1.1rem;
        }

        .panel-head p {
            margin: 0;
            color: var(--muted);
        }

        .slider-wrap {
            margin-top: 16px;
        }

        input[type="range"] {
            width: 100%;
            accent-color: var(--accent);
        }

        .chart {
            width: 100%;
            height: auto;
            display: block;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 14px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .detail-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .detail {
            padding: 18px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .detail small {
            display: block;
            color: var(--muted);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .detail strong {
            display: block;
            font-size: 1.6rem;
        }

        .detail em {
            display: block;
            margin-top: 8px;
            color: var(--muted);
            font-style: normal;
            font-size: 0.9rem;
        }

        .path {
            fill: none;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .grid-line {
            stroke: rgba(255, 255, 255, 0.09);
            stroke-width: 1;
        }

        .zero-line {
            stroke: rgba(248, 201, 93, 0.24);
            stroke-width: 1.5;
            stroke-dasharray: 6 6;
        }

        .marker {
            stroke: rgba(255, 255, 255, 0.4);
            stroke-width: 1.2;
            stroke-dasharray: 4 8;
        }

        .marker-label {
            fill: rgba(255, 255, 255, 0.8);
            font-size: 12px;
        }

        .footer-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .share-card {
            margin-top: 18px;
            padding: 20px;
            display: grid;
            gap: 16px;
            grid-template-columns: 1.2fr 0.8fr;
            align-items: stretch;
        }

        .share-preview {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 20px;
            background:
                radial-gradient(circle at top left, rgba(248, 201, 93, 0.18), transparent 30%),
                radial-gradient(circle at bottom right, rgba(125, 211, 252, 0.18), transparent 32%),
                linear-gradient(180deg, rgba(18, 28, 48, 0.94), rgba(8, 14, 27, 0.98));
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .share-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            color: #ffe7a8;
            background: rgba(248, 201, 93, 0.08);
            border: 1px solid rgba(248, 201, 93, 0.18);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .share-title {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.05;
        }

        .share-subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.55;
        }

        .share-metrics {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .share-metric {
            padding: 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .share-metric small {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .share-metric strong {
            display: block;
            font-size: 1.45rem;
        }

        .share-side {
            display: grid;
            gap: 12px;
            align-content: start;
        }

        .share-side .button-row {
            display: grid;
            gap: 10px;
        }

        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text);
            background: rgba(255, 255, 255, 0.04);
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            gap: 8px;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(110, 231, 183, 0.08);
            border: 1px solid rgba(110, 231, 183, 0.16);
            color: #c3ffe7;
            font-size: 0.84rem;
        }

        .forecast-card {
            margin-top: 18px;
            padding: 20px;
        }

        .forecast-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .forecast-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .forecast-head p {
            margin: 0;
            color: var(--muted);
        }

        .forecast-strip {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }

        .forecast-day {
            display: grid;
            gap: 8px;
            padding: 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            min-height: 132px;
        }

        .forecast-day strong {
            display: block;
            font-size: 0.96rem;
        }

        .forecast-day span {
            color: var(--muted);
            font-size: 0.78rem;
        }

        .forecast-bars {
            display: grid;
            gap: 7px;
            align-content: end;
            margin-top: auto;
        }

        .forecast-bar {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 8px;
            align-items: center;
            font-size: 0.74rem;
            color: var(--muted);
        }

        .forecast-track {
            position: relative;
            height: 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .forecast-fill {
            position: absolute;
            inset: 0 auto 0 0;
            width: 50%;
            border-radius: inherit;
        }

        .forecast-fill.physical { background: var(--physical); }
        .forecast-fill.emotional { background: var(--emotional); }
        .forecast-fill.intellectual { background: var(--intellectual); }

        .forecast-day.is-best {
            border-color: rgba(248, 201, 93, 0.5);
            box-shadow: 0 0 0 1px rgba(248, 201, 93, 0.18), 0 12px 28px rgba(248, 201, 93, 0.08);
        }

        .forecast-day.is-worst {
            border-color: rgba(255, 123, 84, 0.5);
        }

        .forecast-insight {
            margin-top: 12px;
            color: #d9e5ff;
            line-height: 1.55;
        }

        .decision-card {
            margin-top: 18px;
            padding: 20px;
        }

        .decision-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .decision-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .decision-head p {
            margin: 0;
            color: var(--muted);
        }

        .decision-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
        }

        .decision-panel {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .decision-panel small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .decision-panel strong {
            display: block;
            font-size: clamp(1.4rem, 2vw, 2rem);
            line-height: 1.05;
        }

        .decision-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .decision-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-self: start;
            align-items: flex-start;
        }

        .decision-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font-size: 0.78rem;
            white-space: nowrap;
            height: auto;
        }

        .decision-calendar {
            display: grid;
            gap: 10px;
        }

        .decision-calendar h3 {
            margin: 0;
            font-size: 0.95rem;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .decision-calendar-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .decision-day {
            display: grid;
            grid-template-columns: 90px 1fr 2fr auto auto;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .decision-day.is-best {
            border-color: rgba(248, 201, 93, 0.5);
            box-shadow: 0 0 0 1px rgba(248, 201, 93, 0.14);
        }

        .decision-day.is-worst {
            border-color: rgba(255, 123, 84, 0.5);
        }

        .decision-day small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.82rem;
        }

        .decision-day strong {
            font-size: 1rem;
            line-height: 1.3;
        }

        .decision-day p {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .decision-day .decision-action {
            font-size: 0.88rem;
            color: #ffe7a8;
            white-space: nowrap;
        }

        .widget-card {
            margin-top: 18px;
            padding: 20px;
        }

        .widget-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .widget-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .widget-head p {
            margin: 0;
            color: var(--muted);
        }

        .widget-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 0.95fr 1.05fr;
        }

        .widget-panel {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .widget-panel small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .widget-panel strong {
            display: block;
            font-size: clamp(1.4rem, 2vw, 2rem);
            line-height: 1.05;
        }

        .widget-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .widget-preview-mini {
            display: grid;
            gap: 10px;
        }

        .widget-mini-stats {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .widget-mini-stat {
            padding: 10px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .widget-mini-stat small {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 0.68rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .widget-mini-stat strong {
            font-size: 1.1rem;
        }

        .widget-note {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(248, 201, 93, 0.08);
            border: 1px solid rgba(248, 201, 93, 0.14);
            color: #ffe7a8;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .widget-chart-shell {
            position: relative;
            padding: 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .widget-chart-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .widget-chart-title strong {
            font-size: 0.88rem;
            line-height: 1.2;
        }

        .widget-chart-title span {
            color: var(--muted);
            font-size: 0.72rem;
        }

        .widget-chart {
            width: 100%;
            height: 190px;
            display: block;
        }

        .widget-chart .grid-line {
            stroke: rgba(255, 255, 255, 0.08);
            stroke-width: 1;
        }

        .widget-chart .zero-line {
            stroke: rgba(248, 201, 93, 0.25);
            stroke-width: 1.4;
            stroke-dasharray: 6 6;
        }

        .widget-chart .today-line {
            stroke: rgba(248, 201, 93, 0.8);
            stroke-width: 1.4;
        }

        .widget-chart .series-line {
            fill: none;
            stroke-width: 3.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .widget-chart .series-point {
            fill: var(--bg);
            stroke-width: 2.4;
        }

        .widget-chart-tooltip {
            position: absolute;
            z-index: 2;
            pointer-events: none;
            min-width: 170px;
            padding: 10px 11px;
            border-radius: 14px;
            background: rgba(10, 18, 32, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
            color: var(--text);
            transform: translate(-50%, -110%);
            opacity: 0;
            transition: opacity 140ms ease;
        }

        body[data-theme="light"] .widget-chart-tooltip {
            background: rgba(255, 255, 255, 0.96);
        }

        .widget-chart-tooltip.is-visible {
            opacity: 1;
        }

        .widget-chart-tooltip strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.8rem;
        }

        .widget-chart-tooltip .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 0.78rem;
            line-height: 1.5;
        }

        .widget-chart-tooltip .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            margin-right: 6px;
        }

        .widget-code {
            display: grid;
            gap: 10px;
        }

        .widget-code small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .widget-code textarea {
            width: 100%;
            min-height: 136px;
            resize: vertical;
            border-radius: 16px;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: 0.85rem/1.5 "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .widget-frame {
            width: 100%;
            min-height: 320px;
            border: 0;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.02);
        }

        .api-card {
            margin-top: 18px;
            padding: 20px;
        }

        .api-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .api-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .api-head p {
            margin: 0;
            color: var(--muted);
        }

        .api-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr;
        }

        .api-preview {
            margin: 0;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
            color: #dbe8ff;
            overflow: auto;
            font: 0.83rem/1.5 "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            white-space: pre-wrap;
            max-height: calc(1.5em * 30 + 28px);
        }

        .api-preview.expanded {
            max-height: none;
        }

        .api-preview-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted);
            font-size: 0.8rem;
            cursor: pointer;
        }

        .api-preview-toggle:hover {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text);
        }

        .api-snippet {
            display: grid;
            gap: 10px;
        }

        .api-snippet small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .api-snippet textarea {
            width: 100%;
            min-height: 92px;
            resize: vertical;
            border-radius: 16px;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font: 0.85rem/1.5 "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        .api-panel {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .api-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .ritual-card {
            margin-top: 18px;
            padding: 20px;
        }

        .ritual-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .ritual-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .ritual-head p {
            margin: 0;
            color: var(--muted);
        }

        .ritual-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: 0.9fr 1.1fr;
        }

        .ritual-panel {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .ritual-panel small {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .ritual-panel strong {
            display: block;
            font-size: clamp(1.4rem, 2vw, 2rem);
            line-height: 1.05;
        }

        .ritual-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .ritual-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 10px;
            color: var(--text);
            line-height: 1.55;
        }

        .ritual-list li::marker {
            color: #f8c95d;
            font-weight: 700;
        }

        .ritual-note {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(248, 201, 93, 0.08);
            border: 1px solid rgba(248, 201, 93, 0.14);
            color: #ffe7a8;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        body.embed-view {
            background: transparent;
        }

        body.embed-view .shell {
            width: min(840px, calc(100% - 24px));
            margin: 12px auto;
        }

        body.embed-view .shell > :not(.widget-card) {
            display: none;
        }

        body.embed-view .widget-card {
            margin-top: 0;
        }

        body.embed-view .widget-code,
        body.embed-view .widget-frame,
        body.embed-view .api-card {
            display: none;
        }

        .forecast-spark {
            margin-top: 14px;
            width: 100%;
            height: 120px;
            display: block;
        }

        .forecast-spark path {
            fill: none;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 1;
            stroke-dashoffset: 1;
        }

        .forecast-spark .physical { stroke: var(--physical); }
        .forecast-spark .emotional { stroke: var(--emotional); }
        .forecast-spark .intellectual { stroke: var(--intellectual); }

        .forecast-spark .spark-grid {
            stroke: rgba(255, 255, 255, 0.09);
            stroke-width: 1;
        }

        .forecast-spark .spark-zero {
            stroke: rgba(248, 201, 93, 0.24);
            stroke-width: 1.4;
            stroke-dasharray: 6 6;
        }

        .story-card {
            margin-top: 18px;
            padding: 20px;
        }

        .story-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .story-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .story-head p {
            margin: 0;
            color: var(--muted);
        }

        .story-stage {
            display: grid;
            gap: 14px;
            padding: 18px;
            border-radius: 20px;
            background:
                radial-gradient(circle at top left, rgba(248, 201, 93, 0.16), transparent 26%),
                radial-gradient(circle at bottom right, rgba(125, 211, 252, 0.14), transparent 28%),
                rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 240ms ease, box-shadow 240ms ease, border-color 240ms ease;
        }

        .story-stage.is-cinematic {
            transform: translateY(-2px) scale(1.01);
            border-color: rgba(248, 201, 93, 0.24);
            box-shadow: 0 20px 60px rgba(248, 201, 93, 0.06);
        }

        .story-kicker {
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid rgba(248, 201, 93, 0.18);
            background: rgba(248, 201, 93, 0.08);
            color: #ffe7a8;
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .story-title {
            margin: 0;
            font-size: clamp(1.4rem, 2.4vw, 2rem);
            line-height: 1.05;
        }

        .story-copy {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .story-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .story-stat {
            padding: 14px;
            border-radius: 16px;
            background: rgba(8, 14, 27, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .story-stat small {
            display: block;
            color: var(--muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.72rem;
        }

        .story-stat strong {
            display: block;
            font-size: 1.5rem;
        }

        @keyframes storySlideIn {
            from { transform: translateX(48px); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }

        .story-slide {
            display: grid;
            gap: 14px;
        }

        .story-slide.is-entering {
            animation: storySlideIn 380ms cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }

        .story-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 4px;
        }

        .story-dots {
            display: flex;
            gap: 6px;
        }

        .story-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            transition: background 200ms, transform 200ms;
        }

        .story-dot.is-active {
            background: #ffe7a8;
            transform: scale(1.3);
        }

        .story-pause-btn {
            font-size: 0.8rem;
            padding: 6px 14px;
        }

        .story-progress-bar {
            height: 2px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2px;
            margin-top: 10px;
            overflow: hidden;
        }

        .story-progress-fill {
            height: 100%;
            background: rgba(248, 201, 93, 0.6);
            width: 0%;
            transition: width linear;
        }

        .compat-card {
            margin-top: 18px;
            padding: 20px;
        }

        .events-card {
            padding: 20px 22px;
        }

        .events-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 14px;
        }

        .events-head h2 { margin: 0; font-size: 1.1rem; }
        .events-head p { margin: 0; color: var(--muted); font-size: 0.88rem; }

        .events-list {
            display: grid;
            gap: 12px;
        }

        .event-row {
            display: grid;
            grid-template-columns: 110px 1fr;
            align-items: start;
            gap: 18px;
            padding: 14px 18px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.07);
            background: rgba(255,255,255,0.02);
        }

        .event-rhythm-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding-top: 6px;
        }

        .event-chips-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .event-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.03);
            white-space: nowrap;
        }

        .event-chip em {
            font-style: normal;
            font-weight: 700;
            font-size: 0.76rem;
        }

        .event-chip.is-peak   { border-color: rgba(110,231,183,0.3); }
        .event-chip.is-peak em { color: var(--emotional); }
        .event-chip.is-valley  { border-color: rgba(255,123,84,0.3); }
        .event-chip.is-valley em { color: var(--physical); }
        .event-chip.is-zero   { border-color: rgba(125,211,252,0.2); color: var(--muted); }
        .event-chip.is-today  { outline: 1px solid rgba(248,201,93,0.5); }

        .extreme-card {
            padding: 20px 22px;
        }

        .extreme-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .extreme-head h2 { margin: 0; font-size: 1.1rem; }
        .extreme-head p  { margin: 0; color: var(--muted); font-size: 0.88rem; }

        .extreme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }

        .extreme-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.07);
            background: rgba(255,255,255,0.02);
        }

        .extreme-item.is-peak {
            border-color: rgba(110,231,183,0.2);
            background: rgba(110,231,183,0.04);
        }

        .extreme-item.is-valley {
            border-color: rgba(255,123,84,0.2);
            background: rgba(255,100,84,0.04);
        }

        .extreme-item-score {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
        }

        .extreme-item.is-peak   .extreme-item-score { color: var(--emotional); }
        .extreme-item.is-valley .extreme-item-score { color: var(--physical); }

        .extreme-item-date {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text);
        }

        .extreme-item-rhythms {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .extreme-item-rhythm {
            font-size: 0.72rem;
            padding: 2px 7px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--muted);
        }

        .compat-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .compat-head h2 {
            margin: 0;
            font-size: 1.25rem;
        }

        .compat-head p {
            margin: 0;
            color: var(--muted);
        }

        .compat-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 0.9fr 1.1fr;
        }

        .compat-score {
            display: grid;
            gap: 14px;
            align-content: start;
            padding: 18px;
            border-radius: 20px;
            background:
                radial-gradient(circle at top left, rgba(248, 201, 93, 0.18), transparent 26%),
                rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .compat-score strong {
            display: block;
            font-size: clamp(2.4rem, 5vw, 4rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .compat-score small {
            color: var(--muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .compat-score p {
            margin: 0;
            color: var(--text);
            line-height: 1.55;
        }

        .compat-weighted {
            display: none;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(125, 211, 252, 0.2);
            background: rgba(125, 211, 252, 0.06);
            gap: 6px;
        }

        .compat-weighted.is-visible {
            display: grid;
        }

        .compat-weighted small {
            color: var(--accent-2);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .compat-weighted strong {
            font-size: 1.6rem;
            line-height: 1;
            color: var(--accent-2);
        }

        .compat-weighted p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
            line-height: 1.5;
        }

        .compat-inputs {
            display: grid;
            gap: 12px;
        }

        .compat-presets {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .compat-presets .secondary-btn.is-active {
            background: rgba(248, 201, 93, 0.14);
            border-color: rgba(248, 201, 93, 0.32);
            color: #ffe7a8;
        }

        .compat-inputs label {
            display: grid;
            gap: 8px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .compat-inputs input[type="date"] {
            width: 100%;
            padding: 14px 16px;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            font: inherit;
        }

        .compat-strip {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }

        .compat-heatmap {
            display: grid;
            gap: 10px;
            margin-top: 16px;
        }

        .compat-heatmap h3 {
            margin: 0;
            font-size: 0.95rem;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .compat-heatmap-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .compat-heatmap-cell {
            min-height: 68px;
            border-radius: 14px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            display: grid;
            align-content: space-between;
        }

        .compat-heatmap-cell strong {
            font-size: 0.9rem;
        }

        .compat-heatmap-cell span {
            font-size: 0.74rem;
            color: var(--muted);
        }

        .compat-heatmap-cell[data-score-level="high"] { background: rgba(110, 231, 183, 0.14); }
        .compat-heatmap-cell[data-score-level="mid"] { background: rgba(248, 201, 93, 0.14); }
        .compat-heatmap-cell[data-score-level="low"] { background: rgba(255, 123, 84, 0.12); }

        .compat-day {
            display: grid;
            gap: 10px;
            padding: 12px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            min-height: 138px;
        }

        .compat-day strong {
            display: block;
            font-size: 0.95rem;
        }

        .compat-day span {
            color: var(--muted);
            font-size: 0.76rem;
        }

        .compat-meter {
            margin-top: auto;
            display: grid;
            gap: 6px;
        }

        .compat-track {
            position: relative;
            height: 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            overflow: hidden;
        }

        .compat-fill {
            position: absolute;
            inset: 0 auto 0 0;
            width: 50%;
            border-radius: inherit;
            background: var(--physical);
        }

        .compat-day.is-best {
            border-color: rgba(248, 201, 93, 0.55);
            box-shadow: 0 0 0 1px rgba(248, 201, 93, 0.18);
        }

        .compat-day.is-worst {
            border-color: rgba(255, 123, 84, 0.5);
        }

        .compat-footer {
            margin-top: 12px;
            color: var(--muted);
            line-height: 1.55;
        }

        .compat-export-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        @keyframes storyFlash {
            0% { filter: brightness(0.96); }
            45% { filter: brightness(1.08); }
            100% { filter: brightness(1); }
        }

        .story-stage.is-cinematic .story-kicker,
        .story-stage.is-cinematic .story-title,
        .story-stage.is-cinematic .story-copy,
        .story-stage.is-cinematic .story-grid {
            animation: storyFlash 420ms ease;
        }

        .theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text);
            cursor: pointer;
            font: inherit;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        body[data-theme="light"] .theme-toggle {
            background: rgba(255, 255, 255, 0.78);
            border-color: rgba(16, 24, 40, 0.1);
        }

        @media (max-width: 920px) {
            .hero,
            .detail-grid,
            .share-card,
            .compat-grid,
            .decision-grid,
            .widget-grid,
            .forecast-strip,
            .story-grid,
            .compat-strip,
            .compat-heatmap-grid,
            .controls-grid,
            .stat-grid,
            .share-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body<?= $embedMode ? ' class="embed-view" data-theme="' . htmlspecialchars($widgetTheme, ENT_QUOTES) . '"' : '' ?>>
    <main class="shell">
        <section class="hero">
            <article class="card hero-main">
                <div class="hero-topline">
                    <div class="kicker">Biorrhythms</div>
                    <button id="themeToggleBtn" type="button" class="theme-toggle">Tema claro</button>
                </div>
                <div class="hero-body">
                    <div class="hero-text">
                        <h1>Un ciclo simple, convertido en un tablero vivo.</h1>
                        <p class="lead">
                            Tus tres ritmos como una escena visual: curvas, foco del día y una ventana de 90 días
                            para explorar picos, valles y simetrías sin instalar nada en la máquina.
                        </p>
                        <details class="hero-callout">
                            <summary>Qué hace esta app</summary>
                            <div class="hero-chips" aria-label="Resumen del producto">
                                <span class="hero-chip"><strong>Local</strong> Docker + PHP sin instalar nada</span>
                                <span class="hero-chip"><strong>Shareable</strong> URL con estado persistente</span>
                                <span class="hero-chip"><strong>Export</strong> PNG listo para compartir</span>
                            </div>
                            <p>
                                Convierte una librería mínima en una experiencia visual: lectura individual, compatibilidad entre personas
                                y una exportación que puedes compartir sin perder el contexto.
                            </p>
                        </details>
                    </div>
                    <div class="hero-right">
                        <div class="hero-stats-inline">
                            <div class="stat">
                                <small>Físico</small>
                                <strong id="stat-physical"><?= htmlspecialchars(valueToPercent($selectedPoint['physical'])) ?></strong>
                                <span>23 días</span>
                            </div>
                            <div class="stat">
                                <small>Emocional</small>
                                <strong id="stat-emotional"><?= htmlspecialchars(valueToPercent($selectedPoint['emotional'])) ?></strong>
                                <span>28 días</span>
                            </div>
                            <div class="stat">
                                <small>Intelectual</small>
                                <strong id="stat-intellectual"><?= htmlspecialchars(valueToPercent($selectedPoint['intellectual'])) ?></strong>
                                <span>33 días</span>
                            </div>
                        </div>
                        <div class="hero-forecast-chips" id="heroForecastChips" aria-live="polite"></div>
                    </div>
                </div>
            </article>
        </section>

        <section class="card controls-bar">
            <form method="get" style="display:contents;">
                <input type="hidden" name="preset" id="compatPresetInput" value="<?= htmlspecialchars($compatPresetInput) ?>">
                <label>
                    Fecha de nacimiento
                    <input type="date" name="birth" value="<?= htmlspecialchars($birthInput) ?>">
                </label>
                <label>
                    Fecha de la otra persona
                    <input type="date" name="partner_birth" value="<?= htmlspecialchars($partnerBirthInput) ?>">
                </label>
                <label>
                    Día de foco
                    <input type="date" name="focus" value="<?= htmlspecialchars($focusInput) ?>">
                </label>
                <button type="submit" class="bar-submit">Actualizar vista</button>
            </form>
            <div class="bar-slider">
                <small>Vista rápida</small>
                <input id="windowSlider" type="range" min="0" max="<?= (int) (count($window) - 1) ?>" value="<?= $selectedIndex ?>">
                <div class="footer-note" id="selectedLabel">
                    <?= htmlspecialchars($selectedPoint['label']) ?> · offset <?= (int) $selectedPoint['offset'] ?> días
                </div>
            </div>
        </section>

        <section class="section card panel chart-wrap">
            <div class="panel-head">
                <div>
                    <h2>Timeline viva</h2>
                    <p>La línea central es cero. Arriba es energía positiva; abajo, fase baja.</p>
                </div>
                <div class="button-row" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <div class="zoom-btns">
                        <button type="button" class="zoom-btn" data-zoom="7">1S</button>
                        <button type="button" class="zoom-btn" data-zoom="30">30D</button>
                        <button type="button" class="zoom-btn" data-zoom="60">60D</button>
                        <button type="button" class="zoom-btn is-active" data-zoom="91">90D</button>
                    </div>
                    <button id="exportPngBtn" type="button">Exportar PNG</button>
                    <button id="jumpToTodayBtn" type="button" class="secondary-btn">Volver al día foco</button>
                </div>
            </div>

            <svg class="chart" viewBox="0 0 1100 420" role="img" aria-label="Timeline de biorritmos">
                <defs>
                    <linearGradient id="bgFade" x1="0" x2="1">
                        <stop offset="0%" stop-color="rgba(255,255,255,0.05)"></stop>
                        <stop offset="100%" stop-color="rgba(255,255,255,0.01)"></stop>
                    </linearGradient>
                </defs>
                <rect x="0" y="0" width="1100" height="420" rx="22" fill="url(#bgFade)"></rect>
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

        <section class="section detail-grid">
            <article class="detail">
                <small>Día de foco</small>
                <strong id="detail-date"><?= htmlspecialchars($selectedPoint['label']) ?></strong>
                <em id="detail-offset">Offset <?= (int) $selectedPoint['offset'] ?> días</em>
            </article>
            <article class="detail">
                <small>Físico</small>
                <strong id="detail-physical"><?= htmlspecialchars(valueToPercent($selectedPoint['physical'])) ?></strong>
                <em>Lectura instantánea</em>
            </article>
            <article class="detail">
                <small>Emocional</small>
                <strong id="detail-emotional"><?= htmlspecialchars(valueToPercent($selectedPoint['emotional'])) ?></strong>
                <em>Lectura instantánea</em>
            </article>
            <article class="detail">
                <small>Intelectual</small>
                <strong id="detail-intellectual"><?= htmlspecialchars(valueToPercent($selectedPoint['intellectual'])) ?></strong>
                <em>Lectura instantánea</em>
            </article>
        </section>

        <section class="section card events-card">
            <div class="events-head">
                <div>
                    <h2>Días especiales</h2>
                    <p>Picos, valles y cruces de cero en la ventana de 90 días.</p>
                </div>
                <div class="status-pill" id="eventsPill">—</div>
            </div>
            <div class="events-list" id="eventsList"></div>
        </section>

        <section class="section card extreme-card">
            <div class="extreme-head">
                <div>
                    <h2>Días extremos</h2>
                    <p>Días futuros con media superior al +95% o inferior al −95%.</p>
                </div>
                <div class="status-pill" id="extremePill">—</div>
            </div>
            <div class="extreme-grid" id="extremeGrid"></div>
        </section>

        <section class="section card compat-card">
            <div class="compat-head">
                <div>
                    <h2>Modo compatibilidad</h2>
                    <p>Compara dos perfiles en el día actual y en la próxima semana.</p>
                </div>
                <div class="status-pill" id="compatSummaryPill">Listo para comparar</div>
            </div>
            <div class="compat-grid">
                <div class="compat-score">
                    <small>Score de compatibilidad</small>
                    <strong id="compatScore"><?= htmlspecialchars(number_format($focusCompatibility * 100, 1, '.', '')) ?>%</strong>
                    <p id="compatNarrative">
                        <?= htmlspecialchars('Se calculó una lectura inicial usando ' . $focusInput . ' y ' . $partnerBirthInput . '.') ?>
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
                    <div class="compat-strip" id="compatStrip"></div>
                    <div class="compat-heatmap">
                        <h3>Heatmap semanal</h3>
                        <div class="compat-heatmap-grid" id="compatHeatmap"></div>
                    </div>
                    <div class="compat-footer" id="compatFooter"></div>
                </div>
            </div>
        </section>

        <section class="section card decision-card">
            <div class="decision-head">
                <div>
                    <h2>Asistente de decisiones</h2>
                    <p>Traduce la curva de hoy en una acción concreta y en una semana con intención.</p>
                </div>
                <div class="status-pill" id="decisionBadge">Listo para decidir</div>
            </div>
            <div class="decision-grid">
                <article class="decision-panel">
                    <small>Recomendación principal</small>
                    <strong id="decisionAction">Cargando</strong>
                    <p id="decisionWhy">La lectura del día se está calculando.</p>
                    <div class="decision-tags" id="decisionTags"></div>
                </article>
                <div class="decision-calendar">
                    <h3>Calendario de acciones</h3>
                    <div class="decision-calendar-grid" id="decisionCalendar"></div>
                </div>
            </div>
        </section>

        <section class="section card ritual-card">
            <div class="ritual-head">
                <div>
                    <h2>Ritual diario</h2>
                    <p>Una secuencia corta para empezar, sostener y cerrar el día.</p>
                </div>
                <div class="status-pill" id="ritualBadge">Foco listo</div>
            </div>
            <div class="ritual-grid">
                <article class="ritual-panel">
                    <small>Secuencia de hoy</small>
                    <ol class="ritual-list" id="ritualLines">
                        <li>Cargando ritual...</li>
                        <li>...</li>
                        <li>...</li>
                    </ol>
                    <div class="button-row">
                        <button id="copyRitualBtn" type="button" class="secondary-btn">Copiar ritual</button>
                    </div>
                </article>
                <article class="ritual-panel">
                    <small>Foco del día</small>
                    <strong id="ritualFocus">Preparando lectura</strong>
                    <p id="ritualWhy">El ritual se construye a partir de la lectura actual y la mejor ventana de la semana.</p>
                    <div class="decision-tags" id="ritualTags"></div>
                    <div class="ritual-note" id="ritualNote">Corto, accionable y sin ruido: útil para arrancar con intención.</div>
                </article>
            </div>
        </section>

        <section class="section card story-card">
            <div class="story-head">
                <div>
                    <h2>Story mode</h2>
                    <p>La lectura se narra como una secuencia de escenas, no solo como números.</p>
                </div>
                <div class="status-pill" id="storyProgress">Escena 1 / 4</div>
            </div>
            <div class="story-stage">
                <div class="story-slide" id="storySlide">
                    <div class="story-kicker" id="storyKicker">Inicio</div>
                    <h3 class="story-title" id="storyTitle">Tu día de foco</h3>
                    <p class="story-copy" id="storyCopy"></p>
                    <div class="story-grid">
                        <div class="story-stat">
                            <small>Físico</small>
                            <strong id="storyPhysical">0.0%</strong>
                        </div>
                        <div class="story-stat">
                            <small>Emocional</small>
                            <strong id="storyEmotional">0.0%</strong>
                        </div>
                        <div class="story-stat">
                            <small>Intelectual</small>
                            <strong id="storyIntellectual">0.0%</strong>
                        </div>
                    </div>
                </div>
                <div class="story-footer">
                    <div class="story-dots" id="storyDots"></div>
                    <button id="storyAutoBtn" type="button" class="secondary-btn story-pause-btn">⏸ Pausar</button>
                </div>
                <div class="story-progress-bar"><div class="story-progress-fill" id="storyProgressFill"></div></div>
            </div>
        </section>

        <section class="section card share-card">
            <div class="share-preview" id="sharePreview">
                <div class="share-badge">Share card</div>
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
                    Exporta una tarjeta visual con el pico del día y los tres ritmos. Lista para compartir en redes.
                </div>
            </div>
        </section>

        <section class="section card widget-card">
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
                            <span>30 días · hover activo</span>
                        </div>
                        <svg id="widgetChart" class="widget-chart" viewBox="0 0 640 190" role="img" aria-label="Curvas de biorritmos centradas en hoy">
                            <g id="widgetChartGrid"></g>
                            <g id="widgetChartPaths"></g>
                            <g id="widgetChartMarkers"></g>
                        </svg>
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
        </section>

        <section class="section card api-card">
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
    </main>

    <script>
        const palette = {
            physical: getComputedStyle(document.documentElement).getPropertyValue('--physical').trim(),
            emotional: getComputedStyle(document.documentElement).getPropertyValue('--emotional').trim(),
            intellectual: getComputedStyle(document.documentElement).getPropertyValue('--intellectual').trim(),
        };

        const data = <?= json_encode($seriesData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const birthInput = <?= json_encode($birthInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const focusInput = <?= json_encode($focusInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const partnerBirthInputInitial = <?= json_encode($partnerBirthInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const compatPresetInputInitial = <?= json_encode($compatPresetInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const svgNS = 'http://www.w3.org/2000/svg';
        const width = 1100;
        const height = 420;
        const padding = { top: 34, right: 28, bottom: 42, left: 28 };
        const innerWidth = width - padding.left - padding.right;
        const innerHeight = height - padding.top - padding.bottom;
        const centerY = padding.top + innerHeight / 2;

        const gridGroup = document.getElementById('grid');
        const pathsGroup = document.getElementById('paths');
        const markersGroup = document.getElementById('markers');
        const slider = document.getElementById('windowSlider');
        const exportPngBtn = document.getElementById('exportPngBtn');
        const jumpToTodayBtn = document.getElementById('jumpToTodayBtn');
        const copySummaryBtn = document.getElementById('copySummaryBtn');
        const copyLinkBtn = document.getElementById('copyLinkBtn');
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const heroForecastChips = document.getElementById('heroForecastChips');
        const zoomButtons = document.querySelectorAll('.zoom-btn');

        let visibleWindow = data.window;
        let visibleStartIndex = 0;
        const decisionBadge = document.getElementById('decisionBadge');
        const decisionAction = document.getElementById('decisionAction');
        const decisionWhy = document.getElementById('decisionWhy');
        const decisionTags = document.getElementById('decisionTags');
        const decisionCalendar = document.getElementById('decisionCalendar');
        const widgetSnippet = document.getElementById('widgetSnippet');
        const copyWidgetBtn = document.getElementById('copyWidgetBtn');
        const openWidgetBtn = document.getElementById('openWidgetBtn');
        const widgetFrame = document.getElementById('widgetFrame');
        const apiStatus = document.getElementById('apiStatus');
        const apiSnippet = document.getElementById('apiSnippet');
        const copyApiBtn = document.getElementById('copyApiBtn');
        const openApiBtn = document.getElementById('openApiBtn');
        const apiPreview = document.getElementById('apiPreview');
        const ritualBadge = document.getElementById('ritualBadge');
        const ritualLines = document.getElementById('ritualLines');
        const ritualFocus = document.getElementById('ritualFocus');
        const ritualWhy = document.getElementById('ritualWhy');
        const ritualTags = document.getElementById('ritualTags');
        const ritualNote = document.getElementById('ritualNote');
        const copyRitualBtn = document.getElementById('copyRitualBtn');
        const storyProgress = document.getElementById('storyProgress');
        const storyKicker = document.getElementById('storyKicker');
        const storyTitle = document.getElementById('storyTitle');
        const storyCopy = document.getElementById('storyCopy');
        const storyPhysical = document.getElementById('storyPhysical');
        const storyEmotional = document.getElementById('storyEmotional');
        const storyIntellectual = document.getElementById('storyIntellectual');
        const storyAutoBtn = document.getElementById('storyAutoBtn');
        const storySlide = document.getElementById('storySlide');
        const storyDots = document.getElementById('storyDots');
        const storyProgressFill = document.getElementById('storyProgressFill');
        const storyStage = document.querySelector('.story-stage');
        const STORY_INTERVAL = 5000;
        const partnerBirthInput = document.getElementById('partnerBirthInput');
        const presetInput = document.getElementById('compatPresetInput');
        const exportCompatBtn = document.getElementById('exportCompatBtn');
        const compatScore = document.getElementById('compatScore');
        const compatNarrative = document.getElementById('compatNarrative');
        const compatStrip = document.getElementById('compatStrip');
        const compatHeatmap = document.getElementById('compatHeatmap');
        const compatFooter = document.getElementById('compatFooter');
        const compatSummaryPill = document.getElementById('compatSummaryPill');
        const compatPresetButtons = document.querySelectorAll('[data-compat-preset]');
        const widgetChart = document.getElementById('widgetChart');
        const widgetChartGrid = document.getElementById('widgetChartGrid');
        const widgetChartPaths = document.getElementById('widgetChartPaths');
        const widgetChartMarkers = document.getElementById('widgetChartMarkers');
        const widgetChartTooltip = document.getElementById('widgetChartTooltip');

        let selectedIndex = data.selectedIndex;
        let storyIndex = 0;
        let storyTimer = null;
        let theme = window.localStorage.getItem('biorrhythms-theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');

        function applyTheme(nextTheme) {
            theme = nextTheme === 'light' ? 'light' : 'dark';
            document.body.dataset.theme = theme;
            themeToggleBtn.textContent = theme === 'light' ? 'Tema oscuro' : 'Tema claro';
            window.localStorage.setItem('biorrhythms-theme', theme);
            updateWidgetEmbedSnippet();
        }

        function yFor(value) {
            return centerY - value * (innerHeight / 2 - 10);
        }

        function xFor(visibleIndex) {
            return padding.left + (visibleIndex / Math.max(1, visibleWindow.length - 1)) * innerWidth;
        }

        function make(tag, attrs = {}) {
            const el = document.createElementNS(svgNS, tag);
            for (const [key, value] of Object.entries(attrs)) {
                el.setAttribute(key, value);
            }
            return el;
        }

        function buildPath(seriesName) {
            return visibleWindow
                .map((point, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i).toFixed(2)} ${yFor(point[seriesName]).toFixed(2)}`)
                .join(' ');
        }

        function buildGrid() {
            gridGroup.innerHTML = '';

            [-1, -0.5, 0, 0.5, 1].forEach((value) => {
                const y = yFor(value);
                gridGroup.appendChild(make('line', {
                    x1: padding.left,
                    x2: width - padding.right,
                    y1: y,
                    y2: y,
                    class: value === 0 ? 'zero-line' : 'grid-line'
                }));
            });

            const markerVi = data.selectedIndex - visibleStartIndex;
            if (markerVi >= 0 && markerVi < visibleWindow.length) {
                const marker = make('line', {
                    x1: xFor(markerVi),
                    x2: xFor(markerVi),
                    y1: padding.top - 4,
                    y2: height - padding.bottom + 8,
                    class: 'marker',
                });
                markersGroup.appendChild(marker);

                const label = make('text', {
                    x: xFor(markerVi) + 10,
                    y: padding.top + 12,
                    class: 'marker-label',
                });
                label.textContent = 'Hoy';
                markersGroup.appendChild(label);
            }

            // start label
            const startLabel = make('text', {
                x: padding.left + 4,
                y: height - padding.bottom + 16,
                class: 'marker-label',
            });
            startLabel.textContent = visibleWindow[0]?.label ?? '';
            gridGroup.appendChild(startLabel);

            // end label
            const endLabel = make('text', {
                x: width - padding.right - 4,
                y: height - padding.bottom + 16,
                class: 'marker-label',
                'text-anchor': 'end',
            });
            endLabel.textContent = visibleWindow[visibleWindow.length - 1]?.label ?? '';
            gridGroup.appendChild(endLabel);
        }

        function buildPaths() {
            pathsGroup.innerHTML = '';

            [
                ['physical', palette.physical],
                ['emotional', palette.emotional],
                ['intellectual', palette.intellectual],
            ].forEach(([seriesName, color]) => {
                const path = make('path', {
                    d: buildPath(seriesName),
                    class: 'path',
                    stroke: color,
                });
                path.dataset.series = seriesName;
                pathsGroup.appendChild(path);
            });

            requestAnimationFrame(() => {
                document.querySelectorAll('.path').forEach((path) => {
                    const length = path.getTotalLength();
                    path.style.strokeDasharray = `${length}`;
                    path.style.strokeDashoffset = `${length}`;
                    path.style.transition = 'stroke-dashoffset 1600ms ease';
                    requestAnimationFrame(() => {
                        path.style.strokeDashoffset = '0';
                    });
                });
            });
        }

        function buildCenteredWidgetPoints() {
            const radius = 15;
            const start = Math.max(0, data.selectedIndex - radius);
            const end = Math.min(data.window.length - 1, data.selectedIndex + radius);
            return data.window.slice(start, end + 1);
        }

        function buildSmoothPath(points, seriesName, widthPx, heightPx, paddingPx) {
            const innerWidthPx = widthPx - paddingPx * 2;
            const innerHeightPx = heightPx - paddingPx * 2;
            const center = paddingPx + innerHeightPx / 2;
            const coords = points.map((point, index) => ({
                x: paddingPx + (index / Math.max(1, points.length - 1)) * innerWidthPx,
                y: center - point[seriesName] * (innerHeightPx / 2 - 10),
            }));

            if (coords.length === 0) {
                return '';
            }

            if (coords.length === 1) {
                return `M ${coords[0].x} ${coords[0].y}`;
            }

            let path = `M ${coords[0].x.toFixed(2)} ${coords[0].y.toFixed(2)}`;
            for (let index = 0; index < coords.length - 1; index += 1) {
                const current = coords[index];
                const next = coords[index + 1];
                const midX = (current.x + next.x) / 2;
                path += ` Q ${current.x.toFixed(2)} ${current.y.toFixed(2)} ${midX.toFixed(2)} ${((current.y + next.y) / 2).toFixed(2)}`;
                path += ` T ${next.x.toFixed(2)} ${next.y.toFixed(2)}`;
            }

            return path;
        }

        function renderWidgetChartTooltip(point, x, y) {
            if (!widgetChartTooltip) return;
            widgetChartTooltip.innerHTML = `
                <strong>${point.label} · ${point.date}</strong>
                <div class="row"><span><span class="dot" style="background:${palette.physical}"></span>Físico</span><span>${(point.physical * 100).toFixed(1)}%</span></div>
                <div class="row"><span><span class="dot" style="background:${palette.emotional}"></span>Emocional</span><span>${(point.emotional * 100).toFixed(1)}%</span></div>
                <div class="row"><span><span class="dot" style="background:${palette.intellectual}"></span>Intelectual</span><span>${(point.intellectual * 100).toFixed(1)}%</span></div>
            `;
            widgetChartTooltip.style.left = `${x}px`;
            widgetChartTooltip.style.top = `${y}px`;
            widgetChartTooltip.classList.add('is-visible');
            widgetChartTooltip.setAttribute('aria-hidden', 'false');
        }

        function hideWidgetChartTooltip() {
            if (!widgetChartTooltip) return;
            widgetChartTooltip.classList.remove('is-visible');
            widgetChartTooltip.setAttribute('aria-hidden', 'true');
        }

        function updateWidgetChart() {
            if (!widgetChart || !widgetChartGrid || !widgetChartPaths || !widgetChartMarkers || !widgetChartTooltip) {
                return;
            }

            const points = buildCenteredWidgetPoints();
            const widthPx = 640;
            const heightPx = 190;
            const paddingPx = 18;
            const innerWidthPx = widthPx - paddingPx * 2;
            const innerHeightPx = heightPx - paddingPx * 2;
            const centerY = paddingPx + innerHeightPx / 2;
            const todayIndex = points.findIndex((point) => point.offset === 0);
            const todayX = paddingPx + (todayIndex / Math.max(1, points.length - 1)) * innerWidthPx;

            widgetChartGrid.innerHTML = [
                -1, -0.5, 0, 0.5, 1,
            ].map((value) => {
                const y = centerY - value * (innerHeightPx / 2 - 10);
                return `<line x1="${paddingPx}" x2="${widthPx - paddingPx}" y1="${y.toFixed(2)}" y2="${y.toFixed(2)}" class="${value === 0 ? 'zero-line' : 'grid-line'}"></line>`;
            }).join('');

            widgetChartPaths.innerHTML = `
                <path class="series-line" stroke="${palette.physical}" d="${buildSmoothPath(points, 'physical', widthPx, heightPx, paddingPx)}"></path>
                <path class="series-line" stroke="${palette.emotional}" d="${buildSmoothPath(points, 'emotional', widthPx, heightPx, paddingPx)}"></path>
                <path class="series-line" stroke="${palette.intellectual}" d="${buildSmoothPath(points, 'intellectual', widthPx, heightPx, paddingPx)}"></path>
            `;

            widgetChartMarkers.innerHTML = `
                <line x1="${todayX.toFixed(2)}" x2="${todayX.toFixed(2)}" y1="${paddingPx - 2}" y2="${heightPx - paddingPx + 2}" class="today-line"></line>
                <circle cx="${todayX.toFixed(2)}" cy="${centerY.toFixed(2)}" r="4.8" fill="${palette.physical}" opacity="0.9"></circle>
            `;

            const tooltipPoint = points[todayIndex >= 0 ? todayIndex : Math.floor(points.length / 2)];
            if (tooltipPoint) {
                renderWidgetChartTooltip(tooltipPoint, todayX, paddingPx + 12);
                widgetChartTooltip.classList.remove('is-visible');
            }

            const pointsMeta = points.map((point, index) => ({
                point,
                x: paddingPx + (index / Math.max(1, points.length - 1)) * innerWidthPx,
            }));

            const showPointAtClientX = (clientX) => {
                const rect = widgetChart.getBoundingClientRect();
                const x = clientX - rect.left;
                const targetX = ((x / rect.width) * widthPx);
                let nearest = pointsMeta[0];
                let distance = Number.POSITIVE_INFINITY;
                pointsMeta.forEach((candidate) => {
                    const nextDistance = Math.abs(candidate.x - targetX);
                    if (nextDistance < distance) {
                        distance = nextDistance;
                        nearest = candidate;
                    }
                });

                const tooltipX = ((nearest.x / widthPx) * rect.width);
                const tooltipY = (paddingPx + 6) * (rect.height / heightPx);
                renderWidgetChartTooltip(nearest.point, tooltipX, tooltipY);
            };

            widgetChart.addEventListener('pointermove', (event) => {
                showPointAtClientX(event.clientX);
            });
            widgetChart.addEventListener('pointerleave', hideWidgetChartTooltip);
            widgetChart.addEventListener('focusin', () => {
                if (tooltipPoint) {
                    renderWidgetChartTooltip(tooltipPoint, todayX, paddingPx + 12);
                }
            });
        }

        function updateSelected(index) {
            selectedIndex = index;
            const point = data.window[index];
            const focusLabel = point.physical >= point.emotional && point.physical >= point.intellectual
                ? 'Physical peak'
                : point.emotional >= point.physical && point.emotional >= point.intellectual
                    ? 'Emotional peak'
                    : 'Mental peak';
            document.getElementById('detail-date').textContent = point.label;
            document.getElementById('detail-offset').textContent = `Offset ${point.offset} días`;
            document.getElementById('detail-physical').textContent = `${(point.physical * 100).toFixed(1)}%`;
            document.getElementById('detail-emotional').textContent = `${(point.emotional * 100).toFixed(1)}%`;
            document.getElementById('detail-intellectual').textContent = `${(point.intellectual * 100).toFixed(1)}%`;
            document.getElementById('stat-physical').textContent = `${(point.physical * 100).toFixed(1)}%`;
            document.getElementById('stat-emotional').textContent = `${(point.emotional * 100).toFixed(1)}%`;
            document.getElementById('stat-intellectual').textContent = `${(point.intellectual * 100).toFixed(1)}%`;
            document.getElementById('shareTitle').textContent = focusLabel;
            document.getElementById('shareSubtitle').textContent = `${point.label} · nacimiento ${birthInput} · foco ${focusInput}`;
            document.getElementById('sharePhysical').textContent = `${point.physical >= 0 ? '+' : ''}${(point.physical * 100).toFixed(1)}%`;
            document.getElementById('shareEmotional').textContent = `${point.emotional >= 0 ? '+' : ''}${(point.emotional * 100).toFixed(1)}%`;
            document.getElementById('shareIntellectual').textContent = `${point.intellectual >= 0 ? '+' : ''}${(point.intellectual * 100).toFixed(1)}%`;
            document.getElementById('shareStatus').textContent = `${(point.physical * 100).toFixed(1)}% · ${(point.emotional * 100).toFixed(1)}% · ${(point.intellectual * 100).toFixed(1)}%`;

            markersGroup.innerHTML = '';
            const vi = index - visibleStartIndex;
            if (vi >= 0 && vi < visibleWindow.length) {
                markersGroup.appendChild(make('line', {
                    x1: xFor(vi),
                    x2: xFor(vi),
                    y1: padding.top - 4,
                    y2: height - padding.bottom + 8,
                    class: 'marker',
                }));
                markersGroup.appendChild(make('text', {
                    x: xFor(vi) + 10,
                    y: padding.top + 12,
                    class: 'marker-label',
                }));
                markersGroup.lastChild.textContent = point.label;
            }

            document.getElementById('selectedLabel').textContent = `${point.label} · offset ${point.offset} días`;
            updateForecast(index);
        }

        function animateEntrance() {
            document.body.classList.add('ready');
        }

        function selectedPointPayload() {
            return data.window[selectedIndex];
        }

        function valueToBarWidth(value) {
            return `${Math.max(6, Math.round((value + 1) * 50))}%`;
        }

        function parseIsoDate(dateString) {
            return new Date(`${dateString}T00:00:00Z`);
        }

        function daysBetweenIso(baseDate, targetDate) {
            return (parseIsoDate(targetDate).getTime() - parseIsoDate(baseDate).getTime()) / 86400000;
        }

        function rhythmValue(days, period) {
            return Math.sin(2 * Math.PI * days / period);
        }

        function compatibilityScore(a, b) {
            return Math.max(0, 1 - Math.abs(a - b) / 2);
        }

        function compatibilityForDate(dateString, partnerBirth) {
            const daysA = daysBetweenIso(birthInput, dateString);
            const daysB = daysBetweenIso(partnerBirth, dateString);
            const rhythms = [
                {
                    label: 'Físico',
                    value: compatibilityScore(rhythmValue(daysA, 23), rhythmValue(daysB, 23)),
                },
                {
                    label: 'Emocional',
                    value: compatibilityScore(rhythmValue(daysA, 28), rhythmValue(daysB, 28)),
                },
                {
                    label: 'Intelectual',
                    value: compatibilityScore(rhythmValue(daysA, 33), rhythmValue(daysB, 33)),
                },
            ];

            const score = rhythms.reduce((carry, item) => carry + item.value, 0) / rhythms.length;

            return { score, rhythms };
        }

        const PRESET_WEIGHTS = {
            pair:   { physical: 0.20, emotional: 0.50, intellectual: 0.30 },
            friend: { physical: 0.25, emotional: 0.45, intellectual: 0.30 },
            work:   { physical: 0.25, emotional: 0.20, intellectual: 0.55 },
        };

        function weightedCompatibilityScore(preset, rhythms) {
            const w = PRESET_WEIGHTS[preset];
            if (!w) return null;
            const byLabel = Object.fromEntries(rhythms.map((r) => [r.label, r.value]));
            return w.physical * byLabel['Físico'] + w.emotional * byLabel['Emocional'] + w.intellectual * byLabel['Intelectual'];
        }

        function normalizePartnerBirthForPreset(preset) {
            const baseDate = parseIsoDate(focusInput);
            const shiftDays = {
                pair: 0,
                friend: -42,
                work: 73,
            }[preset] ?? 0;

            const normalized = new Date(baseDate.getTime() + shiftDays * 86400000);
            return normalized.toISOString().slice(0, 10);
        }

        function presetNarrative(preset) {
            return {
                pair: 'Pareja: favorece la lectura emocional y la sincronía de largo plazo.',
                friend: 'Amistad: suele dar un ajuste más relajado, con menos presión en los picos.',
                work: 'Trabajo: busca estabilidad y picos útiles para coordinación y entregas.',
            }[preset] ?? 'Comparación genérica entre dos perfiles.';
        }

        function presetLabel(preset) {
            return {
                pair: 'Pareja',
                friend: 'Amistad',
                work: 'Trabajo',
                custom: 'Personalizado',
            }[preset] ?? 'Personalizado';
        }

        function averageSeries(point) {
            return (point.physical + point.emotional + point.intellectual) / 3;
        }

        function dominantSeries(point) {
            const candidates = [
                { key: 'physical', label: 'Físico', value: point.physical },
                { key: 'emotional', label: 'Emocional', value: point.emotional },
                { key: 'intellectual', label: 'Intelectual', value: point.intellectual },
            ];

            return candidates.reduce((carry, item) => (item.value > carry.value ? item : carry), candidates[0]);
        }

        function ritualForPoint(point, forecastWindow) {
            const currentDecision = decisionForPoint(point);
            const dominant = dominantSeries(point);
            const best = forecastWindow.reduce((carry, item) => (averageSeries(item) > averageSeries(carry) ? item : carry), forecastWindow[0]);
            const worst = forecastWindow.reduce((carry, item) => (averageSeries(item) < averageSeries(carry) ? item : carry), forecastWindow[0]);
            const bestDecision = decisionForPoint(best);

            const lines = [
                `Empieza con ${currentDecision.action.toLowerCase()}.`,
                `Reserva ${best.label} para la tarea más importante y evita empujar en ${worst.label}.`,
                `Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.`,
            ];

            return {
                badge: dominant.label,
                focus: `${dominant.label} marca el tono de hoy.`,
                why: `${currentDecision.why} La mejor ventana de la semana cae en ${best.label}, donde conviene usar ${bestDecision.action.toLowerCase()}.`,
                lines,
                tags: [dominant.label, best.label, worst.label],
                note: `Ritual de 3 pasos para un día con foco en ${dominant.label.toLowerCase()} y cierre limpio.`,
            };
        }

        function decisionForPoint(point) {
            const avg = averageSeries(point);
            const dominant = dominantSeries(point);

            if (avg >= 0.35) {
                if (dominant.key === 'physical') {
                    return {
                        badge: 'Ventana de empuje',
                        action: 'Entrena fuerte o resuelve lo físico',
                        why: 'El cuerpo lidera y la media general acompaña: conviene usar esta lectura para movimiento, entrega o acciones que consumen energía.',
                        tags: ['Entrenar', 'Cerrar tareas', 'Mover agenda'],
                    };
                }

                if (dominant.key === 'emotional') {
                    return {
                        badge: 'Ventana social',
                        action: 'Toma conversaciones importantes',
                        why: 'La parte emocional está arriba y la ventana es favorable para vínculo, feedback o conversaciones que requieren tacto.',
                        tags: ['Conversar', 'Escuchar', 'Alinear'],
                    };
                }

                return {
                    badge: 'Ventana mental',
                    action: 'Escribe, decide y estructura',
                    why: 'El ritmo intelectual domina y el promedio acompaña, así que es buen momento para escribir, planificar o cerrar decisiones.',
                    tags: ['Escribir', 'Planificar', 'Resolver'],
                };
            }

            if (avg >= 0.1) {
                if (dominant.key === 'physical') {
                    return {
                        badge: 'Ritmo estable',
                        action: 'Haz trabajo útil y sin sobrecarga',
                        why: 'La energía física ayuda, pero sin exceso. Va mejor para avanzar paso a paso y evitar maratones innecesarias.',
                        tags: ['Progreso', 'Ritmo', 'Sin prisa'],
                    };
                }

                if (dominant.key === 'emotional') {
                    return {
                        badge: 'Ritmo sensible',
                        action: 'Ajusta conversaciones y responde con calma',
                        why: 'La lectura emocional tira del día; funciona mejor para coordinar, cuidar el tono y no forzar decisiones duras.',
                        tags: ['Coordinación', 'Calma', 'Feedback'],
                    };
                }

                return {
                    badge: 'Ritmo analítico',
                    action: 'Prioriza foco corto y revisión',
                    why: 'El intelecto sostiene el día, pero la energía total no es alta: mejor una lista corta que un proyecto interminable.',
                    tags: ['Revisar', 'Sintetizar', 'Priorizar'],
                };
            }

            if (avg >= -0.15) {
                return {
                    badge: 'Ventana neutra',
                    action: 'Mantén el ritmo y simplifica',
                    why: 'No hay un pico claro, así que la mejor jugada es bajar fricción, limpiar pendientes y no meter demasiada carga.',
                    tags: ['Ordenar', 'Reducir', 'Mantener'],
                };
            }

            return {
                badge: 'Ventana de recuperación',
                action: 'Baja intensidad y reserva energía',
                why: 'La lectura general está baja: sirve más para descansar, documentar o hacer tareas mecánicas que para empujar fuerte.',
                tags: ['Descansar', 'Documentar', 'Recuperar'],
            };
        }

        function buildDecisionDayCard(point, isBest, isWorst) {
            const decision = decisionForPoint(point);
            const card = document.createElement('article');
            card.className = `decision-day${isBest ? ' is-best' : ''}${isWorst ? ' is-worst' : ''}`;
            card.innerHTML = `
                <small>${point.label}</small>
                <strong>${decision.action}</strong>
                <p>${decision.why}</p>
                <div class="status-pill">${Math.round(averageSeries(point) * 100)}% media</div>
                <div class="decision-action">${isBest ? 'Mejor uso del día' : isWorst ? 'Día de cuidado' : decision.badge}</div>
            `;
            return card;
        }

        function updateDecisionAssistant(index) {
            const forecastWindow = forecastSliceFrom(index);
            const scored = forecastWindow.map((point) => ({
                ...point,
                score: averageSeries(point),
            }));
            const best = scored.reduce((carry, point) => (point.score > carry.score ? point : carry), scored[0]);
            const worst = scored.reduce((carry, point) => (point.score < carry.score ? point : carry), scored[0]);
            const current = decisionForPoint(data.window[index]);
            const dominant = dominantSeries(data.window[index]);

            decisionBadge.textContent = current.badge;
            decisionAction.textContent = current.action;
            decisionWhy.textContent = current.why;
            decisionTags.innerHTML = current.tags.map((tag) => `<span class="decision-tag">${tag}</span>`).join('');

            decisionCalendar.innerHTML = '';
            scored.forEach((point) => {
                decisionCalendar.appendChild(buildDecisionDayCard(point, point.date === best.date, point.date === worst.date));
            });

            heroForecastChips.innerHTML = `
                <span class="hero-forecast-chip is-best"><strong>Mejor</strong> ${best.label} · ${Math.round(best.score * 100)}%</span>
                <span class="hero-forecast-chip is-worst"><strong>Valle</strong> ${worst.label} · ${Math.round(worst.score * 100)}%</span>
            `;

            if (best.date === data.window[index].date) {
                decisionBadge.textContent = `${current.badge} · mejor día`;
            } else {
                decisionBadge.textContent = `${current.badge} · ${best.label} lidera la semana`;
            }

            if (dominant.key === 'emotional' && current.badge !== 'Ventana de recuperación') {
                decisionWhy.textContent = `${current.why} Hoy domina la lectura emocional, así que el tono importa más que la velocidad.`;
            }
        }

        function updateRitual(index) {
            const forecastWindow = forecastSliceFrom(index);
            const ritual = ritualForPoint(data.window[index], forecastWindow);

            ritualBadge.textContent = ritual.badge;
            ritualFocus.textContent = ritual.focus;
            ritualWhy.textContent = ritual.why;
            ritualNote.textContent = ritual.note;
            ritualLines.innerHTML = ritual.lines.map((line) => `<li>${line}</li>`).join('');
            ritualTags.innerHTML = ritual.tags.map((tag) => `<span class="decision-tag">${tag}</span>`).join('');
        }

        function activeCompatibilityPreset() {
            const active = Array.from(compatPresetButtons).find((button) => button.classList.contains('is-active'));
            return active?.dataset.compatPreset ?? 'custom';
        }

        function syncCompatibilityUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set('birth', birthInput);
            url.searchParams.set('focus', focusInput);
            url.searchParams.set('partner_birth', partnerBirthInput.value || partnerBirthInputInitial);

            if (presetInput.value && presetInput.value !== 'custom') {
                url.searchParams.set('preset', presetInput.value);
            } else {
                url.searchParams.delete('preset');
            }

            window.history.replaceState({}, '', url);
            updateWidgetEmbedSnippet();
            updateApiPreview();
        }

        function clamp(value, min, max) {
            return Math.max(min, Math.min(max, value));
        }

        function formatSignedPercent(value) {
            return `${value >= 0 ? '+' : ''}${(value * 100).toFixed(1)}%`;
        }

        function escapeXml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&apos;');
        }

        function buildWidgetEmbedUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set('embed', '1');
            url.searchParams.set('theme', document.body.dataset.theme || theme || 'dark');
            return url.toString();
        }

        function buildApiUrl() {
            const url = new URL(window.location.origin);
            url.pathname = '/api/';
            url.searchParams.set('birth', birthInput);
            url.searchParams.set('focus', focusInput);
            url.searchParams.set('partner_birth', partnerBirthInput.value || partnerBirthInputInitial);

            if (presetInput.value && presetInput.value !== 'custom') {
                url.searchParams.set('preset', presetInput.value);
            }

            url.searchParams.set('pretty', '1');
            return url.toString();
        }

        function updateWidgetEmbedSnippet() {
            if (!widgetSnippet || !openWidgetBtn || !widgetFrame) {
                return;
            }

            const widgetUrl = buildWidgetEmbedUrl();
            const snippet = `<iframe src="${widgetUrl}" title="Biorrhythms widget" width="420" height="320" loading="lazy" style="border:0;border-radius:20px;overflow:hidden;"></iframe>`;

            widgetSnippet.value = snippet;
            openWidgetBtn.href = widgetUrl;

            if (!document.body.classList.contains('embed-view')) {
                widgetFrame.src = widgetUrl;
            }
        }

        async function updateApiPreview() {
            if (!apiSnippet || !copyApiBtn || !openApiBtn || !apiPreview || !apiStatus) {
                return;
            }

            const apiUrl = buildApiUrl();
            apiSnippet.value = `curl -s "${apiUrl}" | jq`;
            openApiBtn.href = apiUrl;
            apiStatus.textContent = 'Actualizando...';

            try {
                const response = await fetch(apiUrl, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                apiPreview.textContent = JSON.stringify(data, null, 2);
                apiStatus.textContent = 'JSON listo';
            } catch (error) {
                apiPreview.textContent = `No se pudo cargar el preview: ${error.message}`;
                apiStatus.textContent = 'Error';
            }
        }

        function ritualText() {
            return Array.from(ritualLines.querySelectorAll('li'))
                .map((item) => item.textContent.trim())
                .join('\n');
        }

        function forecastSliceFrom(index, length = 7) {
            const end = Math.min(index + length, data.window.length);
            return data.window.slice(index, end);
        }

        function buildCompatibilityDayCard(point, partnerBirth, isBest, isWorst) {
            const result = compatibilityForDate(point.date, partnerBirth);
            const card = document.createElement('article');
            card.className = `compat-day${isBest ? ' is-best' : ''}${isWorst ? ' is-worst' : ''}`;
            card.innerHTML = `
                <strong>${point.label}</strong>
                <span>${Math.round(result.score * 100)}% match</span>
                <div class="compat-meter">
                    ${result.rhythms.map((rhythm) => {
                        const color = rhythm.label === 'Físico'
                            ? 'var(--physical)'
                            : rhythm.label === 'Emocional'
                                ? 'var(--emotional)'
                                : 'var(--intellectual)';
                        return `
                        <div>
                            <span>${rhythm.label}</span>
                            <div class="compat-track"><div class="compat-fill" style="width:${Math.max(8, Math.round(rhythm.value * 100))}%;background:${color};"></div></div>
                        </div>`;
                    }).join('')}
                </div>
            `;

            return { card, score: result.score };
        }

        function buildCompatibilityHeatmapCell(point, partnerBirth, isBest, isWorst) {
            const result = compatibilityForDate(point.date, partnerBirth);
            const cell = document.createElement('article');
            const level = result.score >= 0.8 ? 'high' : result.score >= 0.62 ? 'mid' : 'low';
            cell.className = `compat-heatmap-cell${isBest ? ' is-best' : ''}${isWorst ? ' is-worst' : ''}`;
            cell.dataset.scoreLevel = level;
            cell.innerHTML = `
                <strong>${point.label}</strong>
                <span>${Math.round(result.score * 100)}% match</span>
            `;
            return cell;
        }

        function buildDayForecastCard(point, isBest, isWorst) {
            const card = document.createElement('article');
            card.className = `forecast-day${isBest ? ' is-best' : ''}${isWorst ? ' is-worst' : ''}`;
            card.innerHTML = `
                <strong>${point.label}</strong>
                <span>${point.date}</span>
                <div class="forecast-bars">
                    <div class="forecast-bar">
                        <span>Físico</span>
                        <div class="forecast-track"><div class="forecast-fill physical" style="width:${valueToBarWidth(point.physical)}"></div></div>
                    </div>
                    <div class="forecast-bar">
                        <span>Emocional</span>
                        <div class="forecast-track"><div class="forecast-fill emotional" style="width:${valueToBarWidth(point.emotional)}"></div></div>
                    </div>
                    <div class="forecast-bar">
                        <span>Intelectual</span>
                        <div class="forecast-track"><div class="forecast-fill intellectual" style="width:${valueToBarWidth(point.intellectual)}"></div></div>
                    </div>
                </div>
            `;

            return card;
        }

        const eventsList = document.getElementById('eventsList');
        const eventsPill = document.getElementById('eventsPill');

        function buildSpecialEvents() {
            const SERIES = [
                { key: 'physical',     label: 'Físico',      color: 'var(--physical)'     },
                { key: 'emotional',    label: 'Emocional',   color: 'var(--emotional)'    },
                { key: 'intellectual', label: 'Intelectual', color: 'var(--intellectual)' },
            ];
            const w = data.window;
            const todayDate = data.window[data.selectedIndex].date;
            let totalEvents = 0;

            eventsList.innerHTML = '';

            SERIES.forEach(({ key, label, color }) => {
                const chips = [];

                for (let i = 1; i < w.length - 1; i++) {
                    if (w[i].date < todayDate) continue;
                    const prev = w[i - 1][key];
                    const cur  = w[i][key];
                    const next = w[i + 1][key];
                    const isToday = w[i].date === todayDate;
                    const todayMark = isToday ? ' is-today' : '';

                    if (cur > prev && cur > next && cur > 0.85) {
                        chips.push(`<span class="event-chip is-peak${todayMark}" title="${w[i].label}">▲ ${w[i].label} <em>+${Math.round(cur * 100)}%</em></span>`);
                        totalEvents++;
                    } else if (cur < prev && cur < next && cur < -0.85) {
                        chips.push(`<span class="event-chip is-valley${todayMark}" title="${w[i].label}">▼ ${w[i].label} <em>${Math.round(cur * 100)}%</em></span>`);
                        totalEvents++;
                    } else if (prev * cur < 0) {
                        const dir = cur > 0 ? '↑' : '↓';
                        chips.push(`<span class="event-chip is-zero${todayMark}" title="${w[i].label}">${dir} ${w[i].label}</span>`);
                        totalEvents++;
                    }
                }

                const row = document.createElement('div');
                row.className = 'event-row';
                row.innerHTML = `
                    <span class="event-rhythm-label" style="color:${color}">${label}</span>
                    <div class="event-chips-row">${chips.join('')}</div>
                `;
                eventsList.appendChild(row);
            });

            eventsPill.textContent = `${totalEvents} eventos`;
        }

        function buildExtremeDays() {
            const grid = document.getElementById('extremeGrid');
            const pill = document.getElementById('extremePill');
            const days = data.extremeDays ?? [];

            pill.textContent = `${days.length} días`;
            grid.innerHTML = '';

            if (days.length === 0) {
                grid.innerHTML = '<p style="color:var(--muted);font-size:0.88rem">Sin días extremos en el ciclo restante.</p>';
                return;
            }

            days.forEach(({ label, avg, physical, emotional, intellectual }) => {
                const isPeak = avg > 0;
                const cls = isPeak ? 'is-peak' : 'is-valley';
                const scoreTxt = `${avg > 0 ? '+' : ''}${avg}%`;

                const el = document.createElement('div');
                el.className = `extreme-item ${cls}`;
                el.innerHTML = `
                    <div class="extreme-item-score">${scoreTxt}</div>
                    <div class="extreme-item-date">${label}</div>
                    <div class="extreme-item-rhythms">
                        <span class="extreme-item-rhythm" style="color:var(--physical)">F ${physical > 0 ? '+' : ''}${physical}%</span>
                        <span class="extreme-item-rhythm" style="color:var(--emotional)">E ${emotional > 0 ? '+' : ''}${emotional}%</span>
                        <span class="extreme-item-rhythm" style="color:var(--intellectual)">I ${intellectual > 0 ? '+' : ''}${intellectual}%</span>
                    </div>
                `;
                grid.appendChild(el);
            });
        }

        function updateForecast(index) {
            updateDecisionAssistant(index);
            updateRitual(index);
            updateStoryMode(index);
            updateCompatibility(index);
        }

        function updateStoryMode(index) {
            stopStoryAuto();
            storyIndex = 0;
            renderStoryScene(0);
            startStoryAuto();
        }

        function updateCompatibility(index) {
            const partnerBirth = partnerBirthInput.value || partnerBirthInputInitial;
            const compatibilityWindow = forecastSliceFrom(index);
            const scored = compatibilityWindow.map((point) => {
                const result = compatibilityForDate(point.date, partnerBirth);
                return { ...point, score: result.score, rhythms: result.rhythms };
            });
            const best = scored.reduce((carry, point) => (point.score > carry.score ? point : carry), scored[0]);
            const worst = scored.reduce((carry, point) => (point.score < carry.score ? point : carry), scored[0]);
            const selected = compatibilityForDate(data.window[index].date, partnerBirth);

            compatScore.textContent = `${Math.round(selected.score * 100)}%`;
            compatNarrative.textContent = selected.score >= 0.82
                ? 'Lectura alta: hay buena sincronía en los tres ritmos y la ventana se siente fluida.'
                : selected.score >= 0.62
                    ? 'Lectura mixta: hay uno o dos ritmos alineados, pero la compatibilidad depende del día.'
                    : 'Lectura baja: conviene tratar esta ventana como fase de ajuste, no de empuje.';
            compatSummaryPill.textContent = `${Math.round(best.score * 100)}% mejor día`;

            compatStrip.innerHTML = '';
            scored.forEach((point) => {
                const { card } = buildCompatibilityDayCard(point, partnerBirth, point.date === best.date, point.date === worst.date);
                compatStrip.appendChild(card);
            });

            compatHeatmap.innerHTML = '';
            const heatmapWindow = data.window.slice(Math.max(0, index - 3), Math.min(data.window.length, index + 4));
            heatmapWindow.forEach((point) => {
                compatHeatmap.appendChild(buildCompatibilityHeatmapCell(point, partnerBirth, point.date === best.date, point.date === worst.date));
            });

            compatFooter.textContent = `${best.label} marca el mejor ajuste de esta ventana. ${worst.label} es el punto más delicado, con ${Math.round(worst.score * 100)}% de match.`;
        }

        function buildCompatibilityCardSvg() {
            const partnerBirth = partnerBirthInput.value || partnerBirthInputInitial;
            const preset = activeCompatibilityPreset();
            const selected = compatibilityForDate(data.window[selectedIndex].date, partnerBirth);
            const points = forecastSliceFrom(selectedIndex, 7).map((point) => ({
                ...point,
                score: compatibilityForDate(point.date, partnerBirth).score,
            }));
            const best = points.reduce((carry, point) => (point.score > carry.score ? point : carry), points[0]);
            const worst = points.reduce((carry, point) => (point.score < carry.score ? point : carry), points[0]);
            const urlLabel = escapeXml(`${window.location.pathname}${window.location.search}`);

            return `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${theme === 'light' ? '#f7f2eb' : '#07111f'}"/>
      <stop offset="100%" stop-color="${theme === 'light' ? '#e7eef9' : '#10213b'}"/>
    </linearGradient>
    <linearGradient id="accent" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#f8c95d"/>
      <stop offset="100%" stop-color="#ffb36f"/>
    </linearGradient>
    <style>
      .title { font: 700 58px Georgia, serif; fill: ${theme === 'light' ? '#18202b' : '#ecf2ff'}; }
      .sub { font: 400 23px Georgia, serif; fill: ${theme === 'light' ? '#516174' : '#9bb0d0'}; }
      .pill { font: 700 17px Arial, sans-serif; fill: #18202b; }
      .label { font: 700 17px Arial, sans-serif; fill: ${theme === 'light' ? '#516174' : '#9bb0d0'}; letter-spacing: 1px; text-transform: uppercase; }
      .value { font: 700 36px Georgia, serif; fill: ${theme === 'light' ? '#18202b' : '#ecf2ff'}; }
      .small { font: 400 18px Georgia, serif; fill: ${theme === 'light' ? '#516174' : '#9bb0d0'}; }
    </style>
  </defs>
  <rect width="1200" height="630" rx="40" fill="url(#bg)"/>
  <rect x="44" y="44" width="1112" height="542" rx="32" fill="${theme === 'light' ? 'rgba(255,255,255,0.72)' : 'rgba(8,14,27,0.76)'}" stroke="rgba(255,255,255,0.12)"/>
  <text x="84" y="118" class="pill">BIORHYTHMS / COMPATIBILITY</text>
  <text x="84" y="192" class="title">Modo compatibilidad</text>
  <text x="84" y="236" class="sub">${birthInput} · ${partnerBirth}</text>
  <text x="84" y="276" class="sub">Fecha foco: ${focusInput}</text>
  <text x="84" y="338" class="label">Score actual</text>
  <text x="84" y="394" class="value">${Math.round(selected.score * 100)}%</text>
  <text x="84" y="448" class="small">${escapeXml(compatNarrative.textContent)}</text>
  <text x="84" y="506" class="label">Mejor ventana</text>
  <text x="84" y="548" class="sub">${escapeXml(`${best.label} · ${Math.round(best.score * 100)}%`)}</text>
  <text x="384" y="506" class="label">Peor ventana</text>
  <text x="384" y="548" class="sub">${escapeXml(`${worst.label} · ${Math.round(worst.score * 100)}%`)}</text>
  <text x="684" y="506" class="label">Preset</text>
  <text x="684" y="548" class="sub">${presetLabel(preset)}</text>
  <text x="684" y="338" class="label">Heatmap 7 días</text>
  ${points.map((point, index) => {
    const score = Math.round(point.score * 100);
    const x = 684 + index * 70;
    const color = score >= 80 ? '#6ee7b7' : score >= 62 ? '#f8c95d' : '#ff7b54';
    return `<rect x="${x}" y="360" width="56" height="124" rx="16" fill="${color}" fill-opacity="0.18" stroke="${color}" stroke-opacity="0.5"/>
            <text x="${x + 10}" y="392" class="small">${escapeXml(point.label)}</text>
            <text x="${x + 10}" y="438" class="value" style="font-size:24px">${score}%</text>`;
  }).join('')}
  <line x1="84" y1="576" x2="1116" y2="576" stroke="rgba(255,255,255,0.14)"/>
  <text x="84" y="604" class="small">${urlLabel}</text>
</svg>`;
        }

        async function exportCompatibilityCardAsPng() {
            const svg = buildCompatibilityCardSvg();
            const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const img = new Image();
            const canvas = document.createElement('canvas');
            canvas.width = 1200;
            canvas.height = 630;
            const ctx = canvas.getContext('2d');

            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
                img.src = url;
            });

            ctx.fillStyle = theme === 'light' ? '#f7f2eb' : '#07111f';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
            URL.revokeObjectURL(url);

            const downloadUrl = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `biorrhythms-compatibility-${partnerBirthInput.value || partnerBirthInputInitial}.png`;
            a.click();
        }

        function buildStoryScenes(index) {
            const points = [
                data.window[index],
                data.window[clamp(index + 2, 0, data.window.length - 1)],
                data.window[clamp(index + 4, 0, data.window.length - 1)],
                data.window[clamp(index + 6, 0, data.window.length - 1)],
            ];

            const peak = points.reduce((carry, point) => {
                const score = (point.physical + point.emotional + point.intellectual) / 3;
                return score > carry.score ? { score, point } : carry;
            }, { score: -Infinity, point: points[0] });

            return [
                {
                    kicker: 'Inicio',
                    title: 'Tu día de foco',
                    copy: `Arrancas en ${points[0].label}. La mezcla actual sugiere una lectura base de ${formatSignedPercent(points[0].physical)} físico, ${formatSignedPercent(points[0].emotional)} emocional y ${formatSignedPercent(points[0].intellectual)} intelectual.`,
                    point: points[0],
                },
                {
                    kicker: 'Impulso',
                    title: 'Lo que se mueve primero',
                    copy: `En ${points[1].label} aparece el primer giro importante. Si mantienes el ritmo, este tramo puede darte más tracción para tareas cortas o decisiones rápidas.`,
                    point: points[1],
                },
                {
                    kicker: 'Pico',
                    title: 'La mejor ventana de la secuencia',
                    copy: `La escena más fuerte cae en ${peak.point.label}. Aquí la media de los tres ritmos pinta el mejor punto de esta mini-narrativa.`,
                    point: peak.point,
                },
                {
                    kicker: 'Salida',
                    title: 'Cómo aterriza la semana',
                    copy: `Al final, ${points[3].label} marca la salida de la secuencia. Ideal para cerrar, resumir o preparar la siguiente ventana.`,
                    point: points[3],
                },
            ];
        }

        function renderStoryScene(sceneIndex) {
            const scenes = buildStoryScenes(selectedIndex);
            storyIndex = clamp(sceneIndex, 0, scenes.length - 1);
            const scene = scenes[storyIndex];

            storyStage.classList.add('is-cinematic');
            storySlide.classList.remove('is-entering');
            void storySlide.offsetWidth;
            storySlide.classList.add('is-entering');

            storyKicker.textContent = scene.kicker;
            storyTitle.textContent = scene.title;
            storyCopy.textContent = scene.copy;
            storyPhysical.textContent = formatSignedPercent(scene.point.physical);
            storyEmotional.textContent = formatSignedPercent(scene.point.emotional);
            storyIntellectual.textContent = formatSignedPercent(scene.point.intellectual);
            storyProgress.textContent = `Escena ${storyIndex + 1} / ${scenes.length}`;

            storyDots.innerHTML = scenes.map((_, i) =>
                `<div class="story-dot${i === storyIndex ? ' is-active' : ''}"></div>`
            ).join('');

            storyProgressFill.style.transition = 'none';
            storyProgressFill.style.width = '0%';
            void storyProgressFill.offsetWidth;
            if (storyTimer) {
                storyProgressFill.style.transition = `width ${STORY_INTERVAL}ms linear`;
                storyProgressFill.style.width = '100%';
            }

            window.clearTimeout(renderStoryScene._timer);
            renderStoryScene._timer = window.setTimeout(() => {
                storyStage.classList.remove('is-cinematic');
            }, 400);
        }

        function stopStoryAuto() {
            if (storyTimer) {
                window.clearInterval(storyTimer);
                storyTimer = null;
            }
            storyAutoBtn.textContent = '▶ Reanudar';
            storyProgressFill.style.transition = 'none';
            storyProgressFill.style.width = '0%';
        }

        function startStoryAuto() {
            if (storyTimer) window.clearInterval(storyTimer);
            storyAutoBtn.textContent = '⏸ Pausar';
            storyProgressFill.style.transition = `width ${STORY_INTERVAL}ms linear`;
            storyProgressFill.style.width = '100%';
            storyTimer = window.setInterval(() => {
                const scenes = buildStoryScenes(selectedIndex);
                storyIndex = (storyIndex + 1) % scenes.length;
                renderStoryScene(storyIndex);
            }, STORY_INTERVAL);
        }

        function toggleStoryAuto() {
            if (storyTimer) {
                stopStoryAuto();
            } else {
                startStoryAuto();
                renderStoryScene(storyIndex);
            }
        }

        function buildShareSvg(point) {
            const width = 1200;
            const height = 630;
            const headline = point.physical >= point.emotional && point.physical >= point.intellectual
                ? 'Physical peak'
                : point.emotional >= point.physical && point.emotional >= point.intellectual
                    ? 'Emotional peak'
                    : 'Mental peak';

            return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#07111f"/>
      <stop offset="100%" stop-color="#0f1c34"/>
    </linearGradient>
    <radialGradient id="glowA" cx="0.2" cy="0.15" r="0.65">
      <stop offset="0%" stop-color="#f8c95d" stop-opacity="0.38"/>
      <stop offset="100%" stop-color="#f8c95d" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="glowB" cx="0.85" cy="0.15" r="0.7">
      <stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.28"/>
      <stop offset="100%" stop-color="#7dd3fc" stop-opacity="0"/>
    </radialGradient>
    <style>
      .title { font: 700 58px Georgia, serif; fill: #ecf2ff; }
      .sub { font: 400 24px Georgia, serif; fill: #9bb0d0; }
      .pill { font: 700 18px Arial, sans-serif; fill: #ffe7a8; letter-spacing: 2px; }
      .label { font: 700 18px Arial, sans-serif; fill: #9bb0d0; letter-spacing: 1px; text-transform: uppercase; }
      .value { font: 700 44px Georgia, serif; fill: #ecf2ff; }
      .small { font: 400 20px Georgia, serif; fill: #9bb0d0; }
    </style>
  </defs>
  <rect width="1200" height="630" rx="40" fill="url(#bg)"/>
  <rect width="1200" height="630" rx="40" fill="url(#glowA)"/>
  <rect width="1200" height="630" rx="40" fill="url(#glowB)"/>
  <rect x="44" y="44" width="1112" height="542" rx="32" fill="rgba(8,14,27,0.72)" stroke="rgba(255,255,255,0.12)"/>
  <text x="84" y="120" class="pill">BIORHYTHMS / SHARE CARD</text>
  <text x="84" y="194" class="title">${headline}</text>
  <text x="84" y="244" class="sub">${point.label} · nacimiento ${birthInput} · foco ${focusInput}</text>
  <text x="84" y="300" class="small">Físico</text>
  <text x="84" y="346" class="value">${(point.physical * 100).toFixed(1)}%</text>
  <text x="404" y="300" class="small">Emocional</text>
  <text x="404" y="346" class="value">${(point.emotional * 100).toFixed(1)}%</text>
  <text x="724" y="300" class="small">Intelectual</text>
  <text x="724" y="346" class="value">${(point.intellectual * 100).toFixed(1)}%</text>
  <line x1="84" y1="416" x2="1116" y2="416" stroke="rgba(255,255,255,0.16)"/>
  <text x="84" y="470" class="small">Timeline viva · vista local · exportable a PNG</text>
  <text x="84" y="520" class="label">Focus</text>
  <text x="84" y="564" class="sub">${selectedPointPayload().label}</text>
  <text x="404" y="520" class="label">Status</text>
  <text x="404" y="564" class="sub">${point.physical >= point.emotional && point.physical >= point.intellectual ? 'Peak físico' : point.emotional >= point.physical && point.emotional >= point.intellectual ? 'Peak emocional' : 'Peak intelectual'}</text>
  <text x="724" y="520" class="label">Window</text>
  <text x="724" y="564" class="sub">90 días alrededor del foco</text>
</svg>`;
        }

        async function exportShareCardAsPng() {
            const point = selectedPointPayload();
            const canvas = document.createElement('canvas');
            canvas.width = 1200;
            canvas.height = 630;
            const ctx = canvas.getContext('2d');

            function roundRect(x, y, w, h, r) {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.arcTo(x + w, y, x + w, y + h, r);
                ctx.arcTo(x + w, y + h, x, y + h, r);
                ctx.arcTo(x, y + h, x, y, r);
                ctx.arcTo(x, y, x + w, y, r);
                ctx.closePath();
            }

            function paintLabel(x, y, text) {
                ctx.fillStyle = '#f8c95d';
                ctx.font = '700 18px Arial, sans-serif';
                ctx.fillText(text.toUpperCase(), x, y);
            }

            function paintValue(x, y, value) {
                ctx.fillStyle = '#ecf2ff';
                ctx.font = '700 42px Georgia, serif';
                ctx.fillText(value, x, y);
            }

            ctx.fillStyle = '#07111f';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const bg = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
            bg.addColorStop(0, '#07111f');
            bg.addColorStop(1, '#10213b');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const glowA = ctx.createRadialGradient(180, 110, 20, 180, 110, 360);
            glowA.addColorStop(0, 'rgba(248, 201, 93, 0.32)');
            glowA.addColorStop(1, 'rgba(248, 201, 93, 0)');
            ctx.fillStyle = glowA;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const glowB = ctx.createRadialGradient(980, 80, 20, 980, 80, 420);
            glowB.addColorStop(0, 'rgba(125, 211, 252, 0.22)');
            glowB.addColorStop(1, 'rgba(125, 211, 252, 0)');
            ctx.fillStyle = glowB;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            roundRect(44, 44, 1112, 542, 32);
            ctx.fillStyle = 'rgba(8,14,27,0.76)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.12)';
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.fillStyle = '#ffe7a8';
            ctx.font = '700 18px Arial, sans-serif';
            ctx.fillText('BIORRHYTHMS', 84, 118);

            ctx.fillStyle = '#ffe7a8';
            ctx.font = '700 14px Arial, sans-serif';
            roundRect(885, 86, 171, 36, 18);
            ctx.fillStyle = 'rgba(248, 201, 93, 0.12)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(248, 201, 93, 0.22)';
            ctx.stroke();
            ctx.fillStyle = '#ffe7a8';
            ctx.fillText(theme === 'light' ? 'LIGHT THEME' : 'DARK THEME', 908, 109);

            const mainHeadline = point.physical >= point.emotional && point.physical >= point.intellectual
                ? 'Physical peak'
                : point.emotional >= point.physical && point.emotional >= point.intellectual
                    ? 'Emotional peak'
                    : 'Mental peak';

            ctx.fillStyle = '#ecf2ff';
            ctx.font = '700 60px Georgia, serif';
            ctx.fillText('Biorrhythms', 84, 186);
            ctx.fillStyle = '#9bb0d0';
            ctx.font = '400 24px Georgia, serif';
            ctx.fillText(`${mainHeadline} · ${point.label}`, 84, 230);
            ctx.fillText(`Nacimiento ${birthInput} · foco ${focusInput}`, 84, 264);

            const metricCards = [
                { label: 'Físico', value: formatSignedPercent(point.physical), color: '#ff7b54' },
                { label: 'Emocional', value: formatSignedPercent(point.emotional), color: '#6ee7b7' },
                { label: 'Intelectual', value: formatSignedPercent(point.intellectual), color: '#7dd3fc' },
            ];

            metricCards.forEach((metric, idx) => {
                const x = 84 + idx * 203;
                roundRect(x, 304, 180, 108, 20);
                ctx.fillStyle = 'rgba(255,255,255,0.04)';
                ctx.fill();
                ctx.strokeStyle = 'rgba(255,255,255,0.08)';
                ctx.stroke();
                ctx.fillStyle = metric.color;
                ctx.fillRect(x + 18, 322, 22, 22);
                paintLabel(x + 18, 364, metric.label);
                paintValue(x + 18, 402, metric.value);
            });

            const sparkPoints = forecastSliceFrom(selectedIndex, 7);
            const sparkX = 752;
            const sparkY = 304;
            const sparkW = 364;
            const sparkH = 180;
            roundRect(sparkX, sparkY, sparkW, sparkH, 24);
            ctx.fillStyle = 'rgba(255,255,255,0.035)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(255,255,255,0.08)';
            ctx.stroke();

            ctx.save();
            ctx.beginPath();
            roundRect(sparkX, sparkY, sparkW, sparkH, 24);
            ctx.clip();

            ctx.strokeStyle = 'rgba(255,255,255,0.09)';
            ctx.lineWidth = 1;
            [0.25, 0.5, 0.75].forEach((ratio) => {
                const y = sparkY + sparkH * ratio;
                ctx.beginPath();
                ctx.moveTo(sparkX + 18, y);
                ctx.lineTo(sparkX + sparkW - 18, y);
                ctx.stroke();
            });

            const lineMap = [
                ['physical', '#ff7b54'],
                ['emotional', '#6ee7b7'],
                ['intellectual', '#7dd3fc'],
            ];

            lineMap.forEach(([seriesName, color]) => {
                ctx.beginPath();
                sparkPoints.forEach((pointItem, index) => {
                    const x = sparkX + 24 + (index / Math.max(1, sparkPoints.length - 1)) * (sparkW - 48);
                    const y = sparkY + sparkH / 2 - pointItem[seriesName] * (sparkH / 2 - 26);
                    if (index === 0) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                });
                ctx.strokeStyle = color;
                ctx.lineWidth = 3;
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';
                ctx.stroke();
            });

            ctx.restore();

            ctx.fillStyle = '#f8c95d';
            ctx.font = '700 18px Arial, sans-serif';
            ctx.fillText('7-DAY FORECAST', sparkX + 22, sparkY + 34);
            ctx.fillStyle = '#9bb0d0';
            ctx.font = '400 19px Georgia, serif';
            ctx.fillText('Mini ventana con la curva de más tracción.', sparkX + 22, sparkY + 64);

            ctx.fillStyle = '#9bb0d0';
            ctx.font = '400 20px Georgia, serif';
            ctx.fillText('Timeline viva · vista local · exportable a PNG', 84, 512);
            ctx.fillStyle = '#ecf2ff';
            ctx.font = '700 18px Arial, sans-serif';
            ctx.fillText('FOCUS', 84, 548);
            ctx.font = '400 24px Georgia, serif';
            ctx.fillText(selectedPointPayload().label, 84, 584);

            ctx.fillStyle = '#ecf2ff';
            ctx.font = '700 18px Arial, sans-serif';
            ctx.fillText('Biorrhythms', 864, 548);
            ctx.fillStyle = '#9bb0d0';
            ctx.font = '400 20px Georgia, serif';
            ctx.fillText('Browser-first, local, shareable.', 864, 580);

            const downloadUrl = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `biorrhythms-${point.date}.png`;
            a.click();
        }

        async function copySummary() {
            const point = selectedPointPayload();
            const summary = [
                `Biorrhythms ${point.label}`,
                `Físico: ${(point.physical * 100).toFixed(1)}%`,
                `Emocional: ${(point.emotional * 100).toFixed(1)}%`,
                `Intelectual: ${(point.intellectual * 100).toFixed(1)}%`,
            ].join('\n');
            await navigator.clipboard.writeText(summary);
        }

        function setZoom(days) {
            const total = data.window.length;
            const clampedDays = Math.min(days, total);
            const half = Math.floor(clampedDays / 2);
            const center = data.selectedIndex;
            visibleStartIndex = Math.max(0, Math.min(center - half, total - clampedDays));
            const end = Math.min(visibleStartIndex + clampedDays, total);
            visibleWindow = data.window.slice(visibleStartIndex, end);

            zoomButtons.forEach((btn) => {
                btn.classList.toggle('is-active', Number(btn.dataset.zoom) === days);
            });

            slider.min = String(visibleStartIndex);
            slider.max = String(end - 1);
            if (Number(slider.value) < visibleStartIndex) slider.value = String(visibleStartIndex);
            if (Number(slider.value) > end - 1) slider.value = String(end - 1);

            markersGroup.innerHTML = '';
            buildGrid();
            buildPaths();
        }

        zoomButtons.forEach((btn) => {
            btn.addEventListener('click', () => setZoom(Number(btn.dataset.zoom)));
        });

        setZoom(91);
        buildSpecialEvents();
        buildExtremeDays();
        updateWidgetChart();
        applyTheme(theme);
        updateWidgetEmbedSnippet();
        updateSelected(Number(slider.value));
        animateEntrance();
        syncCompatibilityUrl();

        slider.addEventListener('input', (event) => {
            updateSelected(Number(event.target.value));
        });

        jumpToTodayBtn.addEventListener('click', () => {
            slider.value = String(data.selectedIndex);
            updateSelected(data.selectedIndex);
        });

        exportPngBtn.addEventListener('click', async () => {
            await exportShareCardAsPng();
        });

        copySummaryBtn.addEventListener('click', async () => {
            await copySummary();
            copySummaryBtn.textContent = 'Resumen copiado';
            setTimeout(() => {
                copySummaryBtn.textContent = 'Copiar resumen';
            }, 1600);
        });

        copyLinkBtn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(window.location.href);
            copyLinkBtn.textContent = 'Enlace copiado';
            setTimeout(() => {
                copyLinkBtn.textContent = 'Copiar enlace';
            }, 1600);
        });

        copyWidgetBtn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(widgetSnippet.value);
            copyWidgetBtn.textContent = 'Iframe copiado';
            setTimeout(() => {
                copyWidgetBtn.textContent = 'Copiar iframe';
            }, 1600);
        });

        copyApiBtn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(apiSnippet.value);
            copyApiBtn.textContent = 'Curl copiado';
            setTimeout(() => {
                copyApiBtn.textContent = 'Copiar curl';
            }, 1600);
        });

        copyRitualBtn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(ritualText());
            copyRitualBtn.textContent = 'Ritual copiado';
            setTimeout(() => {
                copyRitualBtn.textContent = 'Copiar ritual';
            }, 1600);
        });

        themeToggleBtn.addEventListener('click', () => {
            applyTheme(theme === 'light' ? 'dark' : 'light');
        });

        partnerBirthInput.addEventListener('input', () => {
            updateCompatibility(selectedIndex);
            syncCompatibilityUrl();
        });

        exportCompatBtn.addEventListener('click', async () => {
            await exportCompatibilityCardAsPng();
        });

        storyAutoBtn.addEventListener('click', toggleStoryAuto);
    </script>
</body>
</html>
