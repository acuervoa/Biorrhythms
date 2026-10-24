<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use Biorrhythms\MonthCalendar;
use PHPUnit\Framework\TestCase;

class MonthCalendarTest extends TestCase
{
    private function build(string $focus = '2026-10-24'): array
    {
        return MonthCalendar::build(DateInput::parse('1972-09-19'), DateInput::parse($focus));
    }

    public function testLabelAndShape(): void
    {
        $month = $this->build();

        $this->assertSame('Octubre 2026', $month['label']);
        $this->assertSame(2026, $month['year']);
        $this->assertSame(10, $month['month']);
        $this->assertCount(5, $month['weeks']);
        foreach ($month['weeks'] as $week) {
            $this->assertCount(7, $week);
        }
    }

    public function testWeeksStartOnMondayWithPadding(): void
    {
        // 2026-10-01 is a Thursday: Mon, Tue and Wed are padding.
        $first = $this->build()['weeks'][0];

        $this->assertNull($first[0]);
        $this->assertNull($first[1]);
        $this->assertNull($first[2]);
        $this->assertSame('2026-10-01', $first[3]['date']);
    }

    public function testEveryDayOfTheMonthAppearsExactlyOnce(): void
    {
        $dates = [];
        foreach ($this->build()['weeks'] as $week) {
            foreach ($week as $cell) {
                if ($cell !== null) {
                    $dates[] = $cell['date'];
                }
            }
        }

        $this->assertCount(31, $dates);
        $this->assertSame('2026-10-01', $dates[0]);
        $this->assertSame('2026-10-31', $dates[30]);
        $this->assertSame($dates, array_values(array_unique($dates)));
    }

    public function testFocusDayIsMarkedOnce(): void
    {
        $marked = [];
        foreach ($this->build()['weeks'] as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['is_focus']) {
                    $marked[] = $cell['date'];
                }
            }
        }

        $this->assertSame(['2026-10-24'], $marked);
    }

    public function testLevelFollowsRitualThresholds(): void
    {
        foreach ($this->build()['weeks'] as $week) {
            foreach ($week as $cell) {
                if ($cell === null) {
                    continue;
                }
                $expected = $cell['avg'] >= 0.35 ? 'high' : ($cell['avg'] >= -0.15 ? 'mid' : 'low');
                $this->assertSame($expected, $cell['level'], $cell['date']);
            }
        }
    }

    public function testCriticalInfoMatchesCriticalDays(): void
    {
        $critical = [];
        foreach ($this->build()['weeks'] as $week) {
            foreach ($week as $cell) {
                if ($cell !== null && $cell['critical'] > 0) {
                    $critical[$cell['date']] = $cell['critical'];
                }
            }
        }

        $expected = [];
        foreach (\Biorrhythms\CriticalDays::find(DateInput::parse('1972-09-19'), DateInput::parse('2026-10-01'), DateInput::parse('2026-10-31')) as $day) {
            $expected[$day['date']] = $day['count'];
        }

        $this->assertSame($expected, $critical);
    }

    public function testFebruaryOfALeapYear(): void
    {
        $month = MonthCalendar::build(DateInput::parse('1990-01-01'), DateInput::parse('2024-02-10'));
        $days  = 0;
        foreach ($month['weeks'] as $week) {
            $days += count(array_filter($week));
        }

        $this->assertSame(29, $days);
        $this->assertSame('Febrero 2024', $month['label']);
    }
}
