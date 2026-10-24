<?php

declare(strict_types=1);

use Biorrhythms\Biorrhythms;
use Biorrhythms\DailyReading;
use Biorrhythms\DateInput;
use Biorrhythms\Timeline;
use PHPUnit\Framework\TestCase;

class DailyReadingTest extends TestCase
{
    private function reading(string $birth, string $focus): array
    {
        $b = DateInput::parse($birth);
        $f = DateInput::parse($focus);

        return DailyReading::forDate($b, $f, Timeline::window(new Biorrhythms(), $b, $f, 6));
    }

    public function testBuildsRitualPhasesAndPersonalDay(): void
    {
        $reading = $this->reading('1972-09-19', '2026-10-24');

        $this->assertSame(['badge', 'focus', 'why', 'lines', 'tags', 'note'], array_keys($reading['ritual']));
        $this->assertSame(['physical', 'emotional', 'intellectual'], array_keys($reading['phases']));
        $this->assertSame(9, $reading['personal']['day']);
        $this->assertContains('Día personal 9', $reading['ritual']['tags']);
    }

    public function testForecastStartsAtTheGivenDate(): void
    {
        // The window is centred on the focus; best/worst must come from the 7 days from the focus on, not the past.
        $reading = $this->reading('1972-09-19', '2026-10-24');

        $allowed = [];
        $day     = DateInput::parse('2026-10-24');
        for ($i = 0; $i < 7; $i++) {
            $allowed[] = \Biorrhythms\DateFormatter::short($day->modify("+{$i} day"));
        }

        $this->assertSame(7, $reading['forecast_days']);
        $this->assertContains($reading['ritual']['tags'][1], $allowed);
        $this->assertContains($reading['ritual']['tags'][2], $allowed);
    }

    public function testCriticalDayIsReported(): void
    {
        $reading = $this->reading('1990-01-01', '1990-01-01');

        $this->assertCount(3, $reading['critical']);
        $this->assertStringContainsString('los tres ritmos', implode(' ', $reading['ritual']['lines']));
    }

    public function testSameInputsGiveTheSameReading(): void
    {
        $this->assertSame($this->reading('1972-09-19', '2026-10-24'), $this->reading('1972-09-19', '2026-10-24'));
    }

    public function testNextDayRotatesSomeText(): void
    {
        $a = $this->reading('1972-09-19', '2026-10-24')['ritual']['lines'];
        $b = $this->reading('1972-09-19', '2026-10-25')['ritual']['lines'];

        $this->assertNotSame($a, $b);
    }
}
