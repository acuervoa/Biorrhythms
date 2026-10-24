<?php

declare(strict_types=1);

use Biorrhythms\Rhythm;
use PHPUnit\Framework\TestCase;

class RhythmTest extends TestCase
{
    public function testPeriods(): void
    {
        $this->assertSame(['physical' => 23, 'emotional' => 28, 'intellectual' => 33], Rhythm::PERIODS);
    }

    public function testBirthDayIsDayOneAndRising(): void
    {
        $phase = Rhythm::phase('physical', 0.0);

        $this->assertSame(23, $phase['period']);
        $this->assertSame(1, $phase['day_in_cycle']);
        $this->assertEqualsWithDelta(0.0, $phase['value'], 1e-12);
        $this->assertSame('rising', $phase['state']);
        $this->assertGreaterThan(0.0, $phase['slope']);
    }

    public function testPeakAtQuarterPeriod(): void
    {
        $phase = Rhythm::phase('physical', 23 / 4);

        $this->assertEqualsWithDelta(1.0, $phase['value'], 1e-12);
        $this->assertSame('peak', $phase['state']);
    }

    public function testValleyAtThreeQuartersPeriod(): void
    {
        $phase = Rhythm::phase('emotional', 28 * 3 / 4);

        $this->assertEqualsWithDelta(-1.0, $phase['value'], 1e-12);
        $this->assertSame('valley', $phase['state']);
    }

    public function testFallingAfterPeakAndRisingAfterValley(): void
    {
        $this->assertSame('falling', Rhythm::phase('intellectual', 33 * 0.4)['state']);
        $this->assertSame('rising', Rhythm::phase('intellectual', 33 * 0.9)['state']);
    }

    public function testFallingAtHalfPeriodCrossing(): void
    {
        $phase = Rhythm::phase('physical', 11.5);

        $this->assertEqualsWithDelta(0.0, $phase['value'], 1e-12);
        $this->assertSame('falling', $phase['state']);
        $this->assertLessThan(0.0, $phase['slope']);
    }

    public function testDayInCycleWrapsAndHandlesNegativeDays(): void
    {
        $this->assertSame(23, Rhythm::phase('physical', 22.0)['day_in_cycle']);
        $this->assertSame(1, Rhythm::phase('physical', 23.0)['day_in_cycle']);
        $this->assertSame(23, Rhythm::phase('physical', -1.0)['day_in_cycle']);
    }

    public function testUnknownRhythmThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Rhythm::phase('spiritual', 1.0);
    }
}
