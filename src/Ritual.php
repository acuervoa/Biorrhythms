<?php

declare(strict_types=1);

namespace Biorrhythms;

class Ritual
{
    private const OPENINGS = [
        'push' => [
            'physical' => [
                'Empieza con una acción física y un cierre rápido.',
                'Abre el día con movimiento: algo que exija cuerpo y termine pronto.',
                'Dedica la primera franja a lo que más fuerza pida.',
            ],
            'emotional' => [
                'Empieza con una conversación importante o una coordinación pendiente.',
                'Abre el día escuchando: una llamada o un mensaje que lleve tiempo esperando.',
                'Dedica la primera franja a un asunto que dependa de otra persona.',
            ],
            'intellectual' => [
                'Empieza con escritura, estructura o una decisión clara.',
                'Abre el día con el problema que más concentración pida.',
                'Dedica la primera franja a planificar antes de ejecutar.',
            ],
        ],
        'steady' => [
            'physical' => [
                'Empieza con una acción útil y concreta, sin cargar demasiado la agenda.',
                'Avanza con una tarea física de tamaño medio y para a tiempo.',
                'Elige una sola cosa que mover hoy y hazla bien.',
            ],
            'emotional' => [
                'Empieza con coordinación suave y un mensaje claro.',
                'Abre con una conversación corta y sin tensión.',
                'Cuida el tono en lo primero que escribas o digas.',
            ],
            'intellectual' => [
                'Empieza con foco corto y una revisión de lo importante.',
                'Abre con una lista de tres cosas y no más.',
                'Revisa lo pendiente antes de abrir nada nuevo.',
            ],
        ],
        'neutral' => [
            'Empieza ordenando una sola cosa que te quite ruido.',
            'Abre el día cerrando un pendiente pequeño.',
            'Empieza por lo que menos energía te cueste.',
        ],
        'recovery' => [
            'Empieza bajando intensidad y eligiendo una sola tarea mecánica.',
            'Abre el día con algo rutinario que no exija decidir.',
            'Empieza por recuperar: agua, luz y un ritmo tranquilo.',
        ],
    ];

    private const CLOSINGS = [
        'push' => [
            'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            'Termina con diez minutos de repaso y desconecta a una hora razonable.',
            'Cierra apuntando lo que queda para mañana y suelta lo demás.',
        ],
        'steady' => [
            'Cierra con una revisión ligera y deja algo de margen para mañana.',
            'Termina con un repaso corto y sin abrir frentes nuevos.',
            'Cierra el día con una tarea menos en la lista, no con una más.',
        ],
        'neutral' => [
            'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            'Termina pronto lo que tengas y reserva la tarde para descansar.',
            'Cierra con un balance de dos líneas: qué salió bien y qué sobra.',
        ],
        'recovery' => [
            'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            'Termina temprano y protege el sueño de esta noche.',
            'Cierra sin evaluar el día: hoy el objetivo era sostenerlo.',
        ],
    ];

    private const LABELS = [
        'physical'     => 'Físico',
        'emotional'    => 'Emocional',
        'intellectual' => 'Intelectual',
    ];

    /**
     * Generate a daily ritual based on the average score and dominant rhythm.
     *
     * @return array{badge: string, focus: string, why: string, lines: list<string>, tags: list<string>, note: string}
     */
    public static function generate(
        float $average,
        string $dominantKey,
        string $bestLabel,
        string $worstLabel,
    ): array {
        $ritual = self::baseRitual($average, $dominantKey, $bestLabel, $worstLabel);
        $ritual['tags'] = [self::LABELS[$dominantKey] ?? $dominantKey, $bestLabel, $worstLabel];
        $ritual['note'] = sprintf(
            'Ritual de 3 pasos para un día con foco en %s.',
            strtolower(self::LABELS[$dominantKey] ?? $dominantKey),
        );
        return $ritual;
    }

