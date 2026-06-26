<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Biorrhythms.php';
require_once __DIR__ . '/../src/Compatibility.php';
require_once __DIR__ . '/../src/DateFormatter.php';
require_once __DIR__ . '/../src/Forecast.php';
require_once __DIR__ . '/../src/Ritual.php';

use Biorrhythms\Biorrhythms;
use Biorrhythms\Compatibility;
use Biorrhythms\DateFormatter;
use Biorrhythms\Forecast;
use Biorrhythms\Ritual;

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

$birthInput = clampDateInput($_GET['birth'] ?? null, '1990-01-01');
$focusInput = clampDateInput($_GET['focus'] ?? null, (new DateTimeImmutable('today'))->format('Y-m-d'));

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
        'label' => DateFormatter::short($date),
        'offset' => $offset,
        'physical' => $bio->calculatePhysical($daysSinceBirth),
        'emotional' => $bio->calculateEmotional($daysSinceBirth),
        'intellectual' => $bio->calculateIntellectual($daysSinceBirth),
    ];
}

$selectedIndex = $windowRadius;
$selectedPoint = $window[$selectedIndex];
$selectedFocusDays = daysBetween($birthDate, $focusDate);
$focusValues = [
    'physical' => $bio->calculatePhysical($selectedFocusDays),
    'emotional' => $bio->calculateEmotional($selectedFocusDays),
    'intellectual' => $bio->calculateIntellectual($selectedFocusDays),
];
$partnerFocusValues = [
    'physical' => $bio->calculatePhysical(daysBetween($partnerBirthDate, $focusDate)),
    'emotional' => $bio->calculateEmotional(daysBetween($partnerBirthDate, $focusDate)),
    'intellectual' => $bio->calculateIntellectual(daysBetween($partnerBirthDate, $focusDate)),
];

$forecast       = array_slice($window, $selectedIndex, 7);
$forecastScored = Forecast::scoreWindow($forecast);
$bestForecast   = Forecast::best($forecastScored);
$worstForecast  = Forecast::worst($forecastScored);

$compatibilityForecast = array_map(static function (array $point) use ($partnerBirthInput, $birthInput): array {
    $bio = new Biorrhythms();
    $date = new DateTimeImmutable($point['date']);
    $daysA = daysBetween(new DateTimeImmutable($birthInput), $date);
    $daysB = daysBetween(new DateTimeImmutable($partnerBirthInput), $date);
    $rhythms = [
        [
            'label' => 'Físico',
            'value' => Compatibility::score($bio->calculatePhysical($daysA), $bio->calculatePhysical($daysB)),
        ],
        [
            'label' => 'Emocional',
            'value' => Compatibility::score($bio->calculateEmotional($daysA), $bio->calculateEmotional($daysB)),
        ],
        [
            'label' => 'Intelectual',
            'value' => Compatibility::score($bio->calculateIntellectual($daysA), $bio->calculateIntellectual($daysB)),
        ],
    ];

    $point['score'] = array_sum(array_column($rhythms, 'value')) / count($rhythms);
    $point['rhythms'] = $rhythms;

    return $point;
}, $forecast);

$bestCompatibility = $compatibilityForecast[0];
$worstCompatibility = $compatibilityForecast[0];
foreach ($compatibilityForecast as $point) {
    if ($point['score'] > $bestCompatibility['score']) {
        $bestCompatibility = $point;
    }

    if ($point['score'] < $worstCompatibility['score']) {
        $worstCompatibility = $point;
    }
}

$profileLabel  = match (true) {
    $selectedPoint['physical']     >= $selectedPoint['emotional'] && $selectedPoint['physical']     >= $selectedPoint['intellectual'] => 'Physical peak',
    $selectedPoint['emotional']    >= $selectedPoint['physical']  && $selectedPoint['emotional']    >= $selectedPoint['intellectual'] => 'Emotional peak',
    $selectedPoint['intellectual'] >= $selectedPoint['physical']  && $selectedPoint['intellectual'] >= $selectedPoint['emotional']   => 'Mental peak',
    default                        => 'Balanced',
};

$ritual = Ritual::generate(
    average:      Forecast::average($selectedPoint),
    dominantKey:  Forecast::dominantKey($selectedPoint),
    bestLabel:    $bestForecast['label'],
    worstLabel:   $worstForecast['label'],
);

$appLinkParams = [
    'birth' => $birthInput,
    'focus' => $focusInput,
    'partner_birth' => $partnerBirthInput,
];

$widgetLinkParams = $appLinkParams + ['embed' => '1'];

$payload = [
    'meta' => [
        'generated_at' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
        'birth' => $birthInput,
        'focus' => $focusInput,
        'partner_birth' => $partnerBirthInput,
        'window_radius' => $windowRadius,
    ],
    'profile' => [
        'label' => $profileLabel,
        'selected' => $selectedPoint,
        'focus_values' => $focusValues,
    ],
    'timeline' => [
        'window' => $window,
    ],
    'forecast' => [
        'days' => $forecastScored,
        'best_day' => $bestForecast,
        'worst_day' => $worstForecast,
    ],
    'ritual' => $ritual,
    'compatibility' => [
        'score' => Compatibility::pointScore($focusValues, $partnerFocusValues),
        'current' => [
            'physical' => Compatibility::score($focusValues['physical'], $partnerFocusValues['physical']),
            'emotional' => Compatibility::score($focusValues['emotional'], $partnerFocusValues['emotional']),
            'intellectual' => Compatibility::score($focusValues['intellectual'], $partnerFocusValues['intellectual']),
        ],
        'forecast' => $compatibilityForecast,
        'best_day' => $bestCompatibility,
        'worst_day' => $worstCompatibility,
    ],
    'links' => [
        'app'    => '/?' . http_build_query($appLinkParams, '', '&', PHP_QUERY_RFC3986),
        'widget' => '/?' . http_build_query($widgetLinkParams, '', '&', PHP_QUERY_RFC3986),
    ],
];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
if (($_GET['pretty'] ?? '0') === '1') {
    $flags |= JSON_PRETTY_PRINT;
}

echo json_encode($payload, $flags);
