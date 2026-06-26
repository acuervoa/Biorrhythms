<?php

declare(strict_types=1);

use Biorrhythms\Ritual;
use PHPUnit\Framework\TestCase;

class RitualTest extends TestCase
{
    private function generate(float $avg, string $dominant): array
    {
        return Ritual::generate($avg, $dominant, 'Lun 1 Ene', 'Vie 5 Ene');
    }

    // --- Structure ---

    public function testResultHasRequiredKeys(): void
    {
        $r = $this->generate(0.5, 'physical');
        foreach (['badge', 'focus', 'why', 'lines', 'tags', 'note'] as $key) {
            $this->assertArrayHasKey($key, $r);
        }
    }

    public function testLinesHasThreeEntries(): void
    {
        $r = $this->generate(0.5, 'physical');
        $this->assertCount(3, $r['lines']);
    }

    public function testTagsHasThreeEntries(): void
    {
        $r = $this->generate(0.5, 'physical');
        $this->assertCount(3, $r['tags']);
    }

    public function testBestAndWorstLabelAppearInLines(): void
    {
        $r = $this->generate(0.5, 'physical');
        $combined = implode(' ', $r['lines']);
        $this->assertStringContainsString('Lun 1 Ene', $combined);
        $this->assertStringContainsString('Vie 5 Ene', $combined);
    }

    // --- Badges by zone ---

    public function testHighAveragePhysicalIsEmpuje(): void
    {
        $this->assertSame('Ventana de empuje', $this->generate(0.5, 'physical')['badge']);
    }

    public function testHighAverageEmotionalIsSocial(): void
    {
        $this->assertSame('Ventana social', $this->generate(0.5, 'emotional')['badge']);
    }

    public function testHighAverageIntellectualIsMental(): void
    {
        $this->assertSame('Ventana mental', $this->generate(0.5, 'intellectual')['badge']);
    }

    public function testMidAveragePhysicalIsEstable(): void
    {
        $this->assertSame('Ritmo estable', $this->generate(0.2, 'physical')['badge']);
    }

    public function testMidAverageEmotionalIsSensible(): void
    {
        $this->assertSame('Ritmo sensible', $this->generate(0.2, 'emotional')['badge']);
    }

    public function testMidAverageIntellectualIsAnalitico(): void
    {
        $this->assertSame('Ritmo analítico', $this->generate(0.2, 'intellectual')['badge']);
    }

    public function testNeutralAverageIsNeutra(): void
    {
        $this->assertSame('Ventana neutra', $this->generate(0.0, 'physical')['badge']);
    }

    public function testLowAverageIsRecuperacion(): void
    {
        $this->assertSame('Ventana de recuperación', $this->generate(-0.5, 'physical')['badge']);
    }

    // --- Boundary thresholds ---

    public function testExactly035IsHighZone(): void
    {
        $this->assertSame('Ventana de empuje', $this->generate(0.35, 'physical')['badge']);
    }

    public function testJustBelow035IsMidZone(): void
    {
        $this->assertSame('Ritmo estable', $this->generate(0.34, 'physical')['badge']);
    }

    public function testExactlyMinus015IsNeutral(): void
    {
        $this->assertSame('Ventana neutra', $this->generate(-0.15, 'physical')['badge']);
    }

    public function testJustBelowMinus015IsRecuperacion(): void
    {
        $this->assertSame('Ventana de recuperación', $this->generate(-0.16, 'physical')['badge']);
    }
}
