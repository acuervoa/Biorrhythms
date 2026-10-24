<?php

declare(strict_types=1);

namespace Biorrhythms;

/**
 * Strict parsing and calendar arithmetic for user-supplied dates.
 *
 * All dates are handled as UTC midnight so day counts never depend on the
 * server timezone or DST transitions.
 */
class DateInput
{
    public const MIN = '1900-01-01';
    public const MAX = '2100-12-31';

    /** Returns the date at UTC midnight, or null when the input is not a valid Y-m-d in [MIN, MAX]. */
    public static function parse(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) !== 1) {
            return null;
        }

        $date   = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();

        if ($date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
            || $value < self::MIN
            || $value > self::MAX
        ) {
            return null;
        }

        return $date;
    }

    /** Valid input as Y-m-d, otherwise $fallback. */
    public static function normalize(mixed $value, string $fallback): string
    {
        $date = self::parse($value);

        return $date === null ? $fallback : $date->format('Y-m-d');
    }

    /** Signed calendar days from $from to $to, using each date's own Y-m-d. */
    public static function daysBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $utc  = new \DateTimeZone('UTC');
        $diff = (new \DateTimeImmutable($from->format('Y-m-d'), $utc))
            ->diff(new \DateTimeImmutable($to->format('Y-m-d'), $utc));

        return $diff->invert === 1 ? -(float) $diff->days : (float) $diff->days;
    }
}
