<?php

declare(strict_types=1);

use Biorrhythms\Ritual;
use PHPUnit\Framework\TestCase;

/**
 * Locks the average thresholds of the ritual (0.35 / 0.1 / -0.15). They are not symmetric around zero:
 * the neutral band is [-0.15, 0.1). The web JS (ritualForPoint) uses the same cut-offs.
 */
class RitualThresholdsTest extends TestCase
{
    private function badge(float $average): string
    {
        return Ritual::generate($average, 'physical', 'Lun 1 Ene', 'Mar 2 Ene')['badge'];
    }

    public function testThresholdBoundaries(): void
    {
        $this->assertSame('Ventana de empuje', $this->badge(0.35));
        $this->assertSame('Ritmo estable', $this->badge(0.3499));
        $this->assertSame('Ritmo estable', $this->badge(0.1));
        $this->assertSame('Ventana neutra', $this->badge(0.0999));
        $this->assertSame('Ventana neutra', $this->badge(-0.15));
        $this->assertSame('Ventana de recuperación', $this->badge(-0.1501));
    }
}
