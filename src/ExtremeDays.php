<?php

declare(strict_types=1);

namespace Biorrhythms;

class ExtremeDays
{
    // LCM(23, 28, 33) — full biorhythm cycle in days (~58.2 years)
    private const CYCLE_DAYS = 21252;

    /**
     * Returns all days from $fromDate to end of cycle where the average of the
     * three rhythms is ≥ $threshold or ≤ -$threshold.
     *
     * @return list<array{date: string, label: string, avg: float, physical: float, emotional: float, intellectual: float}>
     */
    public static function find(
        Biorrhythms $bio,
        \DateTimeImmutable $birthDate,
        \DateTimeImmutable $fromDate,
        float $threshold = 0.95,
    ): array {
        $daysFromBirthToFrom = (int) round(
            ($fromDate->getTimestamp() - $birthDate->getTimestamp()) / 86400
        );
        $remaining = self::CYCLE_DAYS - $daysFromBirthToFrom;
        $results   = [];

        for ($d = 0; $d <= $remaining; $d++) {
            $total = $daysFromBirthToFrom + $d;
            $p     = $bio->calculatePhysical($total);
            $e     = $bio->calculateEmotional($total);
            $i     = $bio->calculateIntellectual($total);
            $avg   = ($p + $e + $i) / 3.0;

            if ($avg >= $threshold || $avg <= -$threshold) {
                $date      = $fromDate->modify('+' . $d . ' days');
                $results[] = [
                    'date'         => $date->format('Y-m-d'),
                    'label'        => $date->format('D j M Y'),
                    'avg'          => round($avg * 100.0, 1),
                    'physical'     => round($p * 100.0, 1),
                    'emotional'    => round($e * 100.0, 1),
                    'intellectual' => round($i * 100.0, 1),
                ];
            }
        }

        return $results;
    }
}
