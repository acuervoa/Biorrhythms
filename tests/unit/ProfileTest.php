<?php

declare(strict_types=1);

use Biorrhythms\Profile;
use PHPUnit\Framework\TestCase;

class ProfileTest extends TestCase
{
    private function point(float $p, float $e, float $i): array
    {
        return ['physical' => $p, 'emotional' => $e, 'intellectual' => $i];
    }

    public function testKeyFollowsHighestRhythm(): void
    {
        $this->assertSame('physical', Profile::key($this->point(0.9, 0.1, 0.2)));
        $this->assertSame('emotional', Profile::key($this->point(0.1, 0.9, 0.2)));
        $this->assertSame('intellectual', Profile::key($this->point(0.1, 0.2, 0.9)));
    }

    public function testTiesResolveInPhysicalEmotionalIntellectualOrder(): void
    {
        $this->assertSame('physical', Profile::key($this->point(0.5, 0.5, 0.5)));
        $this->assertSame('emotional', Profile::key($this->point(0.1, 0.5, 0.5)));
    }

    public function testLegacyEnglishLabelsAreUnchanged(): void
    {
        $this->assertSame('Physical peak', Profile::labelEn($this->point(0.9, 0.1, 0.2)));
        $this->assertSame('Emotional peak', Profile::labelEn($this->point(0.1, 0.9, 0.2)));
        $this->assertSame('Mental peak', Profile::labelEn($this->point(0.1, 0.2, 0.9)));
    }

    public function testSpanishLabels(): void
    {
        $this->assertSame('Pico físico', Profile::labelEs($this->point(0.9, 0.1, 0.2)));
        $this->assertSame('Pico emocional', Profile::labelEs($this->point(0.1, 0.9, 0.2)));
        $this->assertSame('Pico mental', Profile::labelEs($this->point(0.1, 0.2, 0.9)));
    }

    public function testAllNegativeStillPicksLeastNegative(): void
    {
        // Documents current behaviour; revisited in phase B (B4).
        $this->assertSame('emotional', Profile::key($this->point(-0.9, -0.1, -0.5)));
    }
}
