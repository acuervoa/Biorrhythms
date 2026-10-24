<?php

declare(strict_types=1);

namespace Biorrhythms;

/**
 * Tropical sun sign from the calendar date. The exact ingress moves ±1 day between years,
 * so days next to a boundary are flagged as cusp.
 */
class Zodiac
{
    /** First day (MM-DD) of each sign, in calendar order starting with Acuario. */
    private const STARTS = [
        '01-20' => 'Acuario',
        '02-19' => 'Piscis',
        '03-21' => 'Aries',
        '04-20' => 'Tauro',
        '05-21' => 'Géminis',
        '06-21' => 'Cáncer',
        '07-23' => 'Leo',
        '08-23' => 'Virgo',
        '09-23' => 'Libra',
        '10-23' => 'Escorpio',
        '11-22' => 'Sagitario',
        '12-22' => 'Capricornio',
    ];

    private const TRAITS = [
        'Aries'       => ['Fuego', 'Cardinal'],
        'Tauro'       => ['Tierra', 'Fijo'],
        'Géminis'     => ['Aire', 'Mutable'],
        'Cáncer'      => ['Agua', 'Cardinal'],
        'Leo'         => ['Fuego', 'Fijo'],
        'Virgo'       => ['Tierra', 'Mutable'],
        'Libra'       => ['Aire', 'Cardinal'],
        'Escorpio'    => ['Agua', 'Fijo'],
        'Sagitario'   => ['Fuego', 'Mutable'],
        'Capricornio' => ['Tierra', 'Cardinal'],
        'Acuario'     => ['Aire', 'Fijo'],
        'Piscis'      => ['Agua', 'Mutable'],
    ];

    /** @return array{name: string, element: string, modality: string, cusp: bool} */
    public static function sunSign(\DateTimeImmutable $date): array
    {
        $name = self::nameFor($date);
        [$element, $modality] = self::TRAITS[$name];

        return [
            'name'     => $name,
            'element'  => $element,
            'modality' => $modality,
            'cusp'     => self::nameFor($date->modify('-1 day')) !== $name
                || self::nameFor($date->modify('+1 day')) !== $name,
        ];
    }

    private static function nameFor(\DateTimeImmutable $date): string
    {
        $monthDay = $date->format('m-d');
        $name     = 'Capricornio';

        foreach (self::STARTS as $start => $sign) {
            if ($monthDay >= $start) {
                $name = $sign;
            }
        }

        return $name;
    }
}
