<?php

declare(strict_types=1);

use Biorrhythms\DateInput;
use Biorrhythms\Zodiac;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ZodiacTest extends TestCase
{
    #[DataProvider('signs')]
    public function testSunSign(string $date, string $expected): void
    {
        $this->assertSame($expected, Zodiac::sunSign(DateInput::parse($date))['name']);
    }

    public static function signs(): array
    {
        return [
            ['1972-09-19', 'Virgo'],
            ['1990-01-01', 'Capricornio'],
            ['2000-01-19', 'Capricornio'],
            ['2000-01-20', 'Acuario'],
            ['2001-02-18', 'Acuario'],
            ['2001-02-19', 'Piscis'],
            ['2000-03-20', 'Piscis'],
            ['2000-03-21', 'Aries'],
            ['2000-04-19', 'Aries'],
            ['2000-04-20', 'Tauro'],
            ['2000-06-20', 'Géminis'],
            ['2000-06-21', 'Cáncer'],
            ['2000-07-22', 'Cáncer'],
            ['2000-07-23', 'Leo'],
            ['2000-08-22', 'Leo'],
            ['2000-08-23', 'Virgo'],
            ['2000-10-22', 'Libra'],
            ['2000-10-23', 'Escorpio'],
            ['2000-11-21', 'Escorpio'],
            ['2000-11-22', 'Sagitario'],
            ['2000-12-21', 'Sagitario'],
            ['2000-12-22', 'Capricornio'],
            ['2000-12-31', 'Capricornio'],
        ];
    }

    public function testElementAndModality(): void
    {
        $sign = Zodiac::sunSign(DateInput::parse('2000-03-21'));

        $this->assertSame('Fuego', $sign['element']);
        $this->assertSame('Cardinal', $sign['modality']);
    }

    public function testCuspIsFlaggedWithinOneDayOfTheBoundary(): void
    {
        $this->assertTrue(Zodiac::sunSign(DateInput::parse('2000-03-21'))['cusp']);
        $this->assertTrue(Zodiac::sunSign(DateInput::parse('2000-03-20'))['cusp']);
        $this->assertFalse(Zodiac::sunSign(DateInput::parse('2000-04-05'))['cusp']);
    }

    public function testEveryDayOfALeapYearHasASign(): void
    {
        $date = DateInput::parse('2024-01-01');
        for ($i = 0; $i < 366; $i++) {
            $this->assertNotSame('', Zodiac::sunSign($date->modify("+{$i} day"))['name']);
        }
    }
}
