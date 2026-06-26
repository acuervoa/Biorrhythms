<?php

declare(strict_types=1);

namespace Biorrhythms;

class DateFormatter
{
    private const DAYS = [
        'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié',
        'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb', 'Sun' => 'Dom',
    ];

    private const MONTHS = [
        'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr',
        'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
        'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic',
    ];

    /** "Lun 5 Nov" */
    public static function short(\DateTimeImmutable $date): string
    {
        $day   = self::DAYS[$date->format('D')]   ?? $date->format('D');
        $month = self::MONTHS[$date->format('M')] ?? $date->format('M');

        return $day . ' ' . $date->format('j') . ' ' . $month;
    }

    /** "Lun 5 Nov 2029" */
    public static function long(\DateTimeImmutable $date): string
    {
        return self::short($date) . ' ' . $date->format('Y');
    }
}
