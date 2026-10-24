<?php

declare(strict_types=1);

namespace Biorrhythms;

class Timeline
{
    /**
     * Rhythm values for one date.
     *
     * @return array{physical: float, emotional: float, intellectual: float}
     */
    public static function values(Biorrhythms $bio, \DateTimeImmutable $birth, \DateTimeImmutable $date): array
    {
        $days = DateInput::daysBetween($birth, $date);

        return [
            'physical'     => $bio->calculatePhysical($days),
            'emotional'    => $bio->calculateEmotional($days),
            'intellectual' => $bio->calculateIntellectual($days),
        ];
    }

    /**
     * Daily points from focus-$radius to focus+$radius (inclusive).
     *
     * @return list<array{date: string, label: string, offset: int, physical: float, emotional: float, intellectual: float}>
     */
    public static function window(
        Biorrhythms $bio,
        \DateTimeImmutable $birth,
        \DateTimeImmutable $focus,
        int $radius,
    ): array {
        $window = [];

        for ($offset = -$radius; $offset <= $radius; $offset++) {
            $date = $focus->modify(sprintf('%+d day', $offset));

            $window[] = [
                'date'   => $date->format('Y-m-d'),
                'label'  => DateFormatter::short($date),
                'offset' => $offset,
            ] + self::values($bio, $birth, $date);
        }

        return $window;
    }
}
