<?php

declare(strict_types=1);

namespace Biorrhythms;

/**
 * Moon phase from the mean synodic month. The real lunation varies by up to ~14 h around the mean,
 * so the phase is accurate to about a day: enough for a daily reading, not for astronomy.
 */
class Lunar
{
    public const SYNODIC_DAYS = 29.530588853;

    /** Reference new moon: 2000-01-06 18:14 UTC. */
    private const EPOCH = 947182440;

    private const NAMES = [
        'Luna nueva',
        'Luna creciente',
        'Cuarto creciente',
        'Gibosa creciente',
        'Luna llena',
        'Gibosa menguante',
        'Cuarto menguante',
        'Luna menguante',
    ];

    /**
     * Phase at noon UTC of the given calendar day.
     *
     * @return array{name: string, age_days: float, illumination: float}
     */
    public static function phase(\DateTimeImmutable $date): array
    {
        $utcMidnight = (new \DateTimeImmutable($date->format('Y-m-d'), new \DateTimeZone('UTC')))->getTimestamp();
        $days        = ($utcMidnight + 43200 - self::EPOCH) / 86400;
        $age         = fmod(fmod($days, self::SYNODIC_DAYS) + self::SYNODIC_DAYS, self::SYNODIC_DAYS);

        $step  = self::SYNODIC_DAYS / 8;
        $index = (int) floor(fmod($age + $step / 2, self::SYNODIC_DAYS) / $step);

        return [
            'name'         => self::NAMES[$index],
            'age_days'     => $age,
            'illumination' => (1 - cos(2 * M_PI * $age / self::SYNODIC_DAYS)) / 2,
        ];
    }
}
