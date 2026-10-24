<?php

declare(strict_types=1);

namespace Biorrhythms;

/**
 * Critical days: days on which a rhythm crosses zero.
 *
 * A rhythm crosses zero at t = k * period / 2. A crossing is assigned to the
 * day d whose window [d - 0.5, d + 0.5) contains it, so the half-day crossings
 * of odd periods (23, 33) round up. Even k crosses upwards, odd k downwards.
 * Days where two or three rhythms are critical at once are "double" / "triple".
 */
class CriticalDays
{
    private const KINDS = [1 => 'single', 2 => 'double', 3 => 'triple'];

    /** 'rising' | 'falling' when the rhythm crosses zero on day $day since birth, otherwise null. */
    public static function direction(string $key, int $day): ?string
    {
        $period = Rhythm::PERIODS[$key] ?? throw new \InvalidArgumentException("Unknown rhythm: {$key}");

        $k = (int) ceil((2 * $day - 1) / $period);
        if ($k * $period >= 2 * $day + 1) {
            return null;
        }

        return $k % 2 === 0 ? 'rising' : 'falling';
    }

    /**
     * Critical days from $from to $to (inclusive) with at least $minCount rhythms crossing.
     *
     * @return list<array{date: string, label: string, count: int, kind: string, rhythms: list<array{key: string, direction: string}>}>
     */
    public static function find(
        \DateTimeImmutable $birth,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $minCount = 1,
    ): array {
        $span = (int) DateInput::daysBetween($from, $to);
        $base = (int) DateInput::daysBetween($birth, $from);
        $out  = [];

        for ($offset = 0; $offset <= $span; $offset++) {
            $rhythms = [];
            foreach (array_keys(Rhythm::PERIODS) as $key) {
                $direction = self::direction($key, $base + $offset);
                if ($direction !== null) {
                    $rhythms[] = ['key' => $key, 'direction' => $direction];
                }
            }

            if (count($rhythms) < max(1, $minCount)) {
                continue;
            }

            $date  = $from->modify(sprintf('%+d day', $offset));
            $out[] = [
                'date'    => $date->format('Y-m-d'),
                'label'   => DateFormatter::long($date),
                'count'   => count($rhythms),
                'kind'    => self::KINDS[count($rhythms)],
                'rhythms' => $rhythms,
            ];
        }

        return $out;
    }
}
