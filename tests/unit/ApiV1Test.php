<?php

declare(strict_types=1);

use Biorrhythms\ApiV1;
use PHPUnit\Framework\TestCase;

class ApiV1Test extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-10-24 23:30:00', new DateTimeZone('UTC'));
    }

    public function testMissingBirthIs400(): void
    {
        [$status, $body] = ApiV1::handle([], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('missing_parameter', $body['error']['code']);
        $this->assertSame('birth', $body['error']['field']);
    }

    public function testInvalidBirthIs400(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '2026-02-31'], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('invalid_date', $body['error']['code']);
        $this->assertSame('birth', $body['error']['field']);
    }

    public function testArrayBirthIs400(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => ['1990-01-01']], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('invalid_date', $body['error']['code']);
    }

    public function testInvalidFocusIs400(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '1990-01-01', 'focus' => 'mañana'], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('focus', $body['error']['field']);
    }

    public function testInvalidPartnerBirthIs400(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '1990-01-01', 'partner_birth' => '1991-13-01'], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('partner_birth', $body['error']['field']);
    }

    public function testInvalidPresetIs400(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '1990-01-01', 'preset' => 'nope'], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('invalid_value', $body['error']['code']);
        $this->assertSame('preset', $body['error']['field']);
    }

    public function testMinimalRequestDefaultsFocusToNowAndOmitsCompatibility(): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '1990-01-01'], $this->now);

        $this->assertSame(200, $status);
        $this->assertSame('2026-10-24', $body['meta']['focus']);
        $this->assertSame('1990-01-01', $body['meta']['birth']);
        $this->assertNull($body['meta']['partner_birth']);
        $this->assertSame('custom', $body['meta']['preset']);
        $this->assertNull($body['compatibility']);
        $this->assertCount(91, $body['timeline']['window']);
        $this->assertCount(7, $body['forecast']['days']);
    }

    public function testProfileHasStableKeyAndSpanishLabel(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1972-09-19', 'focus' => '2026-06-26'], $this->now);

        $this->assertSame('emotional', $body['profile']['key']);
        $this->assertSame('Pico emocional', $body['profile']['label']);
    }

    public function testPartnerEnablesCompatibility(): void
    {
        [$status, $body] = ApiV1::handle(
            ['birth' => '1972-09-19', 'focus' => '2026-06-26', 'partner_birth' => '1971-05-30'],
            $this->now,
        );

        $this->assertSame(200, $status);
        $this->assertSame('1971-05-30', $body['meta']['partner_birth']);
        $this->assertGreaterThanOrEqual(0.0, $body['compatibility']['score']);
        $this->assertLessThanOrEqual(1.0, $body['compatibility']['score']);
        $this->assertCount(7, $body['compatibility']['forecast']);
    }

    public function testPresetIsHonoredInMetaAndLinks(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1990-01-01', 'preset' => 'pair'], $this->now);

        $this->assertSame('pair', $body['meta']['preset']);
        $this->assertStringContainsString('preset=pair', $body['links']['app']);
        $this->assertStringContainsString('preset=pair', $body['links']['widget']);
    }

    public function testCustomPresetIsNotAddedToLinks(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1990-01-01'], $this->now);

        $this->assertStringNotContainsString('preset=', $body['links']['app']);
    }

    public function testUnknownParametersAreIgnored(): void
    {
        [$status] = ApiV1::handle(['birth' => '1990-01-01', 'pretty' => '1', 'foo' => 'bar'], $this->now);

        $this->assertSame(200, $status);
    }
}
