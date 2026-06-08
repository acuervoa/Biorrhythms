<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Biorrhythms\Biorrhythms;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BiorrhythmsTest extends TestCase
{
    private Biorrhythms $tester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tester = new Biorrhythms();
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function physicalProvider(): array
    {
        return [
            'calculatePhysicalZero' => [0.0, 0.0],
            'calculatePhysical' => [1.0, 0.2697967711570243],
            'calculatePhysicalNegative' => [-1.0, -0.2697967711570243],
            'calculatePhysicalMidCycle' => [10.0, 0.39840108984624044],
            'calculatePhysicalFullCycle' => [23.0, 0.0],
            'calculatePhysicalDoubleCycle' => [46.0, 0.0],
        ];
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function emotionalProvider(): array
    {
        return [
            'calculateEmotionalZero' => [0.0, 0.0],
            'calculateEmotional' => [1.0, 0.2225209339563144],
            'calculateEmotionalNegative' => [-1.0, -0.2225209339563144],
            'calculateEmotionalMidCycle' => [14.0, 0.0],
            'calculateEmotionalFullCycle' => [28.0, 0.0],
            'calculateEmotionalDoubleCycle' => [56.0, 0.0],
        ];
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    public static function intellectualProvider(): array
    {
        return [
            'calculateIntellectualZero' => [0.0, 0.0],
            'calculateIntellectual' => [1.0, 0.1892512443604102],
            'calculateIntellectualNegative' => [-1.0, -0.1892512443604102],
            'calculateIntellectualMidCycle' => [16.5, 0.0],
            'calculateIntellectualFullCycle' => [33.0, 0.0],
            'calculateIntellectualDoubleCycle' => [66.0, 0.0],
        ];
    }

    #[DataProvider('physicalProvider')]
    public function testCalculatePhysical(float $time, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->tester->calculatePhysical($time), 1e-12);
    }

    #[DataProvider('emotionalProvider')]
    public function testCalculateEmotional(float $time, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->tester->calculateEmotional($time), 1e-12);
    }

    #[DataProvider('intellectualProvider')]
    public function testCalculateIntellectual(float $time, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->tester->calculateIntellectual($time), 1e-12);
    }
}
