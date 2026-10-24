<?php

declare(strict_types=1);

use Biorrhythms\Biorrhythms;
use Biorrhythms\DateInput;
use Biorrhythms\Timeline;
use PHPUnit\Framework\TestCase;

class TimelineTest extends TestCase
{
    public function testWindowHasTwoRadiiPlusFocus(): void
    {
        $window = Timeline::window(new Biorrhythms(), DateInput::parse('1972-09-19'), DateInput::parse('2026-06-26'), 45);

        $this->assertCount(91, $window);
        $this->assertSame(-45, $window[0]['offset']);
        $this->assertSame(0, $window[45]['offset']);
        $this->assertSame(45, $window[90]['offset']);
    }

    public function testCenterPointIsFocusDate(): void
    {
        $window = Timeline::window(new Biorrhythms(), DateInput::parse('1972-09-19'), DateInput::parse('2026-06-26'), 45);

        $this->assertSame('2026-06-26', $window[45]['date']);
        $this->assertSame('Vie 26 Jun', $window[45]['label']);
        $this->assertSame('2026-05-12', $window[0]['date']);
        $this->assertSame('2026-08-10', $window[90]['date']);
    }

    public function testValuesMatchBiorhythmFormula(): void
    {
        $bio    = new Biorrhythms();
        $birth  = DateInput::parse('1972-09-19');
        $window = Timeline::window($bio, $birth, DateInput::parse('2026-06-26'), 3);

        $days = DateInput::daysBetween($birth, DateInput::parse('2026-06-26'));
        $this->assertEqualsWithDelta($bio->calculatePhysical($days), $window[3]['physical'], 1e-12);
        $this->assertEqualsWithDelta($bio->calculateEmotional($days), $window[3]['emotional'], 1e-12);
        $this->assertEqualsWithDelta($bio->calculateIntellectual($days), $window[3]['intellectual'], 1e-12);
    }

    public function testPointAtMatchesWindowCenter(): void
    {
        $bio   = new Biorrhythms();
        $birth = DateInput::parse('1990-01-01');
        $focus = DateInput::parse('2026-10-24');

        $window = Timeline::window($bio, $birth, $focus, 2);
        $values = Timeline::values($bio, $birth, $focus);

        $this->assertSame($window[2]['physical'], $values['physical']);
        $this->assertSame($window[2]['emotional'], $values['emotional']);
        $this->assertSame($window[2]['intellectual'], $values['intellectual']);
    }
}
