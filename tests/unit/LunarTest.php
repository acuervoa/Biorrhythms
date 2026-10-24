<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use Biorrhythms\Lunar;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LunarTest extends TestCase
{
    #[DataProvider('knownDays')]
    public function testPhaseNearKnownLunations(string $date, string $phase): void
    {
        $this->assertSame($phase, Lunar::phase(DateInput::parse($date))['name']);
    }

    /** Reference lunations (UTC): new 2024-01-11, first quarter 01-17, full 01-25, last quarter 02-02. */
    public static function knownDays(): array
    {
        return [
            ['2024-01-11', 'Luna nueva'],
            ['2024-01-18', 'Cuarto creciente'],
            ['2024-01-25', 'Luna llena'],
            ['2024-02-03', 'Cuarto menguante'],
            ['2000-01-06', 'Luna nueva'],
        ];
    }

    public function testIlluminationIsLowAtNewAndHighAtFull(): void
    {
        $new  = Lunar::phase(DateInput::parse('2024-01-11'));
        $full = Lunar::phase(DateInput::parse('2024-01-25'));

        $this->assertLessThan(0.1, $new['illumination']);
        $this->assertGreaterThan(0.9, $full['illumination']);
    }

    public function testAgeStaysWithinSynodicMonth(): void
    {
        $date = DateInput::parse('1990-01-01');
        for ($i = 0; $i < 400; $i++) {
            $phase = Lunar::phase($date->modify("+{$i} day"));
            $this->assertGreaterThanOrEqual(0.0, $phase['age_days']);
            $this->assertLessThan(Lunar::SYNODIC_DAYS, $phase['age_days']);
            $this->assertGreaterThanOrEqual(0.0, $phase['illumination']);
            $this->assertLessThanOrEqual(1.0, $phase['illumination']);
        }
    }

    public function testWorksBeforeTheReferenceEpoch(): void
    {
        $phase = Lunar::phase(DateInput::parse('1950-06-15'));

        $this->assertGreaterThanOrEqual(0.0, $phase['age_days']);
        $this->assertLessThan(Lunar::SYNODIC_DAYS, $phase['age_days']);
    }

    public function testAllEightPhasesAppearInOneMonth(): void
    {
        $names = [];
        $date  = DateInput::parse('2024-01-01');
        for ($i = 0; $i < 30; $i++) {
            $names[Lunar::phase($date->modify("+{$i} day"))['name']] = true;
        }

        $this->assertCount(8, $names);
    }
}
