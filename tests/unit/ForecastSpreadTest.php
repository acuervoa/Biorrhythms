<?php

declare(strict_types=1);

use Biorrhythms\Forecast;
use PHPUnit\Framework\TestCase;

class ForecastSpreadTest extends TestCase
{
    private function point(float $p, float $e, float $i): array
    {
        return ['physical' => $p, 'emotional' => $e, 'intellectual' => $i];
    }

    public function testSpreadIsMaxMinusMin(): void
    {
        $this->assertEqualsWithDelta(1.8, Forecast::spread($this->point(0.9, 0.0, -0.9)), 1e-12);
        $this->assertEqualsWithDelta(0.0, Forecast::spread($this->point(0.3, 0.3, 0.3)), 1e-12);
    }

    public function testConflictNeedsOneHighAndOneLowRhythm(): void
    {
        // Average is neutral but the rhythms pull opposite ways: the case a mean alone hides.
        $this->assertTrue(Forecast::hasConflict($this->point(0.9, 0.0, -0.9)));
        $this->assertTrue(Forecast::hasConflict($this->point(0.5, 0.5, -0.5)));
        $this->assertFalse(Forecast::hasConflict($this->point(0.9, 0.4, 0.1)));
        $this->assertFalse(Forecast::hasConflict($this->point(-0.9, -0.5, -0.1)));
        $this->assertFalse(Forecast::hasConflict($this->point(0.49, 0.0, -0.49)));
    }

    public function testAllNegative(): void
    {
        $this->assertTrue(Forecast::allNegative($this->point(-0.1, -0.5, -0.9)));
        $this->assertFalse(Forecast::allNegative($this->point(-0.1, 0.0, -0.9)));
    }
}
