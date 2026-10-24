<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Date-based numerology (Pythagorean reduction). Name-based numbers are out of scope. */
class Numerology
{
    private const MASTERS = [11, 22, 33];

    private const MEANINGS = [
        1  => ['Iniciativa', 'empezar, decidir, liderar'],
        2  => ['Cooperación', 'escuchar, equilibrar, acompañar'],
        3  => ['Expresión', 'comunicar, crear, disfrutar'],
        4  => ['Estructura', 'ordenar, construir, sostener'],
        5  => ['Cambio', 'moverse, probar, adaptarse'],
        6  => ['Cuidado', 'responsabilidad, hogar, vínculos'],
        7  => ['Reflexión', 'estudiar, silencio, análisis'],
        8  => ['Logro', 'recursos, autoridad, resultados'],
        9  => ['Cierre', 'soltar, completar, generosidad'],
        11 => ['Intuición', 'inspiración, sensibilidad, visión'],
        22 => ['Constructor', 'proyectos grandes, realización práctica'],
        33 => ['Maestro', 'servicio, guía, entrega'],
    ];

    /** Digit-sum reduction; with $keepMasters the master numbers 11, 22 and 33 are not reduced further. */
    public static function reduce(int $n, bool $keepMasters): int
    {
        while ($n > 9) {
            if ($keepMasters && in_array($n, self::MASTERS, true)) {
                break;
            }
            $n = array_sum(array_map('intval', str_split((string) $n)));
        }

        return $n;
    }

    /** Life path: month, day and year reduced separately (masters kept), then summed and reduced. */
    public static function lifePath(\DateTimeImmutable $birth): int
    {
        return self::reduce(
            self::reduce((int) $birth->format('n'), true)
            + self::reduce((int) $birth->format('j'), true)
            + self::reduce((int) $birth->format('Y'), true),
            true,
        );
    }

    /**
     * Personal year / month / day (1-9) for $date. Uses the calendar year, not the birthday-to-birthday cycle.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function personal(\DateTimeImmutable $birth, \DateTimeImmutable $date): array
    {
        $year = self::reduce(
            self::reduce((int) $birth->format('n'), false)
            + self::reduce((int) $birth->format('j'), false)
            + self::reduce((int) $date->format('Y'), false),
            false,
        );
        $month = self::reduce($year + (int) $date->format('n'), false);
        $day   = self::reduce($month + (int) $date->format('j'), false);

        return ['year' => $year, 'month' => $month, 'day' => $day];
    }

    /** @return array{name: string, keywords: string} */
    public static function meaning(int $number): array
    {
        [$name, $keywords] = self::MEANINGS[$number] ?? ['', ''];

        return ['name' => $name, 'keywords' => $keywords];
    }
}
