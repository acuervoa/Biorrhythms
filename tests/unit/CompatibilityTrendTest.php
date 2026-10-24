<?php

declare(strict_types=1);

use Biorrhythms\Compatibility;
use PHPUnit\Framework\TestCase;

class CompatibilityTrendTest extends TestCase
{
    public function testPresetWeightsMatchTheWebAndSumToOne(): void
    {
        $this->assertSame(['physical' => 0.20, 'emotional' => 0.50, 'intellectual' => 0.30], Compatibility::weights('pair'));
        $this->assertSame(['physical' => 0.25, 'emotional' => 0.45, 'intellectual' => 0.30], Compatibility::weights('friend'));
        $this->assertSame(['physical' => 0.25, 'emotional' => 0.20, 'intellectual' => 0.55], Compatibility::weights('work'));

        foreach (['pair', 'friend', 'work', 'custom'] as $preset) {
            $this->assertEqualsWithDelta(1.0, array_sum(Compatibility::weights($preset)), 1e-12, $preset);
        }
    }

    public function testCustomIsEqualWeights(): void
    {
        foreach (Compatibility::weights('custom') as $weight) {
            $this->assertEqualsWithDelta(1 / 3, $weight, 1e-12);
        }
    }

    public function testWeightedScoreEqualsPointScoreForCustom(): void
    {
        $a = ['physical' => 0.8, 'emotional' => -0.2, 'intellectual' => 0.1];
        $b = ['physical' => 0.5, 'emotional' => 0.4, 'intellectual' => -0.3];

        $this->assertEqualsWithDelta(Compatibility::pointScore($a, $b), Compatibility::weightedScore($a, $b, 'custom'), 1e-12);
    }

    public function testWeightedScoreFavoursTheWeightedRhythm(): void
    {
        // Same physical, opposite intellectual: "work" (intellectual 0.55) must score lower than "pair".
        $a = ['physical' => 1.0, 'emotional' => 1.0, 'intellectual' => 1.0];
        $b = ['physical' => 1.0, 'emotional' => 1.0, 'intellectual' => -1.0];

        $this->assertEqualsWithDelta(0.70, Compatibility::weightedScore($a, $b, 'pair'), 1e-12);
        $this->assertEqualsWithDelta(0.45, Compatibility::weightedScore($a, $b, 'work'), 1e-12);
    }

    public function testUnknownPresetFallsBackToCustom(): void
    {
        $this->assertEquals(Compatibility::weights('custom'), Compatibility::weights('nope'));
    }

    public function testTrendAgreementPerRhythm(): void
    {
        $up   = ['physical' => 0.5, 'emotional' => -0.2, 'intellectual' => 0.1];
        $down = ['physical' => 0.4, 'emotional' => -0.1, 'intellectual' => -0.1];

        $this->assertSame(
            ['physical' => true, 'emotional' => true, 'intellectual' => false],
            Compatibility::trendAgreement($up, $down),
        );
    }
}
