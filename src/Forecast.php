<?php

declare(strict_types=1);

namespace Biorrhythms;

class Forecast
{
    public static function average(array $point): float
    {
        return ($point['physical'] + $point['emotional'] + $point['intellectual']) / 3.0;
    }

    /** @param list<array> $points Window points with physical/emotional/intellectual keys */
    public static function scoreWindow(array $points): array
    {
        return array_map(static function (array $point): array {
            $point['score'] = self::average($point);
            return $point;
        }, $points);
    }

    /** @param list<array> $scoredPoints Points already scored via scoreWindow() */
    public static function best(array $scoredPoints): array
    {
        return array_reduce(
            $scoredPoints,
            static fn (array $carry, array $point) => $point['score'] > $carry['score'] ? $point : $carry,
            $scoredPoints[0],
        );
    }

    /** @param list<array> $scoredPoints Points already scored via scoreWindow() */
    public static function worst(array $scoredPoints): array
    {
        return array_reduce(
            $scoredPoints,
            static fn (array $carry, array $point) => $point['score'] < $carry['score'] ? $point : $carry,
            $scoredPoints[0],
        );
    }

    /** Dominant rhythm key ('physical' | 'emotional' | 'intellectual') for a point */
    public static function dominantKey(array $point): string
    {
        $keys = ['physical', 'emotional', 'intellectual'];
        return array_reduce(
            $keys,
            static fn (string $carry, string $key) => $point[$key] > $point[$carry] ? $key : $carry,
            'physical',
        );
    }
}
