<?php

declare(strict_types=1);

namespace Biorrhythms;

/** One month of daily averages and critical days, laid out as Monday-first weeks. */
class MonthCalendar
{
    private const MONTHS = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /**
     * @return array{label: string, year: int, month: int, weeks: list<list<array|null>>}
     */
    public static function build(\DateTimeImmutable $birth, \DateTimeImmutable $focus): array
    {
        $first = $focus->modify('first day of this month');
        $last  = $focus->modify('last day of this month');

        $critical = [];
        foreach (CriticalDays::find($birth, $first, $last) as $day) {
            $critical[$day['date']] = $day;
        }

        $bio   = new Biorrhythms();
        $cells = array_fill(0, (int) $first->format('N') - 1, null);

        for ($date = $first; $date <= $last; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $avg = Forecast::average(Timeline::values($bio, $birth, $date));

            $cells[] = [
                'date'     => $key,
                'day'      => (int) $date->format('j'),
                'avg'      => $avg,
                'level'    => $avg >= 0.35 ? 'high' : ($avg >= -0.15 ? 'mid' : 'low'),
                'critical' => $critical[$key]['count'] ?? 0,
                'kind'     => $critical[$key]['kind'] ?? null,
                'is_focus' => $key === $focus->format('Y-m-d'),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return [
            'label' => self::MONTHS[(int) $first->format('n')] . ' ' . $first->format('Y'),
            'year'  => (int) $first->format('Y'),
            'month' => (int) $first->format('n'),
            'weeks' => array_chunk($cells, 7),
        ];
    }
}
