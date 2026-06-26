<?php

declare(strict_types=1);

use Biorrhythms\Forecast;
use PHPUnit\Framework\TestCase;

class ForecastTest extends TestCase
{
    private function point(float $p, float $e, float $i): array
    {
        return ['physical' => $p, 'emotional' => $e, 'intellectual' => $i, 'date' => '2026-01-01', 'label' => 'Test'];
    }

    public function testAverageOfEqualValues(): void
    {
        $this->assertEqualsWithDelta(0.5, Forecast::average($this->point(0.5, 0.5, 0.5)), 1e-12);
    }

    public function testAverageOfMixedValues(): void
    {
        $this->assertEqualsWithDelta(0.0, Forecast::average($this->point(1.0, 0.0, -1.0)), 1e-12);
    }

    public function testScoreWindowAddsScoreKey(): void
    {
        $points = [$this->point(0.6, 0.4, 0.2)];
        $scored = Forecast::scoreWindow($points);
        $this->assertArrayHasKey('score', $scored[0]);
        $this->assertEqualsWithDelta(0.4, $scored[0]['score'], 1e-12);
    }

    public function testBestPicksHighestScore(): void
    {
        $points = Forecast::scoreWindow([
            $this->point(0.1, 0.1, 0.1),
            $this->point(0.9, 0.9, 0.9),
            $this->point(0.5, 0.5, 0.5),
        ]);
        $best = Forecast::best($points);
        $this->assertEqualsWithDelta(0.9, $best['score'], 1e-12);
    }

    public function testWorstPicksLowestScore(): void
    {
        $points = Forecast::scoreWindow([
            $this->point(0.1, 0.1, 0.1),
            $this->point(0.9, 0.9, 0.9),
            $this->point(-0.8, -0.8, -0.8),
        ]);
        $worst = Forecast::worst($points);
        $this->assertEqualsWithDelta(-0.8, $worst['score'], 1e-12);
    }

    public function testDominantKeyPhysical(): void
    {
        $this->assertSame('physical', Forecast::dominantKey($this->point(0.9, 0.5, 0.3)));
    }

    public function testDominantKeyEmotional(): void
    {
        $this->assertSame('emotional', Forecast::dominantKey($this->point(0.3, 0.9, 0.5)));
    }

    public function testDominantKeyIntellectual(): void
    {
        $this->assertSame('intellectual', Forecast::dominantKey($this->point(0.3, 0.5, 0.9)));
    }
}
