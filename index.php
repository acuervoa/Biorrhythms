<?php

declare(strict_types=1);

define('BIORRHYTHMS_ENTRYPOINT', true);

require_once __DIR__ . '/src/autoload.php';

use Biorrhythms\ApiPayload;
use Biorrhythms\Biorrhythms;
use Biorrhythms\Compatibility;
use Biorrhythms\CriticalDays;
use Biorrhythms\DailyReading;
use Biorrhythms\DateInput;
use Biorrhythms\ExtremeDays;
use Biorrhythms\Lunar;
use Biorrhythms\MonthCalendar;
use Biorrhythms\Numerology;
use Biorrhythms\Profile;
use Biorrhythms\Timeline;
use Biorrhythms\Zodiac;

function valueToPercent(float $value): string
{
    return number_format($value * 100, 1, '.', '') . '%';
}

function valueToSignedPercent(float $value): string
{
    $sign = $value > 0 ? '+' : '';

    return $sign . number_format($value * 100, 1, '.', '') . '%';
}


$defaultBirth   = '1990-01-01';
$defaultPartner = '1991-01-01';
$todayInput     = (new DateTimeImmutable('today'))->format('Y-m-d');

// Missing or invalid dates fall back to a default, but the user is told
// instead of silently getting a reading for a different date.
$inputNotices = [];
$dateParam = static function (string $param, string $label, string $default) use (&$inputNotices): string {
    $raw = $_GET[$param] ?? null;
    if ($raw === null || $raw === '') {
        return $default;
    }
    if (DateInput::parse($raw) === null) {
        $inputNotices[] = sprintf(
            '«%s» no es una fecha válida (YYYY-MM-DD, entre %s y %s). Se usa %s.',
            $label,
            DateInput::MIN,
            DateInput::MAX,
            $default,
        );
        return $default;
    }
    return $raw;
};

$birthInput        = $dateParam('birth', 'Fecha de nacimiento', $defaultBirth);
$focusInput        = $dateParam('focus', 'Día de foco', $todayInput);
$partnerBirthInput = $dateParam('partner_birth', 'Fecha de la otra persona', $defaultPartner);

// First visit: no birth in the URL (the head script already filled it from the saved profile if there was one).
$showWelcome = ($_GET['birth'] ?? '') === '';
$birthIsValidInitial = is_string($_GET['birth'] ?? null) && DateInput::parse($_GET['birth']) !== null;