    private static function baseRitual(
        float $average,
        string $dominant,
        string $bestLabel,
        string $worstLabel,
    ): array {
        $reserveLine = sprintf(
            'Reserva %s para la tarea más importante y evita empujar en %s.',
            $bestLabel,
            $worstLabel,
        );

        if ($average >= 0.35) {
            return match ($dominant) {
                'physical' => [
                    'badge' => 'Ventana de empuje',
                    'focus' => 'El cuerpo lidera y la media acompaña.',
                    'why'   => 'Hoy conviene usar la energía para movimiento, entrega o cierres que consumen fuerza.',
                    'lines' => [
                        'Empieza con una acción física y un cierre rápido.',
                        $reserveLine,
                        'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
                    ],
                ],
                'emotional' => [
                    'badge' => 'Ventana social',
                    'focus' => 'La parte emocional está arriba y la ventana favorece el vínculo.',
                    'why'   => 'Hoy funciona mejor coordinar, escuchar y dejar espacio para conversaciones con tacto.',
                    'lines' => [
                        'Empieza con una conversación importante o una coordinación pendiente.',
                        $reserveLine,
                        'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
                    ],
                ],
                default => [
                    'badge' => 'Ventana mental',
                    'focus' => 'El ritmo intelectual domina y el promedio acompaña.',
                    'why'   => 'Hoy conviene escribir, decidir y estructurar antes que dispersarte en tareas largas.',
                    'lines' => [
                        'Empieza con escritura, estructura o una decisión clara.',
                        $reserveLine,
                        'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
                    ],
                ],
            };
        }

        if ($average >= 0.1) {
            return match ($dominant) {
                'physical' => [
                    'badge' => 'Ritmo estable',
                    'focus' => 'La energía física ayuda, pero sin exceso.',
                    'why'   => 'Va mejor para avanzar paso a paso y evitar maratones innecesarias.',
                    'lines' => [
                        'Empieza con una acción útil y concreta, sin cargar demasiado la agenda.',
                        $reserveLine,
                        'Cierra con una revisión ligera y deja algo de margen para mañana.',
                    ],
                ],
                'emotional' => [
                    'badge' => 'Ritmo sensible',
                    'focus' => 'La lectura emocional tira del día.',
                    'why'   => 'Funciona mejor para coordinar, cuidar el tono y no forzar decisiones duras.',
                    'lines' => [
                        'Empieza con coordinación suave y un mensaje claro.',
                        $reserveLine,
                        'Cierra con una revisión ligera y deja algo de margen para mañana.',
                    ],
                ],
                default => [
                    'badge' => 'Ritmo analítico',
                    'focus' => 'El intelecto sostiene el día, pero la energía total no es alta.',
                    'why'   => 'Mejor una lista corta que un proyecto interminable.',
                    'lines' => [
                        'Empieza con foco corto y una revisión de lo importante.',
                        $reserveLine,
                        'Cierra con una revisión ligera y deja algo de margen para mañana.',
                    ],
                ],
            };
        }

        if ($average >= -0.15) {
            return [
                'badge' => 'Ventana neutra',
                'focus' => 'No hay un pico claro, así que simplifica.',
                'why'   => 'La mejor jugada es bajar fricción, limpiar pendientes y no meter demasiada carga.',
                'lines' => [
                    'Empieza ordenando una sola cosa que te quite ruido.',
                    $reserveLine,
                    'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
                ],
            ];
        }

        return [
            'badge' => 'Ventana de recuperación',
            'focus' => 'La lectura general está baja.',
            'why'   => 'Sirve más para descansar, documentar o hacer tareas mecánicas que para empujar fuerte.',
            'lines' => [
                'Empieza bajando intensidad y eligiendo una sola tarea mecánica.',
                $reserveLine,
                'Cierra con una revisión breve y una pausa consciente para no arrastrar la curva al final del día.',
            ],
        ];
    }

