<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use Biorrhythms\CriticalDays;
use Biorrhythms\Ics;
use PHPUnit\Framework\TestCase;

class IcsTest extends TestCase
{
    private DateTimeImmutable $stamp;

    protected function setUp(): void
    {
        $this->stamp = new DateTimeImmutable('2026-10-24 10:00:00', new DateTimeZone('UTC'));
    }

    private function build(string $from = '1990-01-01', string $to = '1990-03-01', int $min = 1): string
    {
        $birth = DateInput::parse('1990-01-01');
        $days  = CriticalDays::find($birth, DateInput::parse($from), DateInput::parse($to), $min);

        return Ics::criticalDays($days, $birth, $this->stamp);
    }

    public function testCalendarEnvelope(): void
    {
        $ics = $this->build();

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString("PRODID:-//Biorrhythms//ES\r\n", $ics);
    }

    public function testEveryLineEndsInCrlfAndNeverExceeds75Octets(): void
    {
        $ics = $this->build('1990-01-01', '1992-01-01');

        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $ics));
        foreach (explode("\r\n", rtrim($ics, "\r\n")) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), $line);
        }
    }

    public function testOneEventPerCriticalDayWithAllDayDates(): void
    {
        $birth = DateInput::parse('1990-01-01');
        $days  = CriticalDays::find($birth, $birth, DateInput::parse('1990-03-01'));
        $ics   = Ics::criticalDays($days, $birth, $this->stamp);

        $this->assertSame(count($days), substr_count($ics, "BEGIN:VEVENT\r\n"));
        $this->assertSame(count($days), substr_count($ics, "END:VEVENT\r\n"));
        $this->assertStringContainsString("DTSTART;VALUE=DATE:19900101\r\n", $ics);
        $this->assertStringContainsString("DTEND;VALUE=DATE:19900102\r\n", $ics);
        $this->assertStringContainsString("DTSTAMP:20261024T100000Z\r\n", $ics);
    }

    public function testUidIsStableAcrossRuns(): void
    {
        $this->assertSame($this->build(), $this->build());
        $this->assertStringContainsString("UID:crit-19900101-19900101@biorrhythms\r\n", $this->build());
    }

    public function testSummaryNamesKindAndRhythms(): void
    {
        $ics = $this->build('1990-01-01', '1990-01-01');

        $this->assertStringContainsString('SUMMARY:Día crítico triple', str_replace("\r\n ", '', $ics));
    }

    public function testEscapesSpecialCharacters(): void
    {
        $this->assertSame('a\\,b\;c\\\\d\\ne', Ics::escape("a,b;c\\d\ne"));
    }

    public function testFoldKeepsMultibyteCharactersWhole(): void
    {
        $long   = 'DESCRIPTION:' . str_repeat('áéíóú', 30);
        $folded = Ics::fold($long);

        foreach (explode("\r\n", $folded) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
            $this->assertTrue(mb_check_encoding($line, 'UTF-8'));
        }
        $this->assertSame($long, str_replace("\r\n ", '', $folded));
    }

    public function testEmptyListStillProducesAValidCalendar(): void
    {
        $ics = Ics::criticalDays([], DateInput::parse('1990-01-01'), $this->stamp);

        $this->assertStringNotContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
    }
}
