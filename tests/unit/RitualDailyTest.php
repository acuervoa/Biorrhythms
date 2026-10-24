<?php

declare(strict_types=1);

use Biorrhythms\Ritual;
use PHPUnit\Framework\TestCase;

class RitualDailyTest extends TestCase
{
    private function ctx(array $over = []): array
    {
        return $over + [
            'phases'       => [
                'physical'     => ['state' => 'rising'],
                'emotional'    => ['state' => 'rising'],
                'intellectual' => ['state' => 'rising'],
            ],
            'critical'     => [],
            'conflict'     => false,
            'all_negative' => false,
            'personal_day' => null,
            'seed'         => 0,
        ];
    }

    private function daily(float $p, float $e, float $i, array $ctx = []): array
    {
        $point = ['physical' => $p, 'emotional' => $e, 'intellectual' => $i];
        $avg   = ($p + $e + $i) / 3;
        $days  = [$point + ['score' => $avg, 'label' => 'Lun 1 Ene'], $point + ['score' => $avg - 0.1, 'label' => 'Mar 2 Ene']];

        return Ritual::daily($point, $days, $this->ctx($ctx));
    }

    public function testShapeMatchesLegacyGenerate(): void
    {
        $legacy = Ritual::generate(0.5, 'physical', 'Lun 1 Ene', 'Mar 2 Ene');
        $daily  = $this->daily(0.6, 0.5, 0.4);

        $this->assertSame(array_keys($legacy), array_keys($daily));
        $this->assertContains($daily['badge'], ['Ventana de empuje']);
    }

    public function testIsDeterministicForTheSameSeed(): void
    {
        $this->assertSame($this->daily(0.6, 0.5, 0.4, ['seed' => 7]), $this->daily(0.6, 0.5, 0.4, ['seed' => 7]));
    }

    public function testDifferentSeedsRotateTheOpeningAndClosingLines(): void
    {
        $openings = [];
        $closings = [];
        for ($seed = 0; $seed < 6; $seed++) {
            $lines      = $this->daily(0.6, 0.5, 0.4, ['seed' => $seed])['lines'];
            $openings[] = $lines[0];
            $closings[] = end($lines);
        }

        $this->assertGreaterThan(1, count(array_unique($openings)));
        $this->assertGreaterThan(1, count(array_unique($closings)));
    }

    public function testNegativeSeedIsHandled(): void
    {
        $this->assertNotEmpty($this->daily(0.6, 0.5, 0.4, ['seed' => -5])['lines']);
    }

    public function testCriticalDayAddsAWarning(): void
    {
        $lines = $this->daily(0.1, 0.1, 0.1, ['critical' => [['key' => 'physical', 'direction' => 'falling']]])['lines'];

        $this->assertStringContainsString('Físico', implode(' ', $lines));
        $this->assertStringContainsString('cruza cero', implode(' ', $lines));
    }

    public function testTripleCriticalIsStrongerThanSingle(): void
    {
        $critical = [
            ['key' => 'physical', 'direction' => 'rising'],
            ['key' => 'emotional', 'direction' => 'rising'],
            ['key' => 'intellectual', 'direction' => 'rising'],
        ];
        $text = implode(' ', $this->daily(0.0, 0.0, 0.0, ['critical' => $critical])['lines']);

        $this->assertStringContainsString('los tres ritmos', $text);
    }

    public function testConflictNamesHighestAndLowestRhythm(): void
    {
        $text = implode(' ', $this->daily(0.9, 0.0, -0.9, ['conflict' => true])['lines']);

        $this->assertStringContainsString('sentidos opuestos', $text);
        $this->assertStringContainsString('Físico', $text);
        $this->assertStringContainsString('Intelectual', $text);
    }

    public function testTrendLineDependsOnTheDominantRhythmState(): void
    {
        $phases = static fn (string $state): array => [
            'physical'     => ['state' => $state],
            'emotional'    => ['state' => 'rising'],
            'intellectual' => ['state' => 'rising'],
        ];

        $rising  = implode(' ', $this->daily(0.5, 0.1, 0.1, ['phases' => $phases('rising')])['lines']);
        $falling = implode(' ', $this->daily(0.5, 0.1, 0.1, ['phases' => $phases('falling')])['lines']);

        $this->assertStringContainsString('sube', $rising);
        $this->assertStringContainsString('baja', $falling);
    }

    public function testAllNegativeAddsRestLine(): void
    {
        $text = implode(' ', $this->daily(-0.3, -0.5, -0.7, ['all_negative' => true])['lines']);

        $this->assertStringContainsString('por debajo de cero', $text);
    }

    public function testPersonalDayAppearsAsTagAndInNote(): void
    {
        $daily = $this->daily(0.5, 0.5, 0.5, ['personal_day' => 9]);

        $this->assertContains('Día personal 9', $daily['tags']);
        $this->assertStringContainsString('Cierre', $daily['note']);
    }

    public function testLinesAreCappedAtFive(): void
    {
        $critical = [
            ['key' => 'physical', 'direction' => 'rising'],
            ['key' => 'emotional', 'direction' => 'rising'],
        ];
        $lines = $this->daily(0.9, 0.0, -0.9, [
            'critical' => $critical, 'conflict' => true, 'all_negative' => true, 'personal_day' => 4,
        ])['lines'];

        $this->assertLessThanOrEqual(5, count($lines));
        $this->assertGreaterThanOrEqual(3, count($lines));
    }

    public function testNoteCountsTheSteps(): void
    {
        $daily = $this->daily(0.5, 0.5, 0.5);

        $this->assertStringContainsString((string) count($daily['lines']) . ' pasos', $daily['note']);
    }

    public function testLegacyGenerateIsUntouched(): void
    {
        $legacy = Ritual::generate(0.5, 'physical', 'Lun 1 Ene', 'Mar 2 Ene');

        $this->assertSame('Empieza con una acción física y un cierre rápido.', $legacy['lines'][0]);
        $this->assertCount(3, $legacy['lines']);
    }
}
