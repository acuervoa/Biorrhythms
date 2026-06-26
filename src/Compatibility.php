<?php

declare(strict_types=1);

namespace Biorrhythms;

class Compatibility
{
    /**
     * Returns a score in [0, 1] measuring how close two rhythm values are.
     * Identical values → 1.0. Opposite extremes (+1 vs -1) → 0.0.
     */
    public static function score(float $a, float $b): float
    {
        return max(0.0, 1.0 - abs($a - $b) / 2.0);
    }

    /**
     * Average compatibility across the three rhythms for a pair of day-points.
     * Each point must have 'physical', 'emotional', 'intellectual' keys (floats in [-1, 1]).
     *
     * @param array{physical: float, emotional: float, intellectual: float} $a
     * @param array{physical: float, emotional: float, intellectual: float} $b
     */
    public static function pointScore(array $a, array $b): float
    {
        return (
            self::score($a['physical'],     $b['physical'])     +
            self::score($a['emotional'],    $b['emotional'])    +
            self::score($a['intellectual'], $b['intellectual'])
        ) / 3.0;
    }
}
