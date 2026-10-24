<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Minimal RFC 5545 writer for all-day critical-day events. */
class Ics
{
    private const LABELS = ['physical' => 'Físico', 'emotional' => 'Emocional', 'intellectual' => 'Intelectual'];
    private const KINDS  = ['single' => 'simple', 'double' => 'doble', 'triple' => 'triple'];

    /**
     * @param list<array{date: string, kind: string, rhythms: list<array{key: string, direction: string}>}> $days
     */
    public static function criticalDays(array $days, \DateTimeImmutable $birth, \DateTimeImmutable $stamp): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Biorrhythms//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Biorritmos: días críticos',
        ];
        $dtstamp = $stamp->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');

        foreach ($days as $day) {
            $date  = DateInput::parse($day['date']);
            $parts = array_map(
                static fn (array $r): string => self::LABELS[$r['key']] . ($r['direction'] === 'rising' ? ' ↑' : ' ↓'),
                $day['rhythms'],
            );

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:crit-' . $birth->format('Ymd') . '-' . $date->format('Ymd') . '@biorrhythms';
            $lines[] = 'DTSTAMP:' . $dtstamp;
            $lines[] = 'DTSTART;VALUE=DATE:' . $date->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $date->modify('+1 day')->format('Ymd');
            $lines[] = 'SUMMARY:' . self::escape('Día crítico ' . self::KINDS[$day['kind']] . ': ' . implode(', ', $parts));
            $lines[] = 'DESCRIPTION:' . self::escape(
                'Cruce por cero de: ' . implode(', ', $parts) . '. '
                . 'Conviene dejar margen y evitar decisiones difíciles de revertir. '
                . 'Herramienta de reflexión, sin validez predictiva demostrada.'
            );
            $lines[] = 'TRANSP:TRANSPARENT';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines)) . "\r\n";
    }

    public static function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\\,', '\\n', '\\n'], $text);
    }

    /** Folds a content line at 75 octets without splitting a UTF-8 character. */
    public static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $out   = '';
        $first = true;
        while ($line !== '') {
            $chunk = mb_strcut($line, 0, $first ? 75 : 74, 'UTF-8');
            $out  .= ($first ? '' : "\r\n ") . $chunk;
            $line  = substr($line, strlen($chunk));
            $first = false;
        }

        return $out;
    }
}
