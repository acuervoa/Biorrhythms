<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Everything the daily ritual needs for one date, in one place, shared by the web and /api/v1/. */
class DailyReading
{
    /**
     * @param list<array> $window Timeline::window() points; must contain $date
     * @return array{
     *   ritual: array, phases: array, critical: list<array>, personal: array{year: int, month: int, day: int},
     *   conflict: bool, forecast_days: int
     * }
     */
    public static function forDate(\DateTimeImmutable $birth, \DateTimeImmutable $date, array $window): array
    {
        $key   = $date->format('Y-m-d');
        $index = array_search($key, array_column($window, 'date'), true);
        if ($index === false) {
            throw new \InvalidArgumentException("Date {$key} is not in the window");
        }

        $point    = $window[$index];
        $forecast = Forecast::scoreWindow(array_slice($window, $index, 7));
        $days     = DateInput::daysBetween($birth, $date);

        $phases   = [];
        $critical = [];
        foreach (array_keys(Rhythm::PERIODS) as $rhythm) {
            $phases[$rhythm] = Rhythm::phase($rhythm, $days);
            $direction       = CriticalDays::direction($rhythm, (int) $days);
            if ($direction !== null) {
                $critical[] = ['key' => $rhythm, 'direction' => $direction];
            }
        }

        $personal = Numerology::personal($birth, $date);
        $conflict = Forecast::hasConflict($point);

        return [
            'ritual' => Ritual::daily($point, $forecast, [
                'phases'       => $phases,
                'critical'     => $critical,
                'conflict'     => $conflict,
                'all_negative' => Forecast::allNegative($point),
                'personal_day' => $personal['day'],
                'seed'         => intdiv($date->getTimestamp(), 86400),
            ]),
            'phases'        => $phases,
            'critical'      => $critical,
            'personal'      => $personal,
            'conflict'      => $conflict,
            'forecast_days' => count($forecast),
        ];
    }
}
