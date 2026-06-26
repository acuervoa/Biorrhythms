<?php

declare(strict_types=1);

use Biorrhythms\DateFormatter;
use PHPUnit\Framework\TestCase;

class DateFormatterTest extends TestCase
{
    public function testShortFormatIsSpanish(): void
    {
        $date = new DateTimeImmutable('2026-06-26'); // Viernes
        $this->assertSame('Vie 26 Jun', DateFormatter::short($date));
    }

    public function testLongFormatIncludesYear(): void
    {
        $date = new DateTimeImmutable('2029-11-05'); // Lunes
        $this->assertSame('Lun 5 Nov 2029', DateFormatter::long($date));
    }

    public function testAllDayNamesTranslated(): void
    {
        $expected = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        // 2026-06-22 es lunes
        foreach ($expected as $i => $name) {
            $date = new DateTimeImmutable('2026-06-2' . (2 + $i));
            $this->assertStringStartsWith($name, DateFormatter::short($date));
        }
    }

    public function testAllMonthNamesTranslated(): void
    {
        $months = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
        ];
        foreach ($months as $num => $name) {
            $date = new DateTimeImmutable("2025-{$num}-01");
            $this->assertStringContainsString($name, DateFormatter::short($date));
        }
    }
}
