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

    /** Weights per preset; same values as the web's PRESET_WEIGHTS. Unknown presets weigh the rhythms equally. */
    public static function weights(string $preset): array
    {
        return match ($preset) {
            'pair'   => ['physical' => 0.20, 'emotional' => 0.50, 'intellectual' => 0.30],
            'friend' => ['physical' => 0.25, 'emotional' => 0.45, 'intellectual' => 0.30],
            'work'   => ['physical' => 0.25, 'emotional' => 0.20, 'intellectual' => 0.55],
            default  => ['physical' => 1 / 3, 'emotional' => 1 / 3, 'intellectual' => 1 / 3],
        };
    }

    /** Preset-weighted closeness of two day-points, in [0, 1]. */
    public static function weightedScore(array $a, array $b, string $preset): float
    {
        $w = self::weights($preset);

        return $w['physical'] * self::score($a['physical'], $b['physical'])
            + $w['emotional'] * self::score($a['emotional'], $b['emotional'])
            + $w['intellectual'] * self::score($a['intellectual'], $b['intellectual']);
    }

    /**
     * Whether each rhythm moves the same way (both rising or both falling) for two people.
     * Takes slopes as produced by Rhythm::phase().
     *
     * @return array{physical: bool, emotional: bool, intellectual: bool}
     */
    public static function trendAgreement(array $slopesA, array $slopesB): array
    {
        $agree = static fn (string $key): bool => ($slopesA[$key] >= 0) === ($slopesB[$key] >= 0);

        return [
            'physical'     => $agree('physical'),
            'emotional'    => $agree('emotional'),
            'intellectual' => $agree('intellectual'),
        ];
    }
}
