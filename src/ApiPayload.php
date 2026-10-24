<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Pieces shared by the legacy /api/ and the strict /api/v1/ payloads. */
class ApiPayload
{
    public const WINDOW_RADIUS = 45;
    public const PRESETS = ['pair', 'friend', 'work', 'custom'];

    /**
     * @return array{
     *   window: list<array>, selected: array, focus_values: array, forecast: list<array>,
     *   best: array, worst: array, ritual: array
     * }
     */
    public static function individual(\DateTimeImmutable $birth, \DateTimeImmutable $focus): array
    {
        $bio      = new Biorrhythms();
        $window   = Timeline::window($bio, $birth, $focus, self::WINDOW_RADIUS);
        $selected = $window[self::WINDOW_RADIUS];
        $forecast = Forecast::scoreWindow(array_slice($window, self::WINDOW_RADIUS, 7));
        $best     = Forecast::best($forecast);
        $worst    = Forecast::worst($forecast);

        return [
            'window'       => $window,
            'selected'     => $selected,
            'focus_values' => Timeline::values($bio, $birth, $focus),
            'forecast'     => $forecast,
            'best'         => $best,
            'worst'        => $worst,
            'ritual'       => Ritual::generate(
                average:     Forecast::average($selected),
                dominantKey: Forecast::dominantKey($selected),
                bestLabel:   $best['label'],
                worstLabel:  $worst['label'],
            ),
        ];
    }

    /**
     * Compatibility block between two birth dates over the 7-day forecast.
     *
     * @param list<array> $forecastDays Points from the individual forecast (date/label/offset/...)
     */
    public static function compatibility(
        \DateTimeImmutable $birth,
        \DateTimeImmutable $partner,
        \DateTimeImmutable $focus,
        array $focusValues,
        array $forecastDays,
    ): array {
        $bio     = new Biorrhythms();
        $current = Timeline::values($bio, $partner, $focus);

        $forecast = array_map(static function (array $point) use ($bio, $birth, $partner): array {
            $date = DateInput::parse($point['date']);
            $a    = Timeline::values($bio, $birth, $date);
            $b    = Timeline::values($bio, $partner, $date);

            $rhythms = [
                ['label' => 'Físico',      'value' => Compatibility::score($a['physical'], $b['physical'])],
                ['label' => 'Emocional',   'value' => Compatibility::score($a['emotional'], $b['emotional'])],
                ['label' => 'Intelectual', 'value' => Compatibility::score($a['intellectual'], $b['intellectual'])],
            ];

            $point['score']   = array_sum(array_column($rhythms, 'value')) / count($rhythms);
            $point['rhythms'] = $rhythms;

            return $point;
        }, $forecastDays);

        $best = $worst = $forecast[0];
        foreach ($forecast as $point) {
            if ($point['score'] > $best['score']) {
                $best = $point;
            }
            if ($point['score'] < $worst['score']) {
                $worst = $point;
            }
        }

        return [
            'score'   => Compatibility::pointScore($focusValues, $current),
            'current' => [
                'physical'     => Compatibility::score($focusValues['physical'], $current['physical']),
                'emotional'    => Compatibility::score($focusValues['emotional'], $current['emotional']),
                'intellectual' => Compatibility::score($focusValues['intellectual'], $current['intellectual']),
            ],
            'forecast'  => $forecast,
            'best_day'  => $best,
            'worst_day' => $worst,
        ];
    }

    /**
     * v1-only compatibility additions: preset weights, per-rhythm trend agreement and a day-by-day sync map.
     *
     * @return array{preset: string, weights: array, weighted_score: float, trend_agreement: array, sync_map: list<array>}
     */
    public static function compatibilityExtras(
        \DateTimeImmutable $birth,
        \DateTimeImmutable $partner,
        \DateTimeImmutable $focus,
        string $preset,
    ): array {
        $bio     = new Biorrhythms();
        $a       = Timeline::values($bio, $birth, $focus);
        $b       = Timeline::values($bio, $partner, $focus);
        $slopesA = [];
        $slopesB = [];
        foreach (array_keys(Rhythm::PERIODS) as $key) {
            $slopesA[$key] = Rhythm::phase($key, DateInput::daysBetween($birth, $focus))['slope'];
            $slopesB[$key] = Rhythm::phase($key, DateInput::daysBetween($partner, $focus))['slope'];
        }

        $syncMap = [];
        for ($offset = -self::WINDOW_RADIUS; $offset <= self::WINDOW_RADIUS; $offset++) {
            $date = $focus->modify(sprintf('%+d day', $offset));
            $pa   = Timeline::values($bio, $birth, $date);
            $pb   = Timeline::values($bio, $partner, $date);

            $syncMap[] = [
                'date'           => $date->format('Y-m-d'),
                'score'          => Compatibility::pointScore($pa, $pb),
                'weighted_score' => Compatibility::weightedScore($pa, $pb, $preset),
            ];
        }

        return [
            'preset'          => $preset,
            'weights'         => Compatibility::weights($preset),
            'weighted_score'  => Compatibility::weightedScore($a, $b, $preset),
            'trend_agreement' => Compatibility::trendAgreement($slopesA, $slopesB),
            'sync_map'        => $syncMap,
        ];
    }
}
