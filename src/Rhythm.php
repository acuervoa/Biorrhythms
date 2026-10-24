<?php

declare(strict_types=1);

namespace Biorrhythms;

class Rhythm
{
    public const PERIODS = [
        'physical'     => 23,
        'emotional'    => 28,
        'intellectual' => 33,
    ];

    /** |value| at or above this counts as a peak/valley rather than a rising/falling stretch. */
    private const EXTREME = 0.9;

    /**
     * Where a rhythm stands on a given day since birth.
     *
     * state: 'peak' | 'valley' | 'rising' | 'falling'. slope is the derivative's sign (cos) in [-1, 1].
     *
     * @return array{period: int, day_in_cycle: int, value: float, slope: float, state: string}
     */
    public static function phase(string $key, float $days): array
    {
        $period = self::PERIODS[$key] ?? throw new \InvalidArgumentException("Unknown rhythm: {$key}");
        $bio    = new Biorrhythms();

        $value = match ($key) {
            'physical'  => $bio->calculatePhysical($days),
            'emotional' => $bio->calculateEmotional($days),
            default     => $bio->calculateIntellectual($days),
        };
        $slope = cos(2 * M_PI * $days / $period);

        $state = match (true) {
            $value >= self::EXTREME  => 'peak',
            $value <= -self::EXTREME => 'valley',
            $slope >= 0              => 'rising',
            default                  => 'falling',
        };

        return [
            'period'       => $period,
            'day_in_cycle' => ((int) floor($days) % $period + $period) % $period + 1,
            'value'        => $value,
            'slope'        => $slope,
            'state'        => $state,
        ];
    }
}