    /**
     * Daily ritual with rotating wording and context (critical days, conflict, trend, personal day).
     * The unversioned generate() keeps its fixed texts for the legacy API.
     *
     * @param array{physical: float, emotional: float, intellectual: float} $point
     * @param list<array> $forecastScored 7-day window starting today, scored via Forecast::scoreWindow()
     * @param array{
     *   phases: array<string, array{state: string}>,
     *   critical: list<array{key: string, direction: string}>,
     *   conflict: bool, all_negative: bool, personal_day: ?int, seed: int
     * } $ctx
     */
    public static function daily(array $point, array $forecastScored, array $ctx): array
    {
        $average  = Forecast::average($point);
        $dominant = Forecast::dominantKey($point);
        $best     = Forecast::best($forecastScored);
        $worst    = Forecast::worst($forecastScored);
        $seed     = abs($ctx['seed']);
        $label    = self::LABELS[$dominant];

        $ritual = self::generate($average, $dominant, $best['label'], $worst['label']);

        $band = match (true) {
            $average >= 0.35  => 'push',
            $average >= 0.1   => 'steady',
            $average >= -0.15 => 'neutral',
            default           => 'recovery',
        };
        $openings = self::OPENINGS[$band];
        $openings = isset($openings[$dominant]) ? $openings[$dominant] : $openings;
        $closings = self::CLOSINGS[$band];

        $extras = [];
        $count  = count($ctx['critical']);
        if ($count === 3) {
            $extras[] = 'Hoy los tres ritmos cruzan cero (día crítico triple): simplifica y evita compromisos nuevos.';
        } elseif ($count === 2) {
            $extras[] = sprintf(
                'Hoy %s y %s cruzan cero a la vez: día de transición, conviene ir despacio.',
                self::LABELS[$ctx['critical'][0]['key']],
                self::LABELS[$ctx['critical'][1]['key']],
            );
        } elseif ($count === 1) {
            $extras[] = sprintf(
                'Hoy %s cruza cero (%s): deja margen y evita decisiones difíciles de revertir.',
                self::LABELS[$ctx['critical'][0]['key']],
                $ctx['critical'][0]['direction'] === 'rising' ? 'sube' : 'baja',
            );
        }

        if ($ctx['conflict']) {
            $high = $low = 'physical';
            foreach (['emotional', 'intellectual'] as $key) {
                $high = $point[$key] > $point[$high] ? $key : $high;
                $low  = $point[$key] < $point[$low] ? $key : $low;
            }
            $extras[] = sprintf(
                'Tus ritmos van en sentidos opuestos (%s alto, %s bajo): decide con %s y contrasta con %s.',
                self::LABELS[$high],
                self::LABELS[$low],
                self::LABELS[$high],
                self::LABELS[$low],
            );
        }

        if ($ctx['all_negative']) {
            $extras[] = 'Los tres ritmos están por debajo de cero: protege el descanso y baja la exigencia.';
        }

        $extras[] = match ($ctx['phases'][$dominant]['state'] ?? 'rising') {
            'peak'    => sprintf('%s está en pico: aprovéchalo sin sobrecargar.', $label),
            'valley'  => sprintf('%s está en valle: recorta y prioriza recuperarte.', $label),
            'falling' => sprintf('%s baja: adelanta lo más exigente y deja margen después.', $label),
            default   => sprintf('%s sube: lo mejor del ciclo aún está por llegar, no lo agotes hoy.', $label),
        };

        $lines = [
            $openings[$seed % count($openings)],
            $ritual['lines'][1],
            ...array_slice($extras, 0, 2),
            $closings[($seed + 1) % count($closings)],
        ];

        $ritual['lines'] = $lines;
        $note = sprintf('Ritual de %d pasos para un día con foco en %s.', count($lines), strtolower($label));

        if ($ctx['personal_day'] !== null) {
            $meaning         = Numerology::meaning($ctx['personal_day']);
            $ritual['tags'][] = 'Día personal ' . $ctx['personal_day'];
            $note           .= sprintf(' Día personal %d (%s): %s.', $ctx['personal_day'], $meaning['name'], $meaning['keywords']);
        }
        $ritual['note'] = $note;

        return $ritual;
    }
}
