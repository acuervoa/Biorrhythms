<?php

declare(strict_types=1);

namespace Biorrhythms;

class Profile
{
    private const LABELS_ES = [
        'physical'     => 'Pico físico',
        'emotional'    => 'Pico emocional',
        'intellectual' => 'Pico mental',
    ];

    private const LABELS_EN = [
        'physical'     => 'Physical peak',
        'emotional'    => 'Emotional peak',
        'intellectual' => 'Mental peak',
    ];

    /** @param array{physical: float, emotional: float, intellectual: float} $point */
    public static function key(array $point): string
    {
        return Forecast::dominantKey($point);
    }

    /**
     * Rhythm with the largest magnitude, regardless of sign. key() picks the highest value instead,
     * which for an all-negative day is merely the least negative rhythm.
     *
     * @return array{key: string, polarity: string} polarity: 'high' (>= 0) | 'low' (< 0)
     */
    public static function strongest(array $point): array
    {
        $best = 'physical';
        foreach (['emotional', 'intellectual'] as $key) {
            if (abs($point[$key]) > abs($point[$best])) {
                $best = $key;
            }
        }

        return ['key' => $best, 'polarity' => $point[$best] >= 0.0 ? 'high' : 'low'];
    }

    public static function labelEs(array $point): string
    {
        return self::LABELS_ES[self::key($point)];
    }

    /** Legacy label kept for the unversioned /api/ endpoint. */
    public static function labelEn(array $point): string
    {
        return self::LABELS_EN[self::key($point)];
    }
}
