<?php

declare(strict_types=1);

use Biorrhythms\Biorrhythms;
use Biorrhythms\DateInput;
use Biorrhythms\ExtremeDays;
use PHPUnit\Framework\TestCase;

class ExtremeDaysThresholdTest extends TestCase
{
    public function testLowerThresholdFindsSupersetOfDefault(): void
    {
        $bio   = new Biorrhythms();
        $birth = DateInput::parse('1972-09-19');
        $from  = DateInput::parse('2026-10-24');

        $strict = ExtremeDays::find($bio, $birth, $from);
        $loose  = ExtremeDays::find($bio, $birth, $from, 0.8);

        $this->assertGreaterThan(count($strict), count($loose));
        $this->assertSame([], array_diff(array_column($strict, 'date'), array_column($loose, 'date')));
        foreach ($loose as $day) {
            $this->assertGreaterThanOrEqual(80.0, abs($day['avg']));
        }
    }

    public function testResultDoesNotDependOnServerTimezone(): void
    {
        $bio   = new Biorrhythms();
        $birth = DateInput::parse('1972-09-19');
        $from  = DateInput::parse('2026-03-30');

        $utc = ExtremeDays::find($bio, $birth, $from, 0.85);

        $previous = date_default_timezone_get();
        date_default_timezone_set('Europe/Madrid');
        try {
            $madrid = ExtremeDays::find($bio, $birth, $from, 0.85);
        } finally {
            date_default_timezone_set($previous);
        }

        $this->assertSame($utc, $madrid);
    }
}
