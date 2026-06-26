<?php

declare(strict_types=1);

namespace Biorrhythms;

class Ritual
{
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
}
