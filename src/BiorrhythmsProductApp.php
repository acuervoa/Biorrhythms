<?php

declare(strict_types=1);

namespace Biorrhythms;

require_once __DIR__ . '/BiorrhythmsProduct.php';

final class BiorrhythmsProductApp
{
    public static function buildContext(array $query = []): array
    {
        $birthInput = biorrhythms_clamp_date_input($query['birth'] ?? null, '1990-01-01');
        $focusInput = biorrhythms_clamp_date_input($query['focus'] ?? null, (new \DateTimeImmutable('today'))->format('Y-m-d'));
        $compatPresetInput = $query['preset'] ?? 'custom';
        $compatPresetInput = in_array($compatPresetInput, ['pair', 'friend', 'work', 'custom'], true) ? $compatPresetInput : 'custom';

        $birthDate = new \DateTimeImmutable($birthInput);
        $focusDate = new \DateTimeImmutable($focusInput);
        $partnerBirthInput = $compatPresetInput === 'custom'
            ? biorrhythms_clamp_date_input($query['partner_birth'] ?? null, '1991-01-01')
            : (biorrhythms_partner_birth_for_preset($compatPresetInput, $focusDate) ?? biorrhythms_clamp_date_input($query['partner_birth'] ?? null, '1991-01-01'));
        $partnerBirthDate = new \DateTimeImmutable($partnerBirthInput);
        $bio = new Biorrhythms();

        $windowRadius = 45;
        $window = [];
        for ($offset = -$windowRadius; $offset <= $windowRadius; $offset++) {
            $date = $focusDate->modify(($offset >= 0 ? '+' : '') . $offset . ' day');
            $daysSinceBirth = biorrhythms_days_between($birthDate, $date);

            $window[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D j M'),
                'offset' => $offset,
                'physical' => $bio->calculatePhysical($daysSinceBirth),
                'emotional' => $bio->calculateEmotional($daysSinceBirth),
                'intellectual' => $bio->calculateIntellectual($daysSinceBirth),
            ];
        }

        $selectedIndex = $windowRadius;
        $selectedPoint = $window[$selectedIndex];
        $focusValues = [
            'physical' => $bio->calculatePhysical(biorrhythms_days_between($birthDate, $focusDate)),
            'emotional' => $bio->calculateEmotional(biorrhythms_days_between($birthDate, $focusDate)),
            'intellectual' => $bio->calculateIntellectual(biorrhythms_days_between($birthDate, $focusDate)),
        ];
        $partnerFocusValues = [
            'physical' => $bio->calculatePhysical(biorrhythms_days_between($partnerBirthDate, $focusDate)),
            'emotional' => $bio->calculateEmotional(biorrhythms_days_between($partnerBirthDate, $focusDate)),
            'intellectual' => $bio->calculateIntellectual(biorrhythms_days_between($partnerBirthDate, $focusDate)),
        ];

        $profileLabel = 'Balanced';
        if ($selectedPoint['physical'] >= $selectedPoint['emotional'] && $selectedPoint['physical'] >= $selectedPoint['intellectual']) {
            $profileLabel = 'Physical peak';
        } elseif ($selectedPoint['emotional'] >= $selectedPoint['physical'] && $selectedPoint['emotional'] >= $selectedPoint['intellectual']) {
            $profileLabel = 'Emotional peak';
        } elseif ($selectedPoint['intellectual'] >= $selectedPoint['physical'] && $selectedPoint['intellectual'] >= $selectedPoint['emotional']) {
            $profileLabel = 'Mental peak';
        }

        $forecast = array_slice($window, $selectedIndex, 7);
        $forecastScored = array_map(static function (array $point): array {
            $point['score'] = biorrhythms_average_series($point);

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

        $compatibilityForecast = array_map(static function (array $point) use ($birthInput, $partnerBirthInput): array {
            $bio = new Biorrhythms();
            $date = new \DateTimeImmutable($point['date']);
            $daysA = biorrhythms_days_between(new \DateTimeImmutable($birthInput), $date);
            $daysB = biorrhythms_days_between(new \DateTimeImmutable($partnerBirthInput), $date);
            $rhythms = [
                [
                    'label' => 'Físico',
                    'value' => biorrhythms_compatibility_score($bio->calculatePhysical($daysA), $bio->calculatePhysical($daysB)),
                ],
                [
                    'label' => 'Emocional',
                    'value' => biorrhythms_compatibility_score($bio->calculateEmotional($daysA), $bio->calculateEmotional($daysB)),
                ],
                [
                    'label' => 'Intelectual',
                    'value' => biorrhythms_compatibility_score($bio->calculateIntellectual($daysA), $bio->calculateIntellectual($daysB)),
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

        $decision = self::decisionForPoint($selectedPoint);
        $ritual = self::ritualForPoint($selectedPoint, $forecastScored, $bestForecast, $worstForecast, $decision);

        $baseQuery = [
            'birth' => $birthInput,
            'focus' => $focusInput,
            'partner_birth' => $partnerBirthInput,
        ];
        if ($compatPresetInput !== 'custom') {
            $baseQuery['preset'] = $compatPresetInput;
        }

        $apiQuery = $baseQuery + ['pretty' => '1'];
        $widgetQuery = $baseQuery + ['embed' => '1'];

        $focusSummary = sprintf(
            '%s · %s · %s',
            number_format($focusValues['physical'] * 100, 1, '.', '') . '%',
            number_format($focusValues['emotional'] * 100, 1, '.', '') . '%',
            number_format($focusValues['intellectual'] * 100, 1, '.', '') . '%'
        );

        return [
            'birthInput' => $birthInput,
            'focusInput' => $focusInput,
            'partnerBirthInput' => $partnerBirthInput,
            'compatPresetInput' => $compatPresetInput,
            'windowRadius' => $windowRadius,
            'window' => $window,
            'selectedIndex' => $selectedIndex,
            'selectedPoint' => $selectedPoint,
            'profileLabel' => $profileLabel,
            'focusValues' => $focusValues,
            'focusSummary' => $focusSummary,
            'forecast' => $forecastScored,
            'bestForecast' => $bestForecast,
            'worstForecast' => $worstForecast,
            'compatibility' => [
                'score' => biorrhythms_point_compatibility($focusValues, $partnerFocusValues),
                'current' => [
                    'physical' => biorrhythms_compatibility_score($focusValues['physical'], $partnerFocusValues['physical']),
                    'emotional' => biorrhythms_compatibility_score($focusValues['emotional'], $partnerFocusValues['emotional']),
                    'intellectual' => biorrhythms_compatibility_score($focusValues['intellectual'], $partnerFocusValues['intellectual']),
                ],
                'forecast' => $compatibilityForecast,
                'bestDay' => $bestCompatibility,
                'worstDay' => $worstCompatibility,
            ],
            'ritual' => $ritual,
            'decision' => $decision,
            'links' => [
                'demo' => '/demo/?' . http_build_query($baseQuery, '', '&', PHP_QUERY_RFC3986),
                'api' => '/api/?' . http_build_query($apiQuery, '', '&', PHP_QUERY_RFC3986),
                'widget' => '/demo/?' . http_build_query($widgetQuery, '', '&', PHP_QUERY_RFC3986),
            ],
            'snippets' => [
                'widget' => '<iframe src="/demo/?' . http_build_query($widgetQuery, '', '&', PHP_QUERY_RFC3986) . '" title="Biorrhythms widget" width="420" height="320" loading="lazy" style="border:0;border-radius:20px;overflow:hidden;"></iframe>',
                'api' => 'curl -s "/api/?' . http_build_query($apiQuery, '', '&', PHP_QUERY_RFC3986) . '" | jq',
            ],
        ];
    }

    private static function decisionForPoint(array $point): array
    {
        $avg = biorrhythms_average_series($point);
        $dominant = biorrhythms_dominant_series($point);

        if ($avg >= 0.35) {
            if ($dominant['key'] === 'physical') {
                return [
                    'badge' => 'Ventana de empuje',
                    'title' => 'Entrena fuerte o resuelve lo físico',
                    'why' => 'El cuerpo lidera y la media general acompaña.',
                    'tags' => ['Entrenar', 'Cerrar tareas', 'Mover agenda'],
                ];
            }

            if ($dominant['key'] === 'emotional') {
                return [
                    'badge' => 'Ventana social',
                    'title' => 'Toma conversaciones importantes',
                    'why' => 'La parte emocional está arriba y la ventana favorece el vínculo.',
                    'tags' => ['Conversar', 'Escuchar', 'Alinear'],
                ];
            }

            return [
                'badge' => 'Ventana mental',
                'title' => 'Escribe, decide y estructura',
                'why' => 'El ritmo intelectual domina y el promedio acompaña.',
                'tags' => ['Escribir', 'Planificar', 'Resolver'],
            ];
        }

        if ($avg >= 0.1) {
            if ($dominant['key'] === 'physical') {
                return [
                    'badge' => 'Ritmo estable',
                    'title' => 'Haz trabajo útil y sin sobrecarga',
                    'why' => 'La energía física ayuda, pero sin exceso.',
                    'tags' => ['Progreso', 'Ritmo', 'Sin prisa'],
                ];
            }

            if ($dominant['key'] === 'emotional') {
                return [
                    'badge' => 'Ritmo sensible',
                    'title' => 'Ajusta conversaciones y responde con calma',
                    'why' => 'La lectura emocional tira del día.',
                    'tags' => ['Coordinación', 'Calma', 'Feedback'],
                ];
            }

            return [
                'badge' => 'Ritmo analítico',
                'title' => 'Prioriza foco corto y revisión',
                'why' => 'El intelecto sostiene el día, pero la energía total no es alta.',
                'tags' => ['Revisar', 'Sintetizar', 'Priorizar'],
            ];
        }

        if ($avg >= -0.15) {
            return [
                'badge' => 'Ventana neutra',
                'title' => 'Mantén el ritmo y simplifica',
                'why' => 'La mejor jugada es bajar fricción.',
                'tags' => ['Ordenar', 'Reducir', 'Mantener'],
            ];
        }

        return [
            'badge' => 'Ventana de recuperación',
            'title' => 'Baja intensidad y reserva energía',
            'why' => 'La lectura general está baja.',
            'tags' => ['Descansar', 'Documentar', 'Recuperar'],
        ];
    }

    private static function ritualForPoint(array $selectedPoint, array $forecastWindow, array $bestForecast, array $worstForecast, array $decision): array
    {
        $dominant = biorrhythms_dominant_series($selectedPoint);

        $intro = match ($decision['badge']) {
            'Ventana de empuje' => 'Empieza con una acción física y un cierre rápido.',
            'Ventana social' => 'Empieza con una conversación importante o una coordinación pendiente.',
            'Ventana mental' => 'Empieza con escritura, estructura o una decisión clara.',
            'Ritmo estable' => 'Empieza con una acción útil y concreta, sin cargar demasiado la agenda.',
            'Ritmo sensible' => 'Empieza con coordinación suave y un mensaje claro.',
            'Ritmo analítico' => 'Empieza con foco corto y una revisión de lo importante.',
            'Ventana neutra' => 'Empieza ordenando una sola cosa que te quite ruido.',
            default => 'Empieza bajando intensidad y eligiendo una sola tarea mecánica.',
        };

        $followUp = sprintf(
            'Reserva %s para la tarea más importante y evita empujar en %s.',
            $bestForecast['label'],
            $worstForecast['label']
        );

        $closing = match ($decision['badge']) {
            'Ventana de empuje', 'Ventana social', 'Ventana mental' =>
                'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            default =>
                'Cierra con una revisión ligera y deja algo de margen para mañana.',
        };

        return [
            'badge' => $decision['badge'],
            'focus' => $dominant['label'] . ' marca el tono de hoy.',
            'why' => $decision['why'] . ' La mejor ventana de la semana cae en ' . $bestForecast['label'] . '.',
            'lines' => [$intro, $followUp, $closing],
            'tags' => [$dominant['label'], $bestForecast['label'], $worstForecast['label']],
            'note' => sprintf('Ritual de 3 pasos para un día con foco en %s.', strtolower($dominant['label'])),
        ];
    }
}
