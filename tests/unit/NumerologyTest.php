<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use Biorrhythms\Numerology;
use PHPUnit\Framework\TestCase;

class NumerologyTest extends TestCase
{
    public function testReduceKeepsMastersWhenAsked(): void
    {
        $this->assertSame(11, Numerology::reduce(29, true));
        $this->assertSame(2, Numerology::reduce(29, false));
        $this->assertSame(22, Numerology::reduce(22, true));
        $this->assertSame(1, Numerology::reduce(1972, true));
        $this->assertSame(9, Numerology::reduce(9, true));
        $this->assertSame(0, Numerology::reduce(0, true));
    }

    public function testLifePath(): void
    {
        $this->assertSame(11, Numerology::lifePath(DateInput::parse('1972-09-19')));
        $this->assertSame(3, Numerology::lifePath(DateInput::parse('1990-01-01')));
        $this->assertSame(6, Numerology::lifePath(DateInput::parse('2000-02-29')));
    }

    public function testPersonalCycles(): void
    {
        $birth = DateInput::parse('1972-09-19');

        $personal = Numerology::personal($birth, DateInput::parse('2026-10-24'));

        $this->assertSame(2, $personal['year']);
        $this->assertSame(3, $personal['month']);
        $this->assertSame(9, $personal['day']);
    }

    public function testPersonalCyclesStayBetweenOneAndNine(): void
    {
        $birth = DateInput::parse('1985-11-22');
        $date  = DateInput::parse('2026-01-01');
        for ($i = 0; $i < 800; $i++) {
            $personal = Numerology::personal($birth, $date->modify("+{$i} day"));
            foreach ($personal as $value) {
                $this->assertGreaterThanOrEqual(1, $value);
                $this->assertLessThanOrEqual(9, $value);
            }
        }
    }

    public function testMeaningsCoverEveryPossibleNumber(): void
    {
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 22, 33] as $number) {
            $meaning = Numerology::meaning($number);
            $this->assertNotSame('', $meaning['name']);
            $this->assertNotSame('', $meaning['keywords']);
        }
    }
}
