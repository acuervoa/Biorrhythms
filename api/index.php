<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Biorrhythms.php';
require_once __DIR__ . '/../src/Compatibility.php';
require_once __DIR__ . '/../src/DateFormatter.php';

use Biorrhythms\Biorrhythms;
use Biorrhythms\Compatibility;
use Biorrhythms\DateFormatter;

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

$forecast = array_slice($window, $selectedIndex, 7);
$forecastScored = array_map(static function (array $point): array {
    $point['score'] = ($point['physical'] + $point['emotional'] + $point['intellectual']) / 3;

    return $point;
}, $forecast);

$bestForecast = $forecastScored[0];
$worstForecast = $forecastScored[0];
foreach ($forecastScored as $point) {
    if ($point['score'] > $bestForecast['score']) {
        $bestForecast = $point;
    }

    if ($point['score'] < $worstForecast['score']) {
        $worstForecast = $point;
    }
}

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

$profileLabel = 'Balanced';
if ($selectedPoint['physical'] >= $selectedPoint['emotional'] && $selectedPoint['physical'] >= $selectedPoint['intellectual']) {
    $profileLabel = 'Physical peak';
} elseif ($selectedPoint['emotional'] >= $selectedPoint['physical'] && $selectedPoint['emotional'] >= $selectedPoint['intellectual']) {
    $profileLabel = 'Emotional peak';
} elseif ($selectedPoint['intellectual'] >= $selectedPoint['physical'] && $selectedPoint['intellectual'] >= $selectedPoint['emotional']) {
    $profileLabel = 'Mental peak';
}

$ritualAverage = ($selectedPoint['physical'] + $selectedPoint['emotional'] + $selectedPoint['intellectual']) / 3;
$ritualDominantKey = 'physical';
$ritualDominantValue = $selectedPoint['physical'];
foreach (['emotional', 'intellectual'] as $key) {
    if ($selectedPoint[$key] > $ritualDominantValue) {
        $ritualDominantKey = $key;
        $ritualDominantValue = $selectedPoint[$key];
    }
}

$ritualLabels = [
    'physical' => 'Físico',
    'emotional' => 'Emocional',
    'intellectual' => 'Intelectual',
];

if ($ritualAverage >= 0.35) {
    if ($ritualDominantKey === 'physical') {
        $ritual = [
            'badge' => 'Ventana de empuje',
            'focus' => 'El cuerpo lidera y la media acompaña.',
            'why' => 'Hoy conviene usar la energía para movimiento, entrega o cierres que consumen fuerza.',
            'lines' => [
                'Empieza con una acción física y un cierre rápido.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            ],
        ];
    } elseif ($ritualDominantKey === 'emotional') {
        $ritual = [
            'badge' => 'Ventana social',
            'focus' => 'La parte emocional está arriba y la ventana favorece el vínculo.',
            'why' => 'Hoy funciona mejor coordinar, escuchar y dejar espacio para conversaciones con tacto.',
            'lines' => [
                'Empieza con una conversación importante o una coordinación pendiente.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            ],
        ];
    } else {
        $ritual = [
            'badge' => 'Ventana mental',
            'focus' => 'El ritmo intelectual domina y el promedio acompaña.',
            'why' => 'Hoy conviene escribir, decidir y estructurar antes que dispersarte en tareas largas.',
            'lines' => [
                'Empieza con escritura, estructura o una decisión clara.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            ],
        ];
    }
} elseif ($ritualAverage >= 0.1) {
    if ($ritualDominantKey === 'physical') {
        $ritual = [
            'badge' => 'Ritmo estable',
            'focus' => 'La energía física ayuda, pero sin exceso.',
            'why' => 'Va mejor para avanzar paso a paso y evitar maratones innecesarias.',
            'lines' => [
                'Empieza con una acción útil y concreta, sin cargar demasiado la agenda.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión ligera y deja algo de margen para mañana.',
            ],
        ];
    } elseif ($ritualDominantKey === 'emotional') {
        $ritual = [
            'badge' => 'Ritmo sensible',
            'focus' => 'La lectura emocional tira del día.',
            'why' => 'Funciona mejor para coordinar, cuidar el tono y no forzar decisiones duras.',
            'lines' => [
                'Empieza con coordinación suave y un mensaje claro.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión ligera y deja algo de margen para mañana.',
            ],
        ];
    } else {
        $ritual = [
            'badge' => 'Ritmo analítico',
            'focus' => 'El intelecto sostiene el día, pero la energía total no es alta.',
            'why' => 'Mejor una lista corta que un proyecto interminable.',
            'lines' => [
                'Empieza con foco corto y una revisión de lo importante.',
                sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
                'Cierra con una revisión ligera y deja algo de margen para mañana.',
            ],
        ];
    }
} elseif ($ritualAverage >= -0.15) {
    $ritual = [
        'badge' => 'Ventana neutra',
        'focus' => 'No hay un pico claro, así que simplifica.',
        'why' => 'La mejor jugada es bajar fricción, limpiar pendientes y no meter demasiada carga.',
        'lines' => [
            'Empieza ordenando una sola cosa que te quite ruido.',
            sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
            'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
        ],
    ];
} else {
    $ritual = [
        'badge' => 'Ventana de recuperación',
        'focus' => 'La lectura general está baja.',
        'why' => 'Sirve más para descansar, documentar o hacer tareas mecánicas que para empujar fuerte.',
        'lines' => [
            'Empieza bajando intensidad y eligiendo una sola tarea mecánica.',
            sprintf('Reserva %s para la tarea más importante y evita empujar en %s.', $bestForecast['label'], $worstForecast['label']),
            'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
        ],
    ];
}

$ritual['tags'] = [
    $ritualLabels[$ritualDominantKey],
    $bestForecast['label'],
    $worstForecast['label'],
];
$ritual['note'] = sprintf('Ritual de 3 pasos para un día con foco en %s.', strtolower($ritualLabels[$ritualDominantKey]));

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
