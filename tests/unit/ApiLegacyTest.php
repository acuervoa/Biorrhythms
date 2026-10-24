<?php

declare(strict_types=1);

use Biorrhythms\ApiLegacy;
use PHPUnit\Framework\TestCase;

class ApiLegacyTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-10-24 10:00:00', new DateTimeZone('UTC'));
    }

    public function testLegacyKeepsEnglishLabelAndNoKey(): void
    {
        $body = ApiLegacy::build(['birth' => '1972-09-19', 'focus' => '2026-06-26', 'partner_birth' => '1971-05-30'], $this->now);

        $this->assertSame('Emotional peak', $body['profile']['label']);
        $this->assertArrayNotHasKey('key', $body['profile']);
    }

    public function testLegacyFallsBackToHistoricDefaults(): void
    {
        $body = ApiLegacy::build([], $this->now);

        $this->assertSame('1990-01-01', $body['meta']['birth']);
        $this->assertSame('2026-10-24', $body['meta']['focus']);
        $this->assertSame('1991-01-01', $body['meta']['partner_birth']);
    }

    public function testLegacyFallsBackOnOverflowedDate(): void
    {
        $body = ApiLegacy::build(['birth' => '2026-02-31'], $this->now);

        $this->assertSame('1990-01-01', $body['meta']['birth']);
    }

    public function testLegacyDoesNotCrashOnArrayParameters(): void
    {
        $body = ApiLegacy::build(['birth' => ['x'], 'focus' => ['y']], $this->now);

        $this->assertSame('1990-01-01', $body['meta']['birth']);
    }

    public function testLegacyAlwaysIncludesCompatibilityAndNoPreset(): void
    {
        $body = ApiLegacy::build(['birth' => '1990-01-01', 'preset' => 'pair'], $this->now);

        $this->assertIsArray($body['compatibility']);
        $this->assertArrayNotHasKey('preset', $body['meta']);
        $this->assertStringNotContainsString('preset', $body['links']['app']);
    }
}
