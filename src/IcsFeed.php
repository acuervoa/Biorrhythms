<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Request handling for calendar.php: critical days as an .ics download. */
class IcsFeed
{
    private const DEFAULT_DAYS = 90;
    private const MAX_DAYS     = 366;

    /**
     * @return array{0: int, 1: string, 2: string} HTTP status, content type and body
     */
    public static function handle(array $query, \DateTimeImmutable $now): array
    {
        $birth = DateInput::parse($query['birth'] ?? null);
        if ($birth === null) {
            return self::error('Parámetro birth obligatorio, formato YYYY-MM-DD.');
        }

        $rawFrom = $query['from'] ?? null;
        $from    = ($rawFrom === null || $rawFrom === '') ? DateInput::parse($now->format('Y-m-d')) : DateInput::parse($rawFrom);
        if ($from === null) {
            return self::error('Parámetro from inválido, formato YYYY-MM-DD.');
        }

        $rawMin = $query['min'] ?? '1';
        $min    = is_string($rawMin) && preg_match('/\A[1-3]\z/', $rawMin) === 1 ? (int) $rawMin : null;
        if ($min === null) {
            return self::error('Parámetro min debe ser 1, 2 o 3.');
        }

        $rawDays = $query['days'] ?? null;
        $days    = is_string($rawDays) && ctype_digit($rawDays) ? (int) $rawDays : self::DEFAULT_DAYS;
        $days    = max(1, min(self::MAX_DAYS, $days));

        $to = $from->modify('+' . ($days - 1) . ' day');

        return [
            200,
            'text/calendar; charset=utf-8',
            Ics::criticalDays(CriticalDays::find($birth, $from, $to, $min), $birth, $now),
        ];
    }

    private static function error(string $message): array
    {
        return [400, 'text/plain; charset=utf-8', $message . "\n"];
    }
}
