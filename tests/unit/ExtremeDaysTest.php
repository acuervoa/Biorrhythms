<?php

declare(strict_types=1);

use Biorrhythms\Biorrhythms;
use Biorrhythms\ExtremeDays;
use PHPUnit\Framework\TestCase;

class ExtremeDaysTest extends TestCase
{
    private Biorrhythms $bio;

    protected function setUp(): void
    {
        $this->bio = new Biorrhythms();
    }

    public function testKnownPeakForBirth19720919(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');
        $days  = ExtremeDays::find($this->bio, $birth, $from);

        $dates = array_column($days, 'date');
        // Known peak ~96.6% computed in previous analysis
        $this->assertContains('2029-11-05', $dates);
    }

    public function testKnownValleyForBirth19720919(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');
        $days  = ExtremeDays::find($this->bio, $birth, $from);

        $dates = array_column($days, 'date');
        // Known valley ~-98.1% computed in previous analysis
        $this->assertContains('2027-05-12', $dates);
    }

    public function testAllReturnedDaysExceedThreshold(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');
        $days  = ExtremeDays::find($this->bio, $birth, $from, 0.95);

        foreach ($days as $day) {
            $this->assertTrue(
                abs($day['avg']) >= 95.0,
                "Expected |avg| >= 95 but got {$day['avg']} on {$day['date']}",
            );
        }
    }

    public function testHigherThresholdReturnsSubset(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');

        $at95 = ExtremeDays::find($this->bio, $birth, $from, 0.95);
        $at99 = ExtremeDays::find($this->bio, $birth, $from, 0.99);

        // Stricter threshold must return fewer or equal days
        $this->assertLessThanOrEqual(count($at95), count($at99));
    }

    public function testNoDaysBeforeFromDate(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');
        $days  = ExtremeDays::find($this->bio, $birth, $from);

        foreach ($days as $day) {
            $this->assertGreaterThanOrEqual(
                $from->format('Y-m-d'),
                $day['date'],
                "Day {$day['date']} is before fromDate {$from->format('Y-m-d')}",
            );
        }
    }

    public function testResultStructure(): void
    {
        $birth = new DateTimeImmutable('1972-09-19');
        $from  = new DateTimeImmutable('2026-06-26');
        $days  = ExtremeDays::find($this->bio, $birth, $from);

        $this->assertNotEmpty($days);
        $first = $days[0];
        $this->assertArrayHasKey('date', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('avg', $first);
        $this->assertArrayHasKey('physical', $first);
        $this->assertArrayHasKey('emotional', $first);
        $this->assertArrayHasKey('intellectual', $first);
    }

    public function testFromDateAtBirthReturnsFullCycle(): void
    {
        // Starting from birth day itself should cover more results
        $birth = new DateTimeImmutable('1972-09-19');
        $days  = ExtremeDays::find($this->bio, $birth, $birth);

        $this->assertGreaterThan(0, count($days));
    }
}
