<?php

declare(strict_types=1);

use Biorrhythms\ApiV1;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testProfileExposesPhaseConflictAndComposites(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1972-09-19', 'focus' => '2026-06-26'], $this->now);
        $profile = $body['profile'];

        $this->assertSame(['physical', 'emotional', 'intellectual'], array_keys($profile['phases']));
        $this->assertSame(23, $profile['phases']['physical']['period']);
        $this->assertContains($profile['phases']['emotional']['state'], ['peak', 'valley', 'rising', 'falling']);
        $this->assertIsBool($profile['conflict']);
        $this->assertIsBool($profile['all_negative']);
        $this->assertGreaterThanOrEqual(0.0, $profile['spread']);
        $this->assertSame(['passion', 'wisdom', 'mastery'], array_keys($profile['composites']));
        $this->assertContains($profile['strongest']['polarity'], ['high', 'low']);
        $this->assertSame($profile['key'], 'emotional');
    }

    public function testStrongestReflectsMagnitudeNotSign(): void
    {
        // On 2026-06-26 physical is -0.89, the largest magnitude, while emotional is the highest value.
        [, $body] = ApiV1::handle(['birth' => '1972-09-19', 'focus' => '2026-06-26'], $this->now);

        $this->assertSame('physical', $body['profile']['strongest']['key']);
        $this->assertSame('low', $body['profile']['strongest']['polarity']);
        $this->assertTrue($body['profile']['conflict']);
    }

    public function testCriticalDaysCoverFocusToHorizon(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1990-01-01', 'focus' => '1990-01-01'], $this->now);

        $this->assertSame('1990-01-01', $body['critical_days'][0]['date']);
        $this->assertSame('triple', $body['critical_days'][0]['kind']);
        $this->assertLessThanOrEqual('1990-02-15', end($body['critical_days'])['date']);
    }

    public function testExtremeDaysOnlyWhenRequested(): void
    {
        [, $without] = ApiV1::handle(['birth' => '1990-01-01'], $this->now);
        [$status, $with] = ApiV1::handle(['birth' => '1990-01-01', 'extreme' => '0.8'], $this->now);

        $this->assertArrayNotHasKey('extreme_days', $without);
        $this->assertSame(200, $status);
        $this->assertSame(0.8, $with['extreme_days']['threshold']);
        $this->assertNotEmpty($with['extreme_days']['days']);
    }

    #[DataProvider('badExtreme')]
    public function testInvalidExtremeIs400(mixed $value): void
    {
        [$status, $body] = ApiV1::handle(['birth' => '1990-01-01', 'extreme' => $value], $this->now);

        $this->assertSame(400, $status);
        $this->assertSame('extreme', $body['error']['field']);
    }

    public static function badExtreme(): array
    {
        return [['abc'], ['0.4'], ['1'], ['-0.9'], [['0.9']]];
    }

    public function testCalendarContextIsPresent(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1972-09-19', 'focus' => '2026-10-24'], $this->now);
        $ctx = $body['calendar_context'];

        $this->assertSame('Virgo', $ctx['sun_sign']['name']);
        $this->assertSame(11, $ctx['life_path']['number']);
        $this->assertSame('Intuición', $ctx['life_path']['name']);
        $this->assertSame(9, $ctx['personal']['day']);
        $this->assertSame('Cierre', $ctx['personal']['day_name']);
        $this->assertArrayHasKey('name', $ctx['moon']);
        $this->assertArrayHasKey('name', $ctx['birth_moon']);
    }

    public function testRitualIsTheDailyVariantWithPersonalDay(): void
    {
        [, $body] = ApiV1::handle(['birth' => '1972-09-19', 'focus' => '2026-10-24'], $this->now);

        $this->assertContains('Día personal 9', $body['ritual']['tags']);
    }

    public function testCompatibilityExtrasWithPresetWeights(): void
    {
        [, $body] = ApiV1::handle(
            ['birth' => '1972-09-19', 'focus' => '2026-10-24', 'partner_birth' => '1971-05-30', 'preset' => 'work'],
            $this->now,
        );
        $c = $body['compatibility'];

        $this->assertSame('work', $c['preset']);
        $this->assertSame(['physical' => 0.25, 'emotional' => 0.20, 'intellectual' => 0.55], $c['weights']);
        $this->assertGreaterThanOrEqual(0.0, $c['weighted_score']);
        $this->assertLessThanOrEqual(1.0, $c['weighted_score']);
        $this->assertSame(['physical', 'emotional', 'intellectual'], array_keys($c['trend_agreement']));
        $this->assertCount(91, $c['sync_map']);
        $this->assertSame(['date', 'score', 'weighted_score'], array_keys($c['sync_map'][0]));
        $this->assertSame('2026-10-24', $c['sync_map'][45]['date']);
    }
}
