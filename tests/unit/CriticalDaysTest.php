<?php

declare(strict_types=1);

use Biorrhythms\CriticalDays;
use Biorrhythms\DateInput;
use Biorrhythms\Rhythm;
use PHPUnit\Framework\TestCase;

class CriticalDaysTest extends TestCase
{
    public function testBirthDayIsCriticalRisingForAllRhythms(): void
    {
        foreach (array_keys(Rhythm::PERIODS) as $key) {
            $this->assertSame('rising', CriticalDays::direction($key, 0), $key);
        }
    }

    public function testHalfPeriodCrossingFallsOnTheRoundedDay(): void
    {
        // Physical crosses zero at t = 11.5; the window [11.5, 12.5) belongs to day 12.
        $this->assertNull(CriticalDays::direction('physical', 11));
        $this->assertSame('falling', CriticalDays::direction('physical', 12));
        $this->assertNull(CriticalDays::direction('physical', 13));
        $this->assertSame('rising', CriticalDays::direction('physical', 23));
    }

    public function testEmotionalCrossingsAreIntegerDays(): void
    {
        $this->assertSame('falling', CriticalDays::direction('emotional', 14));
        $this->assertSame('rising', CriticalDays::direction('emotional', 28));
        $this->assertNull(CriticalDays::direction('emotional', 15));
    }

    public function testMatchesIndependentCrossingEnumeration(): void
    {
        foreach (Rhythm::PERIODS as $key => $period) {
            $expected = [];
            for ($k = 0; $k * $period / 2 < 2100; $k++) {
                $expected[(int) floor($k * $period / 2 + 0.5)] = $k % 2 === 0 ? 'rising' : 'falling';
            }

            for ($d = 0; $d < 2000; $d++) {
                $this->assertSame($expected[$d] ?? null, CriticalDays::direction($key, $d), "{$key} day {$d}");
            }
        }
    }

    public function testDirectionHandlesNegativeDays(): void
    {
        // t = -11.5 is a crossing for physical; its window [-12, -11) belongs to day -11 (floor(-11.5 + 0.5)).
        $this->assertSame('falling', CriticalDays::direction('physical', -11));
        $this->assertNull(CriticalDays::direction('physical', -12));
    }

    public function testFindClassifiesTripleCriticalOnBirthDay(): void
    {
        $birth = DateInput::parse('1990-01-01');

        $days = CriticalDays::find($birth, $birth, $birth);

        $this->assertCount(1, $days);
        $this->assertSame('triple', $days[0]['kind']);
        $this->assertSame(3, $days[0]['count']);
        $this->assertSame('1990-01-01', $days[0]['date']);
        $this->assertSame(['physical', 'emotional', 'intellectual'], array_column($days[0]['rhythms'], 'key'));
    }

    public function testFindReturnsSortedInclusiveRangeWithLabels(): void
    {
        $birth = DateInput::parse('1990-01-01');
        $from  = DateInput::parse('2026-10-24');
        $to    = DateInput::parse('2026-12-31');

        $days = CriticalDays::find($birth, $from, $to);

        $this->assertNotEmpty($days);
        $dates = array_column($days, 'date');
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
        $this->assertGreaterThanOrEqual('2026-10-24', $dates[0]);
        $this->assertLessThanOrEqual('2026-12-31', end($dates));
        $this->assertMatchesRegularExpression('/^[A-Za-zÁ-ú]{3} \d{1,2} [A-Za-z]{3} 2026$/u', $days[0]['label']);
    }

    public function testKindMatchesCount(): void
    {
        $birth = DateInput::parse('1990-01-01');
        $days  = CriticalDays::find($birth, $birth, DateInput::parse('2030-01-01'));

        $map = [1 => 'single', 2 => 'double', 3 => 'triple'];
        foreach ($days as $day) {
            $this->assertSame($map[$day['count']], $day['kind']);
            $this->assertCount($day['count'], $day['rhythms']);
        }
        $this->assertContains('double', array_column($days, 'kind'));
    }

    public function testFindWithMinCountFilters(): void
    {
        $birth = DateInput::parse('1990-01-01');
        $days  = CriticalDays::find($birth, $birth, DateInput::parse('2030-01-01'), 2);

        $this->assertNotEmpty($days);
        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(2, $day['count']);
        }
    }

    public function testFindWithToBeforeFromIsEmpty(): void
    {
        $birth = DateInput::parse('1990-01-01');

        $this->assertSame([], CriticalDays::find($birth, DateInput::parse('2026-02-01'), DateInput::parse('2026-01-01')));
    }
}
