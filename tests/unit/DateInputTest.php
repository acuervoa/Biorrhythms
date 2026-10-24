<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateInputTest extends TestCase
{
    public function testParsesValidDateAtUtcMidnight(): void
    {
        $date = DateInput::parse('1972-09-19');

        $this->assertNotNull($date);
        $this->assertSame('1972-09-19 00:00:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidInput(mixed $value): void
    {
        $this->assertNull(DateInput::parse($value));
    }

    public static function invalidInputs(): array
    {
        return [
            'null'               => [null],
            'empty'              => [''],
            'garbage'            => ['abc'],
            'overflow day'       => ['2026-02-31'],
            'overflow month'     => ['2026-13-01'],
            'zero date'          => ['0000-00-00'],
            'non leap 29 feb'    => ['2023-02-29'],
            'leading space'      => [' 2026-01-01'],
            'trailing text'      => ['2026-01-01 10:00'],
            'wrong separator'    => ['2026/01/01'],
            'below min'          => ['1899-12-31'],
            'above max'          => ['2101-01-01'],
            'array'              => [['2026-01-01']],
            'int'                => [20260101],
        ];
    }

    public function testAcceptsRangeBoundariesAndLeapDay(): void
    {
        $this->assertNotNull(DateInput::parse('1900-01-01'));
        $this->assertNotNull(DateInput::parse('2100-12-31'));
        $this->assertNotNull(DateInput::parse('2024-02-29'));
    }

    public function testNormalizeReturnsValidValueOrFallback(): void
    {
        $this->assertSame('2000-02-29', DateInput::normalize('2000-02-29', '1990-01-01'));
        $this->assertSame('1990-01-01', DateInput::normalize('2026-02-31', '1990-01-01'));
        $this->assertSame('1990-01-01', DateInput::normalize(null, '1990-01-01'));
        $this->assertSame('1990-01-01', DateInput::normalize(['x'], '1990-01-01'));
    }

    public function testDaysBetweenIsCalendarDaysAndSigned(): void
    {
        $a = DateInput::parse('2026-03-01');
        $b = DateInput::parse('2026-03-11');

        $this->assertSame(10.0, DateInput::daysBetween($a, $b));
        $this->assertSame(-10.0, DateInput::daysBetween($b, $a));
        $this->assertSame(0.0, DateInput::daysBetween($a, $a));
    }

    public function testDaysBetweenIgnoresServerTimezoneAcrossDst(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('Europe/Madrid');
        try {
            $birth = DateInput::parse('1972-09-19');
            // 2026-03-30 follows the spring DST change in Europe/Madrid.
            $this->assertSame(19550.0, DateInput::daysBetween($birth, DateInput::parse('2026-03-30')));
            // 2026-10-24 precedes the autumn change; still an exact day count.
            $this->assertSame(19758.0, DateInput::daysBetween($birth, DateInput::parse('2026-10-24')));
        } finally {
            date_default_timezone_set($previous);
        }
    }

    public function testDaysBetweenUsesCalendarDateOfNonUtcInputs(): void
    {
        $madrid = new DateTimeImmutable('2026-03-30 00:00:00', new DateTimeZone('Europe/Madrid'));
        $from   = DateInput::parse('2026-03-28');

        $this->assertSame(2.0, DateInput::daysBetween($from, $madrid));
    }
}
