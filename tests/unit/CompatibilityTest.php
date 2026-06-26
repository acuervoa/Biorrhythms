<?php

declare(strict_types=1);

use Biorrhythms\Compatibility;
use PHPUnit\Framework\TestCase;

class CompatibilityTest extends TestCase
{
    // --- Compatibility::score ---

    public function testScoreIdenticalValuesIsOne(): void
    {
        $this->assertEqualsWithDelta(1.0, Compatibility::score(1.0, 1.0), 1e-12);
        $this->assertEqualsWithDelta(1.0, Compatibility::score(0.0, 0.0), 1e-12);
        $this->assertEqualsWithDelta(1.0, Compatibility::score(-1.0, -1.0), 1e-12);
    }

    public function testScoreOppositeExtremesIsZero(): void
    {
        $this->assertEqualsWithDelta(0.0, Compatibility::score(1.0, -1.0), 1e-12);
        $this->assertEqualsWithDelta(0.0, Compatibility::score(-1.0, 1.0), 1e-12);
    }

    public function testScoreSymmetric(): void
    {
        $this->assertEqualsWithDelta(
            Compatibility::score(0.3, 0.7),
            Compatibility::score(0.7, 0.3),
            1e-12,
        );
    }

    public function testScoreMidpointValues(): void
    {
        // |0.5 - (-0.5)| / 2 = 0.5  →  1 - 0.5 = 0.5
        $this->assertEqualsWithDelta(0.5, Compatibility::score(0.5, -0.5), 1e-12);
    }

    public function testScoreNeverBelowZero(): void
    {
        // Extreme divergence must clamp to 0, not go negative
        $this->assertGreaterThanOrEqual(0.0, Compatibility::score(1.0, -1.0));
    }

    public function testScoreInRange(): void
    {
        foreach ([[-1.0, 0.5], [0.0, 0.8], [0.3, -0.9]] as [$a, $b]) {
            $s = Compatibility::score($a, $b);
            $this->assertGreaterThanOrEqual(0.0, $s);
            $this->assertLessThanOrEqual(1.0, $s);
        }
    }

    // --- Compatibility::pointScore ---

    public function testPointScoreIdenticalPointsIsOne(): void
    {
        $point = ['physical' => 0.5, 'emotional' => -0.3, 'intellectual' => 0.8];
        $this->assertEqualsWithDelta(1.0, Compatibility::pointScore($point, $point), 1e-12);
    }

    public function testPointScoreOppositePointsIsZero(): void
    {
        $a = ['physical' => 1.0,  'emotional' => 1.0,  'intellectual' => 1.0];
        $b = ['physical' => -1.0, 'emotional' => -1.0, 'intellectual' => -1.0];
        $this->assertEqualsWithDelta(0.0, Compatibility::pointScore($a, $b), 1e-12);
    }

    public function testPointScoreIsAverageOfThreeRhythms(): void
    {
        $a = ['physical' => 1.0, 'emotional' => 0.0, 'intellectual' => -1.0];
        $b = ['physical' => 1.0, 'emotional' => 0.0, 'intellectual' => -1.0];
        // All identical → all scores = 1.0 → average = 1.0
        $this->assertEqualsWithDelta(1.0, Compatibility::pointScore($a, $b), 1e-12);
    }

    public function testPointScoreInRange(): void
    {
        $a = ['physical' => 0.7,  'emotional' => -0.4, 'intellectual' => 0.1];
        $b = ['physical' => -0.3, 'emotional' => 0.9,  'intellectual' => 0.6];
        $s = Compatibility::pointScore($a, $b);
        $this->assertGreaterThanOrEqual(0.0, $s);
        $this->assertLessThanOrEqual(1.0, $s);
    }
}