$compatPresetInput = $_GET['preset'] ?? 'custom';
$compatPresetInput = is_string($compatPresetInput) && in_array($compatPresetInput, ApiPayload::PRESETS, true) ? $compatPresetInput : 'custom';
$embedMode   = ($_GET['embed'] ?? '') === '1' || ($_GET['view'] ?? '') === 'widget';
$widgetTheme = ($_GET['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';

$birthDate        = DateInput::parse($birthInput);
$focusDate        = DateInput::parse($focusInput);
$partnerBirthDate = DateInput::parse($partnerBirthInput);
$bio              = new Biorrhythms();

$windowRadius = ApiPayload::WINDOW_RADIUS;
$window       = Timeline::window($bio, $birthDate, $focusDate, $windowRadius);

$focusValues        = Timeline::values($bio, $birthDate, $focusDate);
$partnerFocusValues = Timeline::values($bio, $partnerBirthDate, $focusDate);

$selectedIndex = $windowRadius;
$selectedPoint = $window[$selectedIndex];

$profileLabel = Profile::labelEs($selectedPoint);

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

// Extreme-day threshold (share of the mean, 0.5-0.99). Default 0.95 keeps the original list.
$extremeThreshold = 0.95;
$rawExtreme       = $_GET['extreme'] ?? null;
if ($rawExtreme !== null && $rawExtreme !== '') {
    if (is_string($rawExtreme) && is_numeric($rawExtreme) && (float) $rawExtreme >= 0.5 && (float) $rawExtreme <= 0.99) {
        $extremeThreshold = (float) $rawExtreme;
    } else {
        $inputNotices[] = '«extreme» debe ser un número entre 0.5 y 0.99. Se usa 0.95.';
    }
}
$extremePercent = (int) round($extremeThreshold * 100);
$cycleExtremeDays = ExtremeDays::find($bio, $birthDate, $birthDate, $extremeThreshold);
$exploreWindow = Timeline::window($bio, $birthDate, $focusDate, ApiPayload::WINDOW_RADIUS + 91);

$focusReading  = DailyReading::forDate($birthDate, $focusDate, $window);
$personal      = $focusReading['personal'];
$sunSign       = Zodiac::sunSign($birthDate);
$lifePath      = Numerology::lifePath($birthDate);
$lifePathInfo  = Numerology::meaning($lifePath);
$moon          = Lunar::phase($focusDate);
$birthIsExample = $showWelcome;

$month       = MonthCalendar::build($birthDate, $focusDate);
$prevMonth   = $focusDate->modify('first day of previous month')->format('Y-m-d');
$nextMonth   = $focusDate->modify('first day of next month')->format('Y-m-d');
$currentQuery = array_filter(
    $_GET,
    static fn (mixed $value): bool => is_scalar($value),
);
$preservedQueryParams = array_filter(
    $currentQuery,
    static fn (string|int $key): bool => !in_array((string) $key, ['birth', 'focus', 'partner_birth', 'preset'], true),
    ARRAY_FILTER_USE_KEY,
);
$buildDateLink = static function (string $focus, string $workspace) use (
    $currentQuery,
    $birthInput,
    $birthIsValidInitial,
    $showWelcome,
    $partnerBirthInput,
    $compatPresetInput,
): string {
    $params = $currentQuery;
    if ($birthIsValidInitial) {
        $params['birth'] = $birthInput;
    } elseif ($showWelcome) {
        unset($params['birth']);
    }
    $params['focus'] = $focus;
    $params['partner_birth'] = $partnerBirthInput;
    if ($compatPresetInput !== 'custom') {
        $params['preset'] = $compatPresetInput;
    } else {
        unset($params['preset']);
    }

    return '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) . '#' . $workspace;
};
$monthLink = static fn (string $focus): string => $buildDateLink($focus, 'explorar');
$readingDateLink = static fn (string $focus): string => $buildDateLink($focus, 'lectura');
$readingWeekdays = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
$readingWeekdaysShort = [1 => 'lun', 2 => 'mar', 3 => 'mié', 4 => 'jue', 5 => 'vie', 6 => 'sáb', 7 => 'dom'];
$readingMonths = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
$readingWeekday = $readingWeekdays[(int) $focusDate->format('N')];
$readingDateLabel = sprintf('%d %s %s', $focusDate->format('j'), $readingMonths[(int) $focusDate->format('n')], $focusDate->format('Y'));
$readingDayOfYear = (int) $focusDate->format('z') + 1;
$readingYearLength = $focusDate->format('L') === '1' ? 366 : 365;
$previousDayDate = $focusDate->modify('-1 day');
$nextDayDate = $focusDate->modify('+1 day');
$criticalAhead = CriticalDays::find($birthDate, $focusDate, $focusDate->modify('+1 day'));
$icsLink = 'calendar.php?' . http_build_query(['birth' => $birthInput, 'from' => $focusInput, 'days' => 90], '', '&', PHP_QUERY_RFC3986);

$seriesData = [
    'windowRadius' => $windowRadius,
    'window' => $window,
    'exploreWindow' => $exploreWindow,
    'selectedIndex' => $selectedIndex,
    'selectedPoint' => $selectedPoint,
    'profileLabel' => $profileLabel,
    'birthInput' => $birthInput,
    'focusInput' => $focusInput,
    'partnerBirthInput' => $partnerBirthInput,
    'compatPresetInput' => $compatPresetInput,
    'focusCompatibility' => $focusCompatibility,
    'cycleExtremeDays' => $cycleExtremeDays,
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biorrhythms</title>
<?php if (!$embedMode): ?>
    <link rel="manifest" href="manifest.webmanifest">
    <meta name="theme-color" content="#07111f">
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">
    <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
    <script src="assets/profile.js"></script>
    <script>
        // Fill a missing birth from the saved profile and a missing focus with the visitor's local day,
        // then remember the birth date in the URL. Never overrides parameters that are already present.
        (function () {
            var P = window.BiorrhythmsProfile;
            var store = null;
            try { store = window.localStorage; } catch (e) { /* storage blocked */ }
            var next = P.resolveUrl(window.location.href, store ? P.load(store) : null, P.localDay(), <?= json_encode($todayInput) ?>);
            if (next) {
                window.location.replace(next);
            } else if (store) {
                P.rememberBirth(store, window.location.href);
            }
        })();
    </script>
<?php endif; ?>
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
            --physical: #b83c23;
            --emotional: #176b54;
            --intellectual: #145aa3;
            --accent: #815000;
        }

        * { box-sizing: border-box; }

        :focus-visible {
            outline: 3px solid var(--focus-ring, var(--accent));
            outline-offset: 3px;
        }

        ::selection {
            color: var(--bg);
            background: var(--accent);
        }

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
            font-size: 16px;
            line-height: 1.55;
        }

        p { max-width: 72ch; }

        h1, h2, h3 { text-wrap: balance; }

        body[data-theme="light"] {
            background:
                radial-gradient(circle at top left, rgba(240, 95, 59, 0.18), transparent 26%),
                radial-gradient(circle at top right, rgba(25, 118, 210, 0.18), transparent 28%),
                radial-gradient(circle at bottom center, rgba(36, 154, 121, 0.14), transparent 24%),
                linear-gradient(160deg, #fdf8f2 0%, #f5f3ee 45%, #e7ecf6 100%);
        }

        body[data-theme="light"] { --focus-ring: #145aa3; }

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
        }

        body[data-theme="light"] .card {
            box-shadow: 0 4px 24px rgba(17, 24, 39, 0.10);
            border-color: rgba(16, 24, 40, 0.14);
        }

        /* Fix all white-tinted surfaces → dark-tinted */
        body[data-theme="light"] .detail,
        body[data-theme="light"] .stat,
        body[data-theme="light"] .hero-stat,
        body[data-theme="light"] .widget-mini-stat,
        body[data-theme="light"] .widget-panel,
        body[data-theme="light"] .extreme-item,
        body[data-theme="light"] .compat-day,
        body[data-theme="light"] .compat-heatmap-cell,
        body[data-theme="light"] .decision-panel,
        body[data-theme="light"] .decision-calendar,
        body[data-theme="light"] .event-row,
        body[data-theme="light"] .api-panel,
        body[data-theme="light"] .controls-bar label,
        body[data-theme="light"] .share-metric,
        body[data-theme="light"] .forecast-day,
        body[data-theme="light"] .widget-chart-shell {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.12);
        }

        /* story-stat: hardcoded dark bg must flip to light */
        body[data-theme="light"] .story-stat {
            background: rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 0, 0, 0.12);
        }

        /* story-slide: no background needed on light */
        body[data-theme="light"] .story-slide {
            background: transparent;
        }

        /* zoom buttons */
        body[data-theme="light"] .decision-day .decision-action {
            color: #7a4800;
        }

        body[data-theme="light"] .zoom-btn {
            border-color: rgba(0, 0, 0, 0.14);
            background: rgba(0, 0, 0, 0.04);
        }

        body[data-theme="light"] .zoom-btn.is-active {
            background: rgba(180, 110, 0, 0.12);
            border-color: rgba(180, 110, 0, 0.4);
            color: #7a4800;
        }

        /* secondary buttons */
        body[data-theme="light"] .secondary-btn {
            border-color: rgba(0, 0, 0, 0.18);
            background: rgba(0, 0, 0, 0.04);
        }

        body[data-theme="light"] .secondary-btn:hover {
            background: rgba(0, 0, 0, 0.08);
        }

        /* widget note */
        body[data-theme="light"] .widget-note,
        body[data-theme="light"] .ritual-note {
            background: rgba(199, 136, 39, 0.10);
            border-color: rgba(199, 136, 39, 0.25);
            color: #7a4800;
        }

        /* Chart SVG grid lines */
        body[data-theme="light"] .grid-line {
            stroke: rgba(0, 0, 0, 0.13);
        }

        body[data-theme="light"] .zero-line {
            stroke: rgba(140, 90, 0, 0.45);
        }

        body[data-theme="light"] .marker {
            stroke: rgba(0, 0, 0, 0.25);
        }

        body[data-theme="light"] .marker-label {
            fill: rgba(0, 0, 0, 0.6);
        }

        /* Inputs & textareas */
        body[data-theme="light"] input[type="date"],
        body[data-theme="light"] textarea {
            background: rgba(0, 0, 0, 0.04);
            border-color: rgba(0, 0, 0, 0.15);
            color: #18202b;
        }

        /* API/snippet code areas */
        body[data-theme="light"] .api-snippet,
        body[data-theme="light"] .api-snippet textarea,
        body[data-theme="light"] .widget-code textarea {
            background: rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 0, 0, 0.15);
        }

        /* API JSON preview */
        body[data-theme="light"] .api-preview {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.14);
            color: #1e2d3d;
        }

        body[data-theme="light"] .api-preview-toggle {
            border-color: rgba(0, 0, 0, 0.14);
            background: rgba(0, 0, 0, 0.04);
        }

        /* Compat bars */
        body[data-theme="light"] .compat-fill {
            opacity: 0.7;
        }

        /* Status pills */
        body[data-theme="light"] .status-pill {
            background: rgba(0, 120, 70, 0.08);
            border-color: rgba(0, 120, 70, 0.22);
            color: #005030;
        }

        /* Hero chip buttons */
        body[data-theme="light"] .hero-chip,
        body[data-theme="light"] .event-chip,
        body[data-theme="light"] .chip {
            border-color: rgba(0, 0, 0, 0.15);
            background: rgba(0, 0, 0, 0.05);
        }

        /* colored borders: bump opacity + shift to darker hue on light bg */
        body[data-theme="light"] .hero-forecast-chip.is-best {
            border-color: rgba(0, 130, 80, 0.45);
            background: rgba(0, 130, 80, 0.07);
        }
        body[data-theme="light"] .hero-forecast-chip.is-best strong { color: #006040; }

        body[data-theme="light"] .hero-forecast-chip.is-worst {
            border-color: rgba(200, 60, 20, 0.45);
            background: rgba(200, 60, 20, 0.07);
        }
        body[data-theme="light"] .hero-forecast-chip.is-worst strong { color: #922010; }

        body[data-theme="light"] .forecast-day.is-best,
        body[data-theme="light"] .decision-day.is-best,
        body[data-theme="light"] .compat-day.is-best,
        body[data-theme="light"] .compat-heatmap-cell.is-best {
            border-color: rgba(140, 90, 0, 0.55);
            box-shadow: 0 0 0 1px rgba(140, 90, 0, 0.22), 0 6px 16px rgba(140, 90, 0, 0.08);
        }

        body[data-theme="light"] .forecast-day.is-worst,
        body[data-theme="light"] .decision-day.is-worst,
        body[data-theme="light"] .compat-day.is-worst,
        body[data-theme="light"] .compat-heatmap-cell.is-worst {
            border-color: rgba(200, 60, 20, 0.5);
        }

        body[data-theme="light"] .event-chip.is-peak {
            border-color: rgba(0, 130, 80, 0.5);
        }
        body[data-theme="light"] .event-chip.is-valley {
            border-color: rgba(200, 60, 20, 0.5);
        }
        body[data-theme="light"] .event-chip.is-zero {
            border-color: rgba(0, 100, 160, 0.35);
        }

        body[data-theme="light"] .extreme-item.is-peak {
            border-color: rgba(0, 130, 80, 0.45);
        }
        body[data-theme="light"] .extreme-item.is-valley {
            border-color: rgba(200, 60, 20, 0.45);
        }

        /* kicker / story-kicker: yellow pill → amber on light */
        body[data-theme="light"] .kicker,
        body[data-theme="light"] .story-kicker {
            background: rgba(160, 90, 0, 0.10);
            border-color: rgba(160, 90, 0, 0.28);
            color: #7a4800;
        }

        /* share-badge inside widget preview (light bg) - not inside dark share card canvas */
        body[data-theme="light"] .widget-preview-mini .share-badge {
            background: rgba(160, 90, 0, 0.10);
            border-color: rgba(160, 90, 0, 0.28);
            color: #7a4800;
        }

        /* share-preview is always dark — undo any light overrides inside it */
        body[data-theme="light"] .share-preview .share-metric {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.10);
        }

        body[data-theme="light"] .share-preview .share-badge {
            background: rgba(248, 201, 93, 0.08);
            border-color: rgba(248, 201, 93, 0.18);
            color: #ffe7a8;
        }

        /* hero-callout summary arrow + text */
        body[data-theme="light"] .hero-callout summary {
            color: #7a4800;
        }

        body[data-theme="light"] .hero-callout {
            background: linear-gradient(135deg, rgba(160,90,0,0.07), rgba(0,80,140,0.04)), rgba(0,0,0,0.03);
            border-color: rgba(160, 90, 0, 0.18);
        }

        /* hero-chip strong label (normally #ffe7a8) */
        body[data-theme="light"] .hero-chip strong {
            color: #7a4800;
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
            min-height: 32px;
            cursor: pointer;
        }

        .chart-text-alternative {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation: none !important;
                transition: none !important;
            }

            .hero-main, .controls, .panel, .detail, .share-card {
                opacity: 1 !important;
                transform: none !important;
            }
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

        .input-notice {
            margin: 0 0 14px;
            padding: 12px 16px;
            border: 1px solid var(--accent);
            border-radius: 14px;
            background: rgba(248, 201, 93, 0.12);
            color: var(--text);
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .input-notice p { margin: 0; }
        .input-notice p + p { margin-top: 6px; }

        .disclaimer {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .context-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
        }

        .context-item {
            padding: 14px 16px;
            border: 1px solid var(--stroke);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.03);
        }

        .context-item small { display: block; color: var(--muted); font-size: 0.875rem; }
        .context-item strong { display: block; margin: 4px 0; font-size: 1.15rem; }
        .context-item p { margin: 0; color: var(--muted); font-size: 0.875rem; line-height: 1.4; }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .month-grid {
            width: 100%;
            margin: 10px 0 0;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .month-calendar-scroll { max-width: 100%; overflow-x: auto; }

        .month-dow {
            text-align: center;
            color: var(--muted);
            font-size: 0.875rem;
            padding: 0 0 2px;
        }

        .month-cell {
            height: 58px;
            padding: 0;
            border: 1px solid var(--stroke);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            font-size: 0.875rem;
            line-height: 1.3;
        }

        .month-cell-link {
            display: block;
            min-height: 56px;
            padding: 6px 8px;
            color: inherit;
            text-decoration: none;
        }

        .month-cell strong { display: block; font-size: 0.92rem; }
        .month-cell[data-level="high"] { background: rgba(110, 231, 183, 0.18); border-color: rgba(110, 231, 183, 0.55); }
        .month-cell[data-level="mid"]  { background: rgba(248, 201, 93, 0.14); border-color: rgba(248, 201, 93, 0.45); }
        .month-cell[data-level="low"]  { background: rgba(255, 123, 84, 0.16); border-color: rgba(255, 123, 84, 0.5); }
        .month-cell-link.is-focus { outline: 2px solid var(--text); outline-offset: 1px; }
        .month-cell.is-pad { background: transparent; border-color: transparent; }
        .month-legend { margin: 12px 0 0; color: var(--muted); font-size: 0.875rem; }

        .widget-data-details { margin-top: 10px; }
        .widget-data-details summary { min-height: 44px; padding: 10px 0; cursor: pointer; }
        .widget-data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .widget-data-table th, .widget-data-table td { padding: 8px; border-bottom: 1px solid var(--stroke); text-align: start; }

        @media (max-width: 520px) {
            .month-grid { min-width: 420px; }
        }

        .diary-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 14px;
        }

        .diary-form label { display: flex; flex-direction: column; gap: 4px; font-size: 0.82rem; color: var(--muted); }
        .diary-form select,
        .diary-form input[type="date"] {
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid var(--stroke);
            background: var(--panel-strong);
            color: var(--text);
            font: inherit;
        }

        .diary-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; align-items: center; }
        .diary-actions label.secondary-btn { font: inherit; display: inline-flex; align-items: center; }
        .diary-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; margin-top: 8px; }
        .diary-table th,
        .diary-table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--stroke); }
        .diary-table th { color: var(--muted); font-weight: 600; font-size: 0.78rem; }
        .diary-caveat { margin: 12px 0 0; color: var(--muted); font-size: 0.8rem; line-height: 1.5; }

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
            /* force dark-theme vars regardless of page theme */
            --text: #e2e8f0;
            --muted: rgba(255, 255, 255, 0.55);
            color: #e2e8f0;
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
            width: 100%;
            margin: 0;
            padding: 0;
            gap: 0;
        }

        body.embed-view .shell > :not(#herramientas) {
            display: none;
        }

        body.embed-view .widget-card {
            margin-top: 0;
            padding: 0;
            border: none;
            background: transparent;
        }

        body.embed-view .widget-head,
        body.embed-view .widget-code,
        body.embed-view .widget-frame,
        body.embed-view .api-card {
            display: none;
        }

        body.embed-view .widget-grid {
            grid-template-columns: 1fr;
        }

        body.embed-view .widget-grid > div.widget-panel {
            display: none;
        }

        body.embed-view .widget-panel {
            border: none;
            border-radius: 0;
            background: transparent;
            padding: 16px;
        }

        body.embed-view {
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(255, 123, 84, 0.18), transparent 30%),
                radial-gradient(circle at top right, rgba(125, 211, 252, 0.18), transparent 28%),
                radial-gradient(circle at bottom center, rgba(110, 231, 183, 0.12), transparent 25%),
                linear-gradient(160deg, #040812 0%, #07111f 45%, #0c172a 100%);
        }

        body.embed-view[data-theme="light"] {
            background:
                radial-gradient(circle at top left, rgba(240, 95, 59, 0.18), transparent 26%),
                radial-gradient(circle at top right, rgba(25, 118, 210, 0.18), transparent 28%),
                radial-gradient(circle at bottom center, rgba(36, 154, 121, 0.14), transparent 24%),
                linear-gradient(160deg, #fdf8f2 0%, #f5f3ee 45%, #e7ecf6 100%);
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
            font-size: 0.875rem;
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
            min-height: 44px;
            font-size: 0.875rem;
            padding: 8px 14px;
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
            transform: scaleX(0);
            transform-origin: left center;
            transition: transform linear;
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
            .controls-grid,
            .stat-grid,
            .share-metrics {
                grid-template-columns: 1fr;
            }

            /* strips always scroll horizontally — never collapse */
            .compat-strip {
                display: flex;
                overflow-x: auto;
                gap: 10px;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
            }

            .compat-day {
                flex: 0 0 120px;
                min-width: 120px;
            }

            .compat-heatmap-grid {
                display: flex;
                overflow-x: auto;
                gap: 8px;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
            }

            .compat-heatmap-cell {
                flex: 0 0 90px;
                min-width: 90px;
            }
        }

        @media (max-width: 600px) {
            .shell {
                padding: 12px 10px;
                gap: 12px;
                overflow-x: hidden;
            }

            /* Hero */
            .hero-body {
                flex-direction: column;
                gap: 16px;
            }

            .hero-right {
                flex: unset;
                width: 100%;
            }

            .hero-stats-inline {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .hero-forecast-chips {
                flex-wrap: wrap;
                gap: 6px;
            }

            h1 { font-size: 1.5rem; }
            .stat strong { font-size: 1.3rem; }

            /* Controls */
            .controls-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .zoom-btns {
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            /* Detail grid: date full width, 3 rhythms below */
            .detail-grid {
                grid-template-columns: 1fr;
            }

            /* Compat */
            .compat-score { font-size: 1rem; gap: 10px; padding: 14px; }
            .compat-score small { font-size: 0.72rem; }
            .compat-score strong { font-size: clamp(2.2rem, 10vw, 3rem); }
            .compat-score p { font-size: 0.95rem; line-height: 1.5; overflow-wrap: anywhere; }

            .compat-day {
                flex: 0 0 110px;
                min-width: 110px;
            }

            .compat-heatmap-cell {
                flex: 0 0 80px;
                min-width: 80px;
            }

            /* Decision */
            .decision-panel strong {
                overflow-wrap: break-word;
                word-break: break-word;
            }

            .decision-grid {
                grid-template-columns: 1fr;
            }

            /* Events */
            .event-row {
                grid-template-columns: 72px 1fr;
                gap: 10px;
                padding: 10px 12px;
            }

            .event-chip {
                padding: 4px 8px;
                font-size: 0.74rem;
            }

            /* Extreme days */
            .extreme-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .extreme-item-score {
                font-size: 1.3rem;
            }
        }

        /* Bitácora de observación */
        :root {
            color-scheme: light;
            --bg: #f8f7ee;
            --paper: #f8f7ee;
            --paper-muted: #f4f0e7;
            --surface: #f8f5ed;
            --stroke: #d3cbc1;
            --text: #29231f;
            --muted: #716960;
            --physical: #b84d39;
            --emotional: #24776c;
            --intellectual: #4168a0;
            --accent: #9f4633;
            --focus-ring: #315d96;
            --radius: 10px;
        }

        body {
            color: var(--text);
            background: var(--bg);
            font-family: "Avenir Next", Avenir, "Segoe UI", sans-serif;
            line-height: 1.5;
            font-size: 16px;
        }

        body[data-theme="light"] {
            color-scheme: light;
            --bg: #f8f7ee;
            --paper: #f8f7ee;
            --paper-muted: #f4f0e7;
            --surface: #f8f5ed;
            --stroke: #d3cbc1;
            --text: #29231f;
            --muted: #716960;
            --physical: #b84d39;
            --emotional: #24776c;
            --intellectual: #4168a0;
            --accent: #9f4633;
            --focus-ring: #315d96;
            background: var(--bg);
        }

        body[data-theme="dark"] {
            color-scheme: dark;
            --bg: #202522;
            --paper: #292f2b;
            --paper-muted: #303733;
            --surface: #2a302c;
            --stroke: #505a52;
            --text: #f2eee4;
            --muted: #c0c1b8;
            --physical: #e27a61;
            --emotional: #69b8a5;
            --intellectual: #86a9dc;
            --accent: #efaa85;
            --focus-ring: #a8c5f2;
            background: var(--bg);
        }

        body.embed-view {
            overflow: hidden;
            background: transparent;
        }

        html { scroll-behavior: smooth; scrollbar-color: var(--stroke) var(--bg); }
        ::selection { color: var(--paper); background: var(--text); }
        a { color: inherit; text-decoration-color: color-mix(in srgb, currentColor 42%, transparent); text-underline-offset: 0.22em; }
        a:hover { text-decoration-color: currentColor; }
        :focus-visible { outline: 3px solid var(--focus-ring); outline-offset: 3px; }
        p { max-width: 72ch; }
        h1, h2, h3, .wordmark, .stat strong, .date-rail-item strong {
            font-family: Georgia, "Times New Roman", serif;
            text-wrap: balance;
        }

        .shell {
            width: min(1388px, calc(100% - 64px));
            margin: 18px auto 64px;
            padding: 0;
            display: grid;
            gap: 20px;
            min-width: 0;
        }

        .site-header {
            min-height: 64px;
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 36px;
            border-bottom: 1px solid var(--stroke);
        }

        .wordmark {
            width: fit-content;
            color: var(--text);
            font-size: clamp(1.65rem, 2.7vw, 2.75rem);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.035em;
            text-decoration: none;
        }

        .primary-nav { display: flex; align-items: center; justify-self: end; gap: 30px; }
        .primary-nav a {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            color: var(--muted);
            font-size: 0.95rem;
            text-decoration: none;
            border-bottom: 2px solid transparent;
        }
        .primary-nav a[aria-current="page"], .primary-nav a:hover { color: var(--text); border-color: var(--physical); }
        .theme-toggle { justify-self: end; margin: 0; padding: 8px 12px; }

        .welcome-sheet {
            width: min(680px, 100%);
            margin: clamp(32px, 9vh, 104px) auto 0;
            padding: clamp(26px, 5vw, 52px);
            background: var(--paper);
            border: 1px solid var(--stroke);
            border-radius: var(--radius);
            box-shadow: 0 14px 36px rgba(47, 39, 31, 0.08);
        }
        .welcome-sheet .section-label { margin: 0 0 8px; color: var(--physical); font-size: 0.8rem; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; }
        .welcome-sheet h1 { margin: 0; font-size: clamp(2.4rem, 6vw, 4.2rem); line-height: 1.02; letter-spacing: -0.035em; }
        .welcome-sheet > p:not(.section-label) { margin: 16px 0 28px; color: var(--muted); }
        .welcome-form { display: grid; gap: 8px; }
        .welcome-form > label, .date-control-form label { color: var(--text); font-size: 0.9rem; font-weight: 600; }
        .welcome-form-row { display: flex; gap: 10px; align-items: stretch; }
        .welcome-form-row .bar-submit { min-width: 136px; white-space: nowrap; }
        .welcome-form small { color: var(--muted); }

        .journal-layout { display: grid; grid-template-columns: minmax(120px, 0.16fr) minmax(0, 1fr); gap: 24px; align-items: stretch; }
        .date-rail {
            display: grid;
            align-content: start;
            gap: 10px;
            padding-top: 40px;
            padding-right: 16px;
            border-right: 1px solid var(--stroke);
        }
        .date-rail-item {
            min-width: 0;
            min-height: 124px;
            display: grid;
            align-content: center;
            justify-items: center;
            gap: 3px;
            padding: 12px 8px;
            color: var(--muted);
            text-align: center;
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: var(--radius);
        }
        .date-rail-item:hover { background: var(--paper-muted); }
        .date-rail-item.is-selected { color: var(--physical); background: color-mix(in srgb, var(--physical) 8%, var(--paper)); border-color: color-mix(in srgb, var(--physical) 24%, var(--stroke)); }
        #dateRailPrevious { min-height: 154px; }
        #dateRailCurrent { min-height: 163px; }
        #dateRailNext { min-height: 124px; }
        .date-rail-item strong { font-size: 2.25rem; font-weight: 400; line-height: 1; font-variant-numeric: lining-nums tabular-nums; }
        .date-rail-item span, .date-rail-item small { font-size: 0.76rem; letter-spacing: 0.05em; text-transform: uppercase; }
        .date-rail-item small { font-weight: 700; }

        body[data-theme="light"] .card,
        body[data-theme="dark"] .card,
        body:not([data-theme]) .card {
            color: var(--text);
            background: var(--paper);
            border: 1px solid var(--stroke);
            border-radius: var(--radius);
            box-shadow: 0 12px 28px rgba(47, 39, 31, 0.07);
        }
        body[data-theme="light"] article.hero-main.card {
            background-color: var(--paper);
            background-image: linear-gradient(rgba(248, 247, 238, 0.58), rgba(248, 247, 238, 0.58)), url("assets/paper-texture.webp");
            background-size: 512px 512px;
            background-repeat: repeat;
        }
        .hero { margin: 4px 0 0; }
        .hero-main { min-width: 0; padding: 38px clamp(24px, 4.8vw, 72px); overflow: visible; }
        .reading-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--stroke); }
        .reading-weekday { margin: 0 0 4px; color: var(--muted); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.17em; text-transform: uppercase; }
        .reading-heading h1 { margin: 0; font-size: clamp(3.4rem, 6vw, 5.2rem); font-weight: 400; line-height: 0.98; letter-spacing: -0.04em; font-variant-numeric: lining-nums tabular-nums; }
        .calendar-day-stamp { margin: 8px 0 0; color: var(--muted); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.12em; text-align: right; text-transform: uppercase; white-space: nowrap; }
        .model-values-heading { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; margin: 26px 0 4px; }
        .model-values-heading h2 { margin: 0; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
        .model-values-heading p { margin: 0; color: var(--muted); font-size: 0.88rem; text-align: right; }
        .hero-stats-inline { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; margin: 12px 0 0; }
        .stat { min-width: 0; padding: 10px 18px 8px; text-align: center; border: 0 !important; border-radius: 0 !important; background: transparent !important; box-shadow: none !important; }
        .stat + .stat { border-left: 1px solid var(--stroke) !important; }
        .stat small { display: flex; align-items: center; justify-content: center; gap: 9px; margin: 0; color: var(--text); font-size: 0.86rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .stat small::before { width: 11px; height: 11px; flex: 0 0 11px; content: ""; border-radius: 50%; background: currentColor; }
        .stat-physical small, .stat-physical strong { color: var(--physical); }
        .stat-emotional small, .stat-emotional strong { color: var(--emotional); }
        .stat-intellectual small, .stat-intellectual strong { color: var(--intellectual); }
        .stat strong { display: block; margin-top: 6px; font-size: clamp(3rem, 4.8vw, 4.8rem); font-weight: 400; line-height: 1; letter-spacing: -0.035em; font-variant-numeric: lining-nums tabular-nums; }
        .stat span { display: block; margin-top: 6px; color: var(--muted); font-size: 0.78rem; }

        .controls-bar {
            display: grid;
            grid-template-columns: minmax(230px, 0.75fr) minmax(320px, 1.25fr);
            align-items: center;
            gap: 24px;
            margin: 8px 0 0;
            padding: 10px 0;
            background: transparent !important;
            border: 0;
            border-top: 1px solid var(--stroke);
            border-radius: 0;
            box-shadow: none !important;
        }
        .date-control-form { display: flex; align-items: end; gap: 10px; min-width: 0; }
        .controls-bar label { background: transparent !important; border: 0 !important; }
        .date-control-form label { min-width: 0; flex: 1; }
        .date-control-form .bar-submit { flex: 0 0 auto; margin: 0; }
        .controls-bar .bar-slider { flex: none; display: grid; grid-auto-rows: min-content; align-self: center; gap: 2px; min-width: 0; min-height: 0; }
        .bar-slider label { color: var(--text); font-size: 0.84rem; font-weight: 600; }
        .bar-slider input[type="range"] { min-height: 22px; accent-color: var(--physical); }
        .footer-note { color: var(--muted); font-size: 0.8rem; }
        .disclaimer { max-width: 86ch; margin: 18px 0 0; padding-top: 12px; border-top: 1px solid var(--stroke); color: var(--muted); font-size: 0.78rem; }

        .panel { padding: 22px 26px; }
        .hero-main .chart-wrap { margin: 0; padding: 8px 0 8px; border: 0; border-radius: 0; background: transparent; box-shadow: none; }
        .hero-main .chart-wrap .panel-head h2 { margin: 0; font-size: 1rem; letter-spacing: 0.15em; text-transform: uppercase; }
        .panel-head { align-items: center; margin-bottom: 10px; }
        .panel-head h2, .events-head h2, .extreme-head h2, .compat-head h2, .decision-head h2, .ritual-head h2 { font-size: 1.35rem; }
        .panel-head p, .events-head p, .extreme-head p { color: var(--muted); font-size: 0.88rem; }
        .button-row { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .zoom-btns { flex-wrap: wrap; gap: 4px; }
        .zoom-btn { min-height: 36px; padding: 6px 9px; border-color: var(--stroke); background: transparent; color: var(--muted); font-size: 0.75rem; letter-spacing: 0; }
        .zoom-btn.is-active { color: var(--text); background: color-mix(in srgb, var(--physical) 12%, var(--paper)); border-color: color-mix(in srgb, var(--physical) 42%, var(--stroke)); }
        .chart { width: 100%; height: auto; max-height: 360px; }
        .chart > rect { fill: none !important; }
        .grid-line { stroke: color-mix(in srgb, var(--stroke) 80%, transparent); }
        .zero-line { stroke: color-mix(in srgb, var(--text) 46%, transparent); stroke-dasharray: 0; }
        .marker { stroke: var(--muted); stroke-dasharray: 3 5; }
        .marker-label { fill: var(--muted); font-family: "Avenir Next", Avenir, "Segoe UI", sans-serif; font-size: 11px; }
        .axis-label { fill: var(--muted); font-family: "Avenir Next", Avenir, "Segoe UI", sans-serif; font-size: 12px; }
        .path { stroke-width: 3; }
        .legend { justify-content: center; margin-top: 4px; color: var(--text); font-family: Georgia, "Times New Roman", serif; font-size: 1rem; }
        .legend .dot { width: 10px; height: 10px; }
        .chart-text-alternative { max-width: 72ch; margin: 14px auto 0; color: var(--muted); font-size: 0.82rem; }

        .reflection-note { padding: 20px 0 16px; border-bottom: 1px solid var(--stroke); }
        .reflection-note h2 { margin: 0 0 5px; color: var(--muted); font-family: Georgia, "Times New Roman", serif; font-size: 1rem; font-weight: 400; letter-spacing: 0.32em; text-transform: uppercase; }
        .reflection-note p { margin: 0; font-size: clamp(1.05rem, 1.6vw, 1.35rem); }
        .tool-index { display: grid; grid-template-columns: minmax(200px, 0.55fr) 1.45fr; gap: 24px; padding: 22px 0 8px; scroll-margin-top: 20px; }
        .tool-index h2 { margin: 0; font-size: 1.45rem; }
        .tool-index p { margin: 4px 0 0; color: var(--muted); font-size: 0.88rem; }
        .tool-links { display: flex; flex-wrap: wrap; gap: 7px; }
        .tool-links a { min-height: 38px; display: inline-flex; align-items: center; padding: 6px 10px; border: 1px solid var(--stroke); border-radius: 999px; color: var(--text); background: var(--paper); font-size: 0.8rem; text-decoration: none; }
        .tool-links a:hover { border-color: var(--physical); }
        .forecast-card, .events-card, .extreme-card, .compat-card, .decision-card, .ritual-card, #context, #monthCalendar, #diary, #chrono, #prefs, .story-card, .share-card, .widget-card, .api-card { padding: 22px; scroll-margin-top: 18px; }
        .forecast-card .hero-forecast-chips { gap: 8px; }
        .forecast-card .hero-forecast-chip { border-color: var(--stroke); background: var(--paper-muted); color: var(--muted); }
        .forecast-card .hero-forecast-chip strong { color: var(--text); }
        .status-pill { color: var(--text); border-color: var(--stroke); background: var(--paper-muted); }
        .event-row, .extreme-item, .context-item, .decision-panel, .decision-calendar, .ritual-panel, .api-panel, .widget-panel, .story-stat, .share-metric, .compat-day, .compat-heatmap-cell, .forecast-day, .widget-mini-stat, .widget-chart-shell {
            color: var(--text);
            border-color: var(--stroke);
            background: var(--paper-muted);
            box-shadow: none;
        }
        .input-notice[role="alert"] { border-color: var(--physical); color: var(--text); background: var(--paper); }

        button, .secondary-btn, .api-preview-toggle, .month-nav a, .diary-actions .secondary-btn {
            min-height: 40px;
            padding: 8px 12px;
            color: var(--text);
            border: 1px solid var(--stroke);
            border-radius: 7px;
            background: var(--paper);
            box-shadow: none;
            font: inherit;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover, .secondary-btn:hover, .api-preview-toggle:hover, .month-nav a:hover { background: var(--paper-muted); border-color: var(--muted); }
        .bar-submit, .welcome-form .bar-submit { color: var(--paper); background: var(--text); border-color: var(--text); }
        .bar-submit:hover, .welcome-form .bar-submit:hover { color: var(--paper); background: #403830; border-color: #403830; }
        input[type="date"], input[type="time"], select, textarea {
            min-height: 42px;
            padding: 8px 10px;
            color: var(--text);
            border: 1px solid var(--stroke);
            border-radius: 6px;
            background: var(--paper);
            font: inherit;
        }
        .api-snippet textarea, .widget-code textarea, .api-preview { color: var(--text); background: var(--paper-muted); border-color: var(--stroke); }
        .compat-strip, .compat-heatmap-grid, .month-calendar-scroll { max-width: 100%; overflow-x: auto; overscroll-behavior-inline: contain; }

        @media (max-width: 820px) {
            .shell { width: min(100% - 40px, 720px); }
            .site-header { grid-template-columns: 1fr auto; gap: 10px; padding-bottom: 8px; }
            .primary-nav { grid-column: 1 / -1; grid-row: 2; justify-content: flex-start; gap: 22px; }
            .theme-toggle { grid-column: 2; grid-row: 1; }
            .journal-layout { grid-template-columns: minmax(0, 1fr); gap: 12px; }
            .hero-main { grid-row: 1; }
            .date-rail { grid-row: 2; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; padding: 8px 0 0; border: 0; border-top: 1px solid var(--stroke); }
            .date-rail-item { min-height: 84px; }
            #dateRailPrevious, #dateRailCurrent, #dateRailNext { min-height: 84px; }
            .date-rail-item strong { font-size: 1.8rem; }
            .controls-bar { grid-template-columns: minmax(0, 1fr); gap: 12px; }
            .tool-index { grid-template-columns: minmax(0, 1fr); gap: 12px; }
            .compat-grid, .decision-grid, .widget-grid, .share-card { grid-template-columns: minmax(0, 1fr); }
        }

        @media (max-width: 560px) {
            .shell { width: calc(100% - 24px); margin: 10px auto 40px; gap: 14px; }
            .site-header { min-height: auto; }
            .wordmark { font-size: 1.65rem; }
            .primary-nav { gap: 20px; }
            .compat-strip, .compat-heatmap-grid { scroll-snap-type: x proximity; }
            .compat-day, .compat-heatmap-cell { scroll-snap-align: start; }
            .hero-main { padding: 20px 16px; }
            .reading-heading { gap: 8px; padding-bottom: 16px; }
            .reading-heading h1 { max-width: 8ch; font-size: clamp(2.8rem, 11vw, 3.5rem); }
            .calendar-day-stamp { max-width: 94px; font-size: 0.66rem; white-space: normal; }
            .model-values-heading { display: block; margin-top: 16px; }
            .model-values-heading h2 { font-size: 0.95rem; }
            .model-values-heading p { margin-top: 3px; text-align: left; font-size: 0.78rem; }
            .desktop-break { display: none; }
            .hero-stats-inline { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 8px; }
            .stat { padding: 8px 6px 6px; }
            .stat + .stat { border-left: 1px solid var(--stroke) !important; }
            .stat small { gap: 5px; font-size: 0.62rem; letter-spacing: 0.045em; }
            .stat small::before { width: 8px; height: 8px; flex-basis: 8px; }
            .stat strong { margin-top: 8px; font-size: clamp(1.55rem, 7.4vw, 2.25rem); letter-spacing: -0.055em; }
            .stat span { font-size: 0.63rem; }
            .date-rail-item { min-height: 78px; padding: 6px 3px; }
            #dateRailPrevious, #dateRailCurrent, #dateRailNext { min-height: 78px; }
            .date-rail-item span, .date-rail-item small { font-size: 0.65rem; }
            .controls-bar { padding: 12px; }
            .date-control-form { align-items: end; gap: 7px; }
            .date-control-form label { font-size: 0.78rem; }
            .date-control-form .bar-submit { padding-inline: 9px; font-size: 0.78rem; }
            .panel, .forecast-card, .events-card, .extreme-card, .compat-card, .decision-card, .ritual-card, #context, #monthCalendar, #diary, #chrono, #prefs, .story-card, .share-card, .widget-card, .api-card { padding: 15px 13px; }
            .panel-head { align-items: flex-start; }
            .panel-head h2, .events-head h2, .extreme-head h2, .compat-head h2, .decision-head h2, .ritual-head h2 { font-size: 1.15rem; }
            .panel-head p { font-size: 0.8rem; }
            .panel-head > .button-row { width: 100%; justify-content: flex-start; }
            .zoom-btns { gap: 3px; }
            .zoom-btn { min-height: 34px; padding: 5px 7px; font-size: 0.7rem; }
            .button-row > button { min-height: 36px; padding: 6px 8px; font-size: 0.76rem; }
            .chart { max-height: none; }
            .legend { gap: 10px; font-size: 0.76rem; }
            .chart .axis-label, .chart .marker-label { font-size: 32px; }
            .reflection-note h2 { font-size: 0.82rem; letter-spacing: 0.12em; line-height: 1.4; }
            .tool-links { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
            .tool-links a { min-height: 40px; justify-content: center; padding: 6px; text-align: center; }
            .welcome-sheet { margin-top: 28px; padding: 24px 18px; }
            .welcome-form-row { align-items: stretch; }
            .welcome-form-row input { min-width: 0; }
            .event-row { grid-template-columns: minmax(60px, 0.35fr) minmax(0, 1fr); }
            .month-grid { border-spacing: 2px; }
            .month-cell-link { min-height: 48px; padding: 5px 2px; }
            .month-average { font-size: 0.65rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }

        body.embed-view .widget-card { background: transparent !important; border: 0 !important; box-shadow: none !important; }
        body.embed-view .widget-panel { background: transparent !important; border: 0 !important; }
    </style>
    <link rel="stylesheet" href="assets/workspaces.css">
<?php if (!$showWelcome || $embedMode): ?>
    <script src="assets/workspaces.js" defer></script>
<?php endif; ?>
</head>
<body<?= $embedMode ? ' class="embed-view" data-theme="' . htmlspecialchars($widgetTheme, ENT_QUOTES) . '"' : ($showWelcome ? ' class="first-visit"' : '') ?>>
    <main class="shell">
        <?php require __DIR__ . '/views/header.php'; ?>
        <?php require __DIR__ . '/views/reading.php'; ?>
<?php if (!$showWelcome || $embedMode): ?>
        <section id="explorar" data-workspace-panel="explorar" aria-labelledby="explorar-heading">
            <h1 id="explorar-heading" class="workspace-heading">Explorar</h1>
            <?php require __DIR__ . '/views/explore.php'; ?>
        </section>
        <section id="diario" data-workspace-panel="diario" aria-labelledby="diario-heading">
            <h1 id="diario-heading" class="workspace-heading">Diario</h1>
            <?php require __DIR__ . '/views/diary.php'; ?>
        </section>
        <section id="herramientas" data-workspace-panel="herramientas" aria-labelledby="herramientas-heading">
            <h1 id="herramientas-heading" class="workspace-heading">Herramientas</h1>
            <?php require __DIR__ . '/views/tools.php'; ?>
        </section>
<?php endif; ?>
    </main>

<?php if (!$showWelcome || $embedMode): ?>
    <script>
        const palette = {
            physical: getComputedStyle(document.body).getPropertyValue('--physical').trim(),
            emotional: getComputedStyle(document.body).getPropertyValue('--emotional').trim(),
            intellectual: getComputedStyle(document.body).getPropertyValue('--intellectual').trim(),
        };

        const data = <?= json_encode($seriesData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const birthInput = <?= json_encode($birthInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const focusInput = <?= json_encode($focusInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const partnerBirthInputInitial = <?= json_encode($partnerBirthInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const birthIsExampleInitial = <?= json_encode($showWelcome) ?>;
        const birthIsValidInitial = <?= json_encode($birthIsValidInitial) ?>;
        const compatPresetInputInitial = <?= json_encode($compatPresetInput, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const svgNS = 'http://www.w3.org/2000/svg';
        const width = 1100;
        const height = 315;
        const padding = { top: 12, right: 54, bottom: 42, left: 60 };
        const innerWidth = width - padding.left - padding.right;
        const innerHeight = height - padding.top - padding.bottom;
        const centerY = padding.top + innerHeight / 2;

        const gridGroup = document.getElementById('grid');
        const pathsGroup = document.getElementById('paths');
        const markersGroup = document.getElementById('markers');
        const slider = document.getElementById('windowSlider');
        const focusDateInput = document.getElementById('focusDateInput');
        const focusDateForm = document.getElementById('focusDateForm');
        const profileBirthForm = document.getElementById('profileBirthForm');
        const profileBirthInput = document.getElementById('profileBirthInput');
        const profileFocusInput = document.getElementById('profileFocusInput');
        const previousReadingDay = document.getElementById('previousReadingDay');
        const nextReadingDay = document.getElementById('nextReadingDay');
        const todayReadingDay = document.getElementById('todayReadingDay');
        const readingDateHeading = document.getElementById('readingDateHeading');
        const readingWeekday = document.getElementById('readingWeekday');
        const calendarDayStamp = document.getElementById('calendarDayStamp');
        const dateRailItems = document.querySelectorAll('.date-rail-item');
        const exportPngBtn = document.getElementById('exportPngBtn');
        const jumpToTodayBtn = document.getElementById('jumpToTodayBtn');
        const copySummaryBtn = document.getElementById('copySummaryBtn');
        const copyLinkBtn = document.getElementById('copyLinkBtn');
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const zoomButtons = document.querySelectorAll('.zoom-btn');

        let visibleWindow = data.window;
        let visibleStartIndex = 0;
        const widgetSnippet = document.getElementById('widgetSnippet');
        const copyWidgetBtn = document.getElementById('copyWidgetBtn');
        const openWidgetBtn = document.getElementById('openWidgetBtn');
        const widgetFrame = document.getElementById('widgetFrame');
        const widgetDetails = document.getElementById('widgetDetails');
        const apiStatus = document.getElementById('apiStatus');
        const apiSnippet = document.getElementById('apiSnippet');
        const copyApiBtn = document.getElementById('copyApiBtn');
        const openApiBtn = document.getElementById('openApiBtn');
        const apiPreview = document.getElementById('apiPreview');
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
        const widgetChartData = document.getElementById('widgetChartData');

        let selectedIndex = data.selectedIndex;
        const isEmbedView = document.body.classList.contains('embed-view');
        let theme = isEmbedView
            ? (document.body.dataset.theme || 'dark')
            : (window.localStorage.getItem('biorrhythms-theme') || 'light');

        function applyTheme(nextTheme) {
            theme = nextTheme === 'light' ? 'light' : 'dark';
            document.body.dataset.theme = theme;
            if (themeToggleBtn) themeToggleBtn.textContent = theme === 'light' ? 'Tema oscuro' : 'Tema claro';
            if (!isEmbedView) window.localStorage.setItem('biorrhythms-theme', theme);
            Object.assign(palette, {
                physical: getComputedStyle(document.body).getPropertyValue('--physical').trim(),
                emotional: getComputedStyle(document.body).getPropertyValue('--emotional').trim(),
                intellectual: getComputedStyle(document.body).getPropertyValue('--intellectual').trim(),
            });
            document.querySelectorAll('.path').forEach((path) => {
                path.setAttribute('stroke', palette[path.dataset.series]);
            });
            if (widgetChartPaths?.childElementCount) updateWidgetChart();
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

            const valueLabels = [[1, '+100%'], [0.5, '+50%'], [0, '0%'], [-0.5, '−50%'], [-1, '−100%']];
            valueLabels.forEach(([value, label]) => {
                const y = yFor(value);
                gridGroup.appendChild(make('line', {
                    x1: padding.left,
                    x2: width - padding.right,
                    y1: y,
                    y2: y,
                    class: value === 0 ? 'zero-line' : 'grid-line'
                }));
                const valueLabel = make('text', {
                    x: 4,
                    y: y + 4,
                    class: 'axis-label',
                    'text-anchor': 'start',
                });
                valueLabel.textContent = label;
                gridGroup.appendChild(valueLabel);
            });

            const targetTickIntervals = window.matchMedia('(max-width: 700px)').matches ? 4 : 7;
            const tickStep = Math.max(1, Math.ceil((visibleWindow.length - 1) / targetTickIntervals));
            const tickIndices = [];
            for (let index = 0; index < visibleWindow.length; index += tickStep) tickIndices.push(index);
            if (tickIndices[tickIndices.length - 1] !== visibleWindow.length - 1) tickIndices.push(visibleWindow.length - 1);
            tickIndices.forEach((index) => {
                const x = xFor(index);
                gridGroup.appendChild(make('line', {
                    x1: x, x2: x, y1: padding.top, y2: height - padding.bottom,
                    class: 'grid-line',
                }));
                const dateLabel = make('text', {
                    x,
                    y: height - 8,
                    class: 'axis-label',
                    'text-anchor': index === 0 ? 'start' : index === visibleWindow.length - 1 ? 'end' : 'middle',
                });
                dateLabel.textContent = visibleWindow[index]?.label?.replace(/^[^ ]+ /, '') ?? '';
                gridGroup.appendChild(dateLabel);
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
                label.textContent = visibleWindow[markerVi]?.label ?? '';
                markersGroup.appendChild(label);
            }

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

            if (reducedMotion) return;
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
            if (widgetChartData) {
                const rows = document.createDocumentFragment();
                points.forEach((point) => {
                    const row = document.createElement('tr');
                    const day = document.createElement('th');
                    day.scope = 'row';
                    day.textContent = `${point.label} · ${point.date}`;
                    row.appendChild(day);
                    ['physical', 'emotional', 'intellectual'].forEach((rhythm) => {
                        const value = document.createElement('td');
                        value.textContent = formatSignedPercent(point[rhythm]);
                        row.appendChild(value);
                    });
                    rows.appendChild(row);
                });
                widgetChartData.replaceChildren(rows);
            }

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

        function updateDateRail(centerDate) {
            const center = new Date(`${centerDate}T12:00:00`);
            const weekdayShort = new Intl.DateTimeFormat('es-ES', { weekday: 'short' });
            const monthYear = new Intl.DateTimeFormat('es-ES', { month: 'short', year: 'numeric' });

            [-1, 0, 1].forEach((offset, index) => {
                const date = new Date(center);
                date.setDate(date.getDate() + offset);
                const isoDate = [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
                const entry = dateRailItems[index];
                const fullLabel = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(date);
                const url = new URL(window.location.href);
                url.searchParams.set('focus', isoDate);
                url.hash = 'lectura';
                if (birthIsExampleInitial) url.searchParams.delete('birth');
                else if (birthIsValidInitial) url.searchParams.set('birth', birthInput);

                entry.children[0].textContent = String(date.getDate());
                entry.children[1].textContent = monthYear.format(date).replace(/\./g, '');
                entry.children[2].textContent = weekdayShort.format(date).replace(/\./g, '');
                entry.href = url.toString();
                entry.setAttribute('aria-label', `${offset === 0 ? 'Día seleccionado' : offset < 0 ? 'Día anterior' : 'Día siguiente'}: ${fullLabel}`);
                entry.classList.toggle('is-selected', offset === 0);
                if (offset === 0) entry.setAttribute('aria-current', 'date');
                else entry.removeAttribute('aria-current');
            });
        }

        function updateSelected(index) {
            selectedIndex = index;
            const point = data.window[index];
            const selectedDate = new Date(`${point.date}T12:00:00`);
            const dateLabel = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
                .format(selectedDate)
                .replace(/\./g, '');
            readingDateHeading.textContent = dateLabel;
            readingWeekday.textContent = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(selectedDate);
            const year = selectedDate.getFullYear();
            const dayOfYear = Math.floor((Date.UTC(year, selectedDate.getMonth(), selectedDate.getDate()) - Date.UTC(year, 0, 0)) / 86400000);
            calendarDayStamp.textContent = `Día ${dayOfYear} de ${new Date(Date.UTC(year, 1, 29)).getUTCDate() === 29 ? 366 : 365}`;
            focusDateInput.value = point.date;
            updateDateRail(point.date);
            document.getElementById('stat-physical').textContent = formatSignedPercent(point.physical);
            document.getElementById('stat-emotional').textContent = formatSignedPercent(point.emotional);
            document.getElementById('stat-intellectual').textContent = formatSignedPercent(point.intellectual);
            document.getElementById('shareTitle').textContent = 'Lectura del día';
            document.getElementById('shareSubtitle').textContent = `${point.label} · fecha seleccionada ${point.date}`;
            if (profileFocusInput) profileFocusInput.value = point.date;
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

            const offsetText = point.offset === 0
                ? 'día seleccionado'
                : `${Math.abs(point.offset)} ${Math.abs(point.offset) === 1 ? 'día' : 'días'} ${point.offset < 0 ? 'antes' : 'después'} del foco`;
            document.getElementById('selectedLabel').textContent = `${point.label} · ${offsetText}`;
            updateForecast(index);
        }

        function selectedFocusDate() {
            return data.window[selectedIndex]?.date || focusInput;
        }

        function syncReadingUrl(focusDate = selectedFocusDate()) {
            const url = new URL(window.location.href);
            if (birthIsExampleInitial) url.searchParams.delete('birth');
            else if (birthIsValidInitial) url.searchParams.set('birth', birthInput);
            url.searchParams.set('focus', focusDate);
            url.searchParams.set('partner_birth', partnerBirthInput.value || partnerBirthInputInitial);

            if (presetInput.value && presetInput.value !== 'custom') {
                url.searchParams.set('preset', presetInput.value);
            } else {
                url.searchParams.delete('preset');
            }

            window.history.replaceState(window.history.state, '', url);
            if (icsExportLink) {
                const calendarUrl = new URL(icsExportLink.href, window.location.href);
                calendarUrl.searchParams.set('from', focusDate);
                calendarUrl.searchParams.set('days', '90');
                if (birthIsValidInitial) calendarUrl.searchParams.set('birth', birthInput);
                icsExportLink.href = `${calendarUrl.pathname}${calendarUrl.search}`;
            }
            return url;
        }

        function selectReadingDate(index, { refreshPreviews = false } = {}) {
            const nextIndex = clamp(Number(index), 0, data.window.length - 1);
            if (nextIndex < visibleStartIndex || nextIndex >= visibleStartIndex + visibleWindow.length) {
                setZoom(visibleWindow.length, nextIndex);
            }
            slider.value = String(nextIndex);
            updateSelected(nextIndex);
            syncReadingUrl();
            buildSpecialEvents(exploreHorizonDays);
            buildExtremeDays();
            if (refreshPreviews) refreshIntegrationPreviews();
        }

        function refreshIntegrationPreviews() {
            updateWidgetEmbedSnippet();
            updateApiPreview();
        }

        function navigateToReadingDate(date) {
            const index = data.window.findIndex((point) => point.date === date);
            if (index >= 0) {
                selectReadingDate(index, { refreshPreviews: true });
                return;
            }
            const url = syncReadingUrl(date);
            url.hash = 'lectura';
            window.history.replaceState(window.history.state, '', url);
            window.location.reload();
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
            const baseDate = parseIsoDate(selectedFocusDate());
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

        function syncCompatibilityUrl() {
            return syncReadingUrl();
        }

        function activeCompatibilityPreset() {
            const active = Array.from(compatPresetButtons).find((button) => button.classList.contains('is-active'));
            return active?.dataset.compatPreset ?? presetInput.value ?? 'custom';
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
            url.searchParams.set('focus', selectedFocusDate());
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

            if (!document.body.classList.contains('embed-view') && widgetDetails?.open) {
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
                <span>${Math.round(result.score * 100)}% de solapamiento</span>
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
                <span>${Math.round(result.score * 100)}% de solapamiento</span>
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
        const exploreHorizonButtons = document.querySelectorAll('[data-explore-horizon="7"], [data-explore-horizon="30"], [data-explore-horizon="90"]');
        const cycleExtremeReveal = document.getElementById('cycleExtremeReveal');
        const cycleExtremeMore = document.getElementById('cycleExtremeMore');
        const cycleExtremeLess = document.getElementById('cycleExtremeLess');
        const extremeGrid = document.getElementById('extremeGrid');
        const extremePill = document.getElementById('extremePill');
        const icsExportLink = document.getElementById('icsExportLink');
        let exploreHorizonDays = 30;
        let cycleExtremeExpanded = false;
        let cycleExtremeVisibleCount = 0;

        function addIsoDays(dateString, days) {
            const date = new Date(`${dateString}T00:00:00Z`);
            date.setUTCDate(date.getUTCDate() + days);
            return date.toISOString().slice(0, 10);
        }

        function buildSpecialEvents(horizonDays = exploreHorizonDays) {
            const SERIES = [
                { key: 'physical',     label: 'Físico',      color: 'var(--physical)'     },
                { key: 'emotional',    label: 'Emocional',   color: 'var(--emotional)'    },
                { key: 'intellectual', label: 'Intelectual', color: 'var(--intellectual)' },
            ];
            const w = data.exploreWindow;
            const selectedDate = selectedFocusDate();
            const lastDate = addIsoDays(selectedDate, horizonDays);
            const startIndex = w.findIndex((point) => point.date >= selectedDate);
            const eventsByDate = new Map();
            let totalEvents = 0;

            eventsList.innerHTML = '';

            SERIES.forEach(({ key, label, color }) => {
                for (let i = Math.max(1, startIndex); i < w.length - 1; i++) {
                    if (w[i].date > lastDate) break;
                    const prev = w[i - 1][key];
                    const cur  = w[i][key];
                    const next = w[i + 1][key];
                    let kind = '';
                    let value = '';

                    if (cur > prev && cur > next && cur > 0.85) {
                        kind = 'Pico';
                        value = `+${Math.round(cur * 100)}%`;
                    } else if (cur < prev && cur < next && cur < -0.85) {
                        kind = 'Valle';
                        value = `${Math.round(cur * 100)}%`;
                    } else if (prev * cur < 0) {
                        kind = cur > 0 ? 'Cruce de cero hacia positivo' : 'Cruce de cero hacia negativo';
                    }

                    if (!kind) continue;
                    if (!eventsByDate.has(w[i].date)) {
                        eventsByDate.set(w[i].date, { label: w[i].label, events: [] });
                    }
                    eventsByDate.get(w[i].date).events.push({ label, color, kind, value });
                    totalEvents++;
                }
            });

            eventsPill.textContent = `${totalEvents} ${totalEvents === 1 ? 'evento' : 'eventos'}`;
            if (totalEvents === 0) {
                eventsList.innerHTML = `<p class="event-empty">No hay eventos calculados en los próximos ${horizonDays} días.</p>`;
                return;
            }

            [...eventsByDate.entries()].sort(([left], [right]) => left.localeCompare(right)).forEach(([, { label: dateLabel, events }]) => {
                const group = document.createElement('section');
                group.className = 'event-date-group';
                group.innerHTML = `<h3>${dateLabel}</h3><ul>${events.map(({ label, color, kind, value }) => `
                    <li><span class="event-rhythm-label" style="color:${color}">${label}</span><span>${kind}</span>${value ? `<strong>${value}</strong>` : ''}</li>
                `).join('')}</ul>`;
                eventsList.appendChild(group);
            });
        }

        function buildExtremeDays() {
            const selectedDate = selectedFocusDate();
            const allFutureDays = (data.cycleExtremeDays ?? []).filter((day) => day.date >= selectedDate);
            const previewEnd = addIsoDays(selectedDate, 30);
            const previewDays = allFutureDays.filter((day) => day.date <= previewEnd);
            const visibleDays = cycleExtremeExpanded
                ? allFutureDays.slice(0, cycleExtremeVisibleCount)
                : previewDays.slice(0, 5);

            extremePill.textContent = cycleExtremeExpanded
                ? `${visibleDays.length} de ${allFutureDays.length} del ciclo`
                : `${Math.min(previewDays.length, 5)} de ${previewDays.length} próximos 30 días`;
            extremeGrid.innerHTML = '';

            if (visibleDays.length === 0) {
                const emptyCopy = cycleExtremeExpanded
                    ? 'No quedan días extremos en el ciclo desde esta fecha.'
                    : 'No hay días extremos en los próximos 30 días.';
                extremeGrid.innerHTML = `<p class="event-empty">${emptyCopy}</p>`;
            }

            visibleDays.forEach(({ label, avg, physical, emotional, intellectual }) => {
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
                extremeGrid.appendChild(el);
            });

            cycleExtremeReveal.hidden = cycleExtremeExpanded || allFutureDays.length === 0;
            cycleExtremeReveal.setAttribute('aria-pressed', String(cycleExtremeExpanded));
            cycleExtremeMore.hidden = !cycleExtremeExpanded || cycleExtremeVisibleCount >= allFutureDays.length;
            cycleExtremeLess.hidden = !cycleExtremeExpanded;
        }

        function updateForecast(index) {
            updateCompatibility(index);
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
                ? 'Los valores de los tres ciclos coinciden ampliamente en esta fecha; la comparación no evalúa relaciones personales.'
                : selected.score >= 0.62
                    ? 'Algunos valores coinciden y otros difieren en esta fecha; es solo una comparación del modelo.'
                    : 'Los valores difieren más en esta fecha; el resultado no describe compatibilidad interpersonal.';
            compatSummaryPill.textContent = `${Math.round(best.score * 100)}% mayor solapamiento`;

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

            compatFooter.textContent = `${best.label} presenta el mayor solapamiento de esta ventana. ${worst.label} presenta el menor (${Math.round(worst.score * 100)}%).`;
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

            const isDark = theme !== 'light';
            const fg    = isDark ? '#ecf2ff' : '#18202b';
            const muted = isDark ? '#9bb0d0' : '#516174';
            const bg0   = isDark ? '#07111f'  : '#f7f2eb';
            const bg1   = isDark ? '#10213b'  : '#e7eef9';
            const panelFill   = isDark ? 'rgba(8,14,27,0.76)'   : 'rgba(255,255,255,0.72)';
            const narrativeRaw = compatNarrative.textContent.trim();
            const narrative = narrativeRaw.length > 72 ? narrativeRaw.slice(0, 70) + '…' : narrativeRaw;

            // Heatmap layout: 7 cards, 76px step, 62px wide, starts at x=618
            const HX = 618, STEP = 76, CW = 62, CH = 130;
            const heatmapCards = points.map((point, index) => {
                const score = Math.round(point.score * 100);
                const cx = HX + index * STEP + CW / 2;
                const cy = 370;
                const color = score >= 80 ? '#6ee7b7' : score >= 62 ? '#f8c95d' : '#ff7b54';
                const textFill = isDark ? '#ecf2ff' : '#18202b';
                const [wday, day, mon] = point.label.split(' ');
                return `<rect x="${HX + index * STEP}" y="${cy}" width="${CW}" height="${CH}" rx="14" fill="${color}" fill-opacity="0.15" stroke="${color}" stroke-opacity="0.55" stroke-width="1.5"/>
  <text x="${cx}" y="${cy + 22}" text-anchor="middle" style="font:700 13px Arial,sans-serif;fill:${muted}">${escapeXml(wday ?? '')}</text>
  <text x="${cx}" y="${cy + 50}" text-anchor="middle" style="font:700 22px Georgia,serif;fill:${textFill}">${escapeXml(day ?? '')}</text>
  <text x="${cx}" y="${cy + 70}" text-anchor="middle" style="font:400 13px Arial,sans-serif;fill:${muted}">${escapeXml(mon ?? '')}</text>
  <text x="${cx}" y="${cy + 106}" text-anchor="middle" style="font:700 20px Georgia,serif;fill:${color}">${score}%</text>`;
            }).join('\n');

            return `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${bg0}"/>
      <stop offset="100%" stop-color="${bg1}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" rx="40" fill="url(#bg)"/>
  <rect x="44" y="44" width="1112" height="542" rx="32" fill="${panelFill}" stroke="rgba(255,255,255,0.12)"/>

  <!-- Header -->
  <text x="84" y="108" style="font:700 14px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">BIORHYTHMS / COMPATIBILITY</text>
  <text x="84" y="175" style="font:700 54px Georgia,serif;fill:${fg}">Comparación de ciclos</text>
  <text x="84" y="213" style="font:400 22px Georgia,serif;fill:${muted}">${escapeXml(birthInput)} · ${escapeXml(partnerBirth)}</text>
  <text x="84" y="247" style="font:400 20px Georgia,serif;fill:${muted}">Fecha foco: ${escapeXml(selectedFocusDate())}</text>

  <!-- Divider -->
  <line x1="84" y1="272" x2="580" y2="272" stroke="rgba(255,255,255,0.1)"/>

  <!-- Score -->
  <text x="84" y="308" style="font:700 13px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">SCORE ACTUAL</text>
  <text x="84" y="378" style="font:700 70px Georgia,serif;fill:${fg}">${Math.round(selected.score * 100)}%</text>
  <text x="84" y="420" style="font:400 17px Georgia,serif;fill:${muted}">${escapeXml(narrative)}</text>

  <!-- Best / Worst / Preset -->
  <text x="84"  y="468" style="font:700 13px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">MEJOR VENTANA</text>
  <text x="84"  y="500" style="font:400 20px Georgia,serif;fill:${fg}">${escapeXml(`${best.label} · ${Math.round(best.score * 100)}%`)}</text>
  <text x="310" y="468" style="font:700 13px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">PEOR VENTANA</text>
  <text x="310" y="500" style="font:400 20px Georgia,serif;fill:${fg}">${escapeXml(`${worst.label} · ${Math.round(worst.score * 100)}%`)}</text>
  <text x="84"  y="545" style="font:700 13px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">PRESET</text>
  <text x="84"  y="572" style="font:400 18px Georgia,serif;fill:${fg}">${escapeXml(presetLabel(preset))}</text>

  <!-- Vertical separator -->
  <line x1="598" y1="100" x2="598" y2="530" stroke="rgba(255,255,255,0.1)"/>

  <!-- Heatmap -->
  <text x="${HX}" y="348" style="font:700 13px Arial,sans-serif;fill:${muted};letter-spacing:2px;text-transform:uppercase">PRÓXIMOS 7 DÍAS</text>
  ${heatmapCards}

  <!-- URL footer -->
  <line x1="44" y1="556" x2="1156" y2="556" stroke="rgba(255,255,255,0.08)"/>
  <text x="84" y="585" style="font:400 15px Arial,sans-serif;fill:${muted}">${escapeXml(urlLabel)}</text>
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

        function buildShareSvg(point) {
            const width = 1200;
            const height = 630;
            const headline = point.physical >= point.emotional && point.physical >= point.intellectual
                ? 'Lectura del día'
                : point.emotional >= point.physical && point.emotional >= point.intellectual
                    ? 'Lectura del día'
                    : 'Lectura del día';

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
  <text x="84" y="244" class="sub">${point.label} · fecha seleccionada ${selectedFocusDate()}</text>
  <text x="84" y="300" class="small">Físico</text>
  <text x="84" y="346" class="value">${(point.physical * 100).toFixed(1)}%</text>
  <text x="404" y="300" class="small">Emocional</text>
  <text x="404" y="346" class="value">${(point.emotional * 100).toFixed(1)}%</text>
  <text x="724" y="300" class="small">Intelectual</text>
  <text x="724" y="346" class="value">${(point.intellectual * 100).toFixed(1)}%</text>
  <line x1="84" y1="416" x2="1116" y2="416" stroke="rgba(255,255,255,0.16)"/>
  <text x="84" y="470" class="small">Ritmos en el tiempo · vista local · exportable a PNG</text>
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
            ctx.fillText(theme === 'light' ? 'TEMA CLARO' : 'TEMA OSCURO', 908, 109);

            const mainHeadline = point.physical >= point.emotional && point.physical >= point.intellectual
                ? 'Lectura del día'
                : point.emotional >= point.physical && point.emotional >= point.intellectual
                    ? 'Lectura del día'
                    : 'Lectura del día';

            ctx.fillStyle = '#ecf2ff';
            ctx.font = '700 60px Georgia, serif';
            ctx.fillText('Biorrhythms', 84, 186);
            ctx.fillStyle = '#9bb0d0';
            ctx.font = '400 24px Georgia, serif';
            ctx.fillText(`${mainHeadline} · ${point.label}`, 84, 230);
            ctx.fillText(`Fecha seleccionada ${selectedFocusDate()}`, 84, 264);

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
            ctx.fillText('Ritmos en el tiempo · vista local · exportable a PNG', 84, 512);
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

        function setZoom(days, centerIndex = selectedIndex) {
            const total = data.window.length;
            const clampedDays = Math.min(days, total);
            const half = Math.floor(clampedDays / 2);
            const center = centerIndex;
            visibleStartIndex = Math.max(0, Math.min(center - half, total - clampedDays));
            const end = Math.min(visibleStartIndex + clampedDays, total);
            visibleWindow = data.window.slice(visibleStartIndex, end);

            zoomButtons.forEach((btn) => {
                btn.classList.toggle('is-active', Number(btn.dataset.zoom) === days);
            });
            const zoomLabel = [...zoomButtons].find((btn) => Number(btn.dataset.zoom) === days)?.textContent.trim() || `${days} días`;
            document.getElementById('chartTitle').textContent = `Ciclo de ${zoomLabel}`;

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

        let chartResizeFrame = 0;
        window.addEventListener('resize', () => {
            cancelAnimationFrame(chartResizeFrame);
            chartResizeFrame = requestAnimationFrame(buildGrid);
        }, { passive: true });

        exploreHorizonButtons.forEach((button) => {
            button.addEventListener('click', () => {
                exploreHorizonDays = Number(button.dataset.exploreHorizon);
                exploreHorizonButtons.forEach((option) => {
                    option.setAttribute('aria-pressed', String(option === button));
                });
                buildSpecialEvents(exploreHorizonDays);
            });
        });

        cycleExtremeReveal.addEventListener('click', () => {
            const allFutureDays = (data.cycleExtremeDays ?? []).filter((day) => day.date >= selectedFocusDate());
            cycleExtremeExpanded = true;
            cycleExtremeVisibleCount = Math.min(10, allFutureDays.length);
            buildExtremeDays();
        });
        cycleExtremeMore.addEventListener('click', () => {
            cycleExtremeVisibleCount += 10;
            buildExtremeDays();
        });
        cycleExtremeLess.addEventListener('click', () => {
            cycleExtremeExpanded = false;
            cycleExtremeVisibleCount = 0;
            buildExtremeDays();
        });

        setZoom(91);
        buildSpecialEvents();
        buildExtremeDays();
        updateWidgetChart();
        applyTheme(theme);
        updateSelected(Number(slider.value));
        animateEntrance();
        syncCompatibilityUrl();
        document.addEventListener('DOMContentLoaded', () => {
            if (!isEmbedView && !document.getElementById('herramientas').hidden) refreshIntegrationPreviews();
        });

        let sliderFrame = 0;
        let pendingSliderIndex = Number(slider.value);
        slider.addEventListener('input', (event) => {
            pendingSliderIndex = Number(event.target.value);
            if (sliderFrame) return;
            sliderFrame = window.requestAnimationFrame(() => {
                sliderFrame = 0;
                selectReadingDate(pendingSliderIndex);
            });
        });
        slider.addEventListener('change', () => refreshIntegrationPreviews());

        previousReadingDay.addEventListener('click', () => selectReadingDate(selectedIndex - 1, { refreshPreviews: true }));
        nextReadingDay.addEventListener('click', () => selectReadingDate(selectedIndex + 1, { refreshPreviews: true }));
        todayReadingDay.addEventListener('click', () => {
            const today = new Date();
            const localToday = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
            navigateToReadingDate(localToday);
        });

        focusDateForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!focusDateInput.reportValidity()) return;
            const url = syncReadingUrl(focusDateInput.value);
            url.hash = 'lectura';
            window.history.replaceState(window.history.state, '', url);
            window.location.reload();
        });

        profileBirthForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!profileBirthInput.reportValidity()) return;
            const url = syncReadingUrl(selectedFocusDate());
            url.searchParams.set('birth', profileBirthInput.value);
            url.hash = 'herramientas';
            window.history.replaceState(window.history.state, '', url);
            window.location.reload();
        });

        document.querySelector('[data-workspace-link="herramientas"]')?.addEventListener('click', refreshIntegrationPreviews);

        widgetDetails?.addEventListener('toggle', () => {
            if (widgetDetails.open) updateWidgetEmbedSnippet();
        });

        jumpToTodayBtn.addEventListener('click', () => {
            selectReadingDate(data.selectedIndex, { refreshPreviews: true });
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
            const shareUrl = new URL(window.location.href);
            shareUrl.hash = 'lectura';
            await navigator.clipboard.writeText(shareUrl.toString());
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

        themeToggleBtn.addEventListener('click', () => {
            applyTheme(theme === 'light' ? 'dark' : 'light');
            updateWidgetEmbedSnippet();
        });

        partnerBirthInput.addEventListener('input', () => {
            updateCompatibility(selectedIndex);
            syncCompatibilityUrl();
            refreshIntegrationPreviews();
        });

        exportCompatBtn.addEventListener('click', async () => {
            await exportCompatibilityCardAsPng();
        });

    </script>
    <script src="assets/diary.js"></script>
    <script src="assets/chrono.js"></script>
    <script src="assets/notify.js"></script>
    <script>
        (function () {
            const D = window.BiorrhythmsDiary;
            const form = document.getElementById('diaryForm');
            if (!D || !form) return;

            const rows = document.getElementById('diaryRows');
            const count = document.getElementById('diaryCount');
            const status = document.getElementById('diaryStatus');
            const labels = { physical: 'Físico ↔ energía', emotional: 'Emocional ↔ ánimo', intellectual: 'Intelectual ↔ claridad' };
            const forgetBtn = document.getElementById('diaryForget');
            let entries = D.load(window.localStorage);
            let forgetArmed = null;

            function say(message) { status.textContent = message; }

            function cell(text) {
                const td = document.createElement('td');
                td.textContent = text;
                return td;
            }

            function fmt(value) { return (value >= 0 ? '+' : '') + value.toFixed(2); }

            function render() {
                const total = Object.keys(entries).length;
                count.textContent = total === 1 ? '1 registro' : total + ' registros';
                rows.replaceChildren();
                D.correlations(entries, birthInput).forEach((row) => {
                    const tr = document.createElement('tr');
                    tr.appendChild(cell(labels[row.rhythm]));
                    tr.appendChild(cell(String(row.n)));
                    if (row.status === 'insufficient') {
                        tr.appendChild(cell('—'));
                        tr.appendChild(cell('—'));
                        tr.appendChild(cell('Faltan ' + row.missing + ' registros'));
                    } else if (row.status === 'flat') {
                        tr.appendChild(cell('—'));
                        tr.appendChild(cell('—'));
                        tr.appendChild(cell('Respuestas constantes: no se puede calcular'));
                    } else {
                        tr.appendChild(cell(fmt(row.r)));
                        tr.appendChild(cell(row.ci ? fmt(row.ci[0]) + ' a ' + fmt(row.ci[1]) : '—'));
                        tr.appendChild(cell(row.label));
                    }
                    rows.appendChild(tr);
                });
            }

            function persist(message) {
                say(D.save(window.localStorage, entries) ? message : message + ' (no se pudo guardar en este navegador)');
                render();
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const date = document.getElementById('diaryDate').value;
                const entry = D.normalizeEntry({
                    energy: Number(document.getElementById('diaryEnergy').value),
                    mood: Number(document.getElementById('diaryMood').value),
                    focus: Number(document.getElementById('diaryFocus').value),
                });
                if (!D.parseIso(date) || !entry) { say('Fecha o valores no válidos.'); return; }
                const existed = Object.prototype.hasOwnProperty.call(entries, date);
                entries[date] = entry;
                persist(existed ? 'Registro de ' + date + ' actualizado.' : 'Registro de ' + date + ' guardado.');
            });

            document.getElementById('diaryExport').addEventListener('click', () => {
                const blob = new Blob([D.serialize(entries)], { type: 'application/json' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'biorritmos-diario.json';
                link.click();
                URL.revokeObjectURL(link.href);
                say('Diario exportado.');
            });

            document.getElementById('diaryImport').addEventListener('change', async (event) => {
                const file = event.target.files[0];
                event.target.value = '';
                if (!file) return;
                const result = D.parseImport(await file.text());
                if (result.error) { say('No se importó nada: ' + result.error + '.'); return; }
                Object.assign(entries, result.entries);
                persist('Importados ' + result.accepted + ' registros' + (result.rejected ? ' (' + result.rejected + ' descartados por no ser válidos)' : '') + '.');
            });

            forgetBtn.addEventListener('click', () => {
                if (forgetArmed === null) {
                    forgetBtn.textContent = 'Confirmar: borrar todo';
                    forgetArmed = window.setTimeout(() => { forgetArmed = null; forgetBtn.textContent = 'Olvidar mis datos'; }, 5000);
                    return;
                }
                window.clearTimeout(forgetArmed);
                forgetArmed = null;
                forgetBtn.textContent = 'Olvidar mis datos';
                entries = {};
                D.clear(window.localStorage);
                say('Diario borrado de este navegador.');
                render();
            });

            render();
        })();
    </script>
    <script>
        (function () {
            var P = window.BiorrhythmsProfile, C = window.BiorrhythmsChrono, N = window.BiorrhythmsNotify;
            if (!P || !C || !N || !document.getElementById('chrono')) return;

            var store = null;
            try { store = window.localStorage; } catch (e) { /* storage blocked */ }
            var criticalAhead = <?= json_encode($criticalAhead, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
            var profile = store ? P.load(store) : P.sanitize(null);

            function el(tag, text, className) {
                var node = document.createElement(tag);
                if (text !== undefined) node.textContent = text;
                if (className) node.className = className;
                return node;
            }

            function persist(patch) {
                if (store) P.save(store, patch);
                profile = store ? P.load(store) : Object.assign(profile, patch);
            }

            // ---- schedule from wake-up time
            var wakeInput = document.getElementById('chronoWake');
            var scheduleBox = document.getElementById('chronoSchedule');
            var pill = document.getElementById('chronoPill');

            function renderSchedule(wake) {
                scheduleBox.replaceChildren();
                var s = C.schedule(wake);
                if (!s) return;
                var table = el('table', undefined, 'diary-table');
                var head = el('tr');
                ['Franja', 'Horario'].forEach(function (t) { head.appendChild(el('th', t)); });
                table.appendChild(head);
                s.windows.forEach(function (w) {
                    var tr = el('tr');
                    tr.appendChild(el('td', w.label));
                    tr.appendChild(el('td', w.start === w.end ? w.start : w.start + ' – ' + w.end));
                    table.appendChild(tr);
                });
                scheduleBox.appendChild(table);
                scheduleBox.appendChild(el('h3', 'Bloques de trabajo de 90 min', undefined)).style.cssText = 'margin:18px 0 8px;font-size:1rem';
                var list = el('ul');
                s.blocks.forEach(function (b) { list.appendChild(el('li', 'Bloque ' + b.index + ': ' + b.start + ' – ' + b.end + ' (pausa de 20 min después)')); });
                scheduleBox.appendChild(list);
            }

            document.getElementById('wakeForm').addEventListener('submit', function (event) {
                event.preventDefault();
                if (!C.schedule(wakeInput.value)) return;
                persist({ wake: wakeInput.value });
                renderSchedule(wakeInput.value);
                updatePill();
                updateForget();
            });

            // ---- questionnaire
            var form = document.getElementById('chronoForm');
            var status = document.getElementById('chronoStatus');
            var result = document.getElementById('chronoResult');

            C.QUESTIONS.forEach(function (q, qi) {
                var fs = el('fieldset');
                fs.style.cssText = 'border:1px solid var(--stroke);border-radius:12px;margin:0 0 10px;padding:10px 14px';
                fs.appendChild(el('legend', (qi + 1) + '. ' + q.text));
                q.options.forEach(function (o) {
                    var label = el('label');
                    label.style.cssText = 'display:inline-flex;gap:6px;align-items:center;margin:4px 14px 4px 0;font-size:0.9rem';
                    var input = document.createElement('input');
                    input.type = 'radio';
                    input.name = q.id;
                    input.value = String(o[1]);
                    label.appendChild(input);
                    label.appendChild(document.createTextNode(o[0]));
                    fs.appendChild(label);
                });
                form.appendChild(fs);
            });
            var submit = el('button', 'Calcular mi cronotipo', 'bar-submit');
            submit.type = 'submit';
            form.appendChild(submit);

            function renderResult(c) {
                result.replaceChildren();
                if (!c) return;
                var info = C.TYPES[c.key];
                result.appendChild(el('p', 'Tu perfil: ' + info.label + ' (puntuación ' + c.score + ' de ' + C.MAX_SCORE + ')', undefined)).style.fontWeight = '700';
                result.appendChild(el('p', info.advice));
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var answers = {};
                C.QUESTIONS.forEach(function (q) {
                    var chosen = form.querySelector('input[name="' + q.id + '"]:checked');
                    if (chosen) answers[q.id] = Number(chosen.value);
                });
                var total = C.score(answers);
                if (total === null) { status.textContent = 'Responde las 5 preguntas.'; return; }
                var c = C.classify(total);
                persist({ chronotype: { score: total, key: c.key } });
                status.textContent = '';
                renderResult(profile.chronotype);
                updatePill();
                updateForget();
            });

            function updatePill() {
                pill.textContent = profile.chronotype ? C.TYPES[profile.chronotype.key].label : (profile.wake ? 'Horario listo' : 'Sin datos');
            }

            // ---- notifications + forget
            var notifyBtn = document.getElementById('notifyToggle');
            var forgetBtn = document.getElementById('forgetProfile');
            var prefsStatus = document.getElementById('prefsStatus');
            var supported = 'Notification' in window;

            function updateNotifyBtn() {
                if (!supported) { notifyBtn.disabled = true; notifyBtn.textContent = 'Avisos no disponibles en este navegador'; return; }
                if (Notification.permission === 'denied') { notifyBtn.disabled = true; notifyBtn.textContent = 'Avisos bloqueados en el navegador'; return; }
                notifyBtn.textContent = profile.notify && Notification.permission === 'granted' ? 'Desactivar avisos' : 'Activar avisos de días críticos';
            }

            function updateForget() {
                forgetBtn.hidden = !(profile.birth || profile.chronotype || profile.wake || profile.notify);
            }

            function show(n) {
                var opts = { body: n.body, icon: 'assets/icons/icon-192.png', tag: 'critical-' + n.date };
                var direct = function () { try { new Notification(n.title, opts); } catch (e) { /* unsupported */ } };
                if (!('serviceWorker' in navigator)) { direct(); return; }
                navigator.serviceWorker.getRegistration().then(function (r) { return r ? r.showNotification(n.title, opts) : direct(); }).catch(direct);
            }

            function maybeNotify() {
                if (!supported || !profile.notify || Notification.permission !== 'granted') return;
                var today = P.localDay();
                if (focusInput !== today) return; // the critical list is for the focus date
                var notice = N.pendingNotice(criticalAhead, today, profile.notified);
                if (!notice) return;
                show(notice);
                persist({ notified: profile.notified.concat(notice.date) });
            }

            notifyBtn.addEventListener('click', function () {
                if (profile.notify && supported && Notification.permission === 'granted') {
                    persist({ notify: false });
                    prefsStatus.textContent = 'Avisos desactivados.';
                    updateNotifyBtn(); updateForget();
                    return;
                }
                Notification.requestPermission().then(function (permission) {
                    if (permission === 'granted') {
                        persist({ notify: true });
                        prefsStatus.textContent = 'Avisos activados. Se mostrarán al abrir la app en un día crítico o la víspera.';
                        maybeNotify();
                    } else {
                        prefsStatus.textContent = 'No se concedió el permiso de notificaciones.';
                    }
                    updateNotifyBtn(); updateForget();
                });
            });

            var forgetTimer = null;
            forgetBtn.addEventListener('click', function () {
                if (forgetTimer === null) {
                    forgetBtn.textContent = 'Confirmar: borrar fecha y preferencias';
                    forgetTimer = window.setTimeout(function () { forgetTimer = null; forgetBtn.textContent = 'Olvidar mi fecha y preferencias'; }, 5000);
                    return;
                }
                window.clearTimeout(forgetTimer);
                if (store) P.clear(store);
                window.location.assign(window.location.pathname);
            });

            // ---- init
            if (profile.wake) { wakeInput.value = profile.wake; renderSchedule(profile.wake); }
            renderResult(profile.chronotype);
            updatePill(); updateNotifyBtn(); updateForget();
            maybeNotify();

            if ('serviceWorker' in navigator && !document.body.classList.contains('embed-view')) {
                window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () { /* offline support is optional */ }); });
            }
        })();
    </script>
<?php endif; ?>
</body>
</html>
