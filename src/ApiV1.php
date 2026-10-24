<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Strict /api/v1/ endpoint: invalid input is a 400, never a silent default. */
class ApiV1
{
    private const EXTREME_MIN = 0.5;
    private const EXTREME_MAX = 0.99;

    /**
     * @return array{0: int, 1: array} HTTP status and JSON body
     */
    public static function handle(array $query, \DateTimeImmutable $now): array
    {
        $birth = self::requiredDate($query, 'birth', $error);
        if ($error !== null) {
            return [400, $error];
        }

        $focus = self::optionalDate($query, 'focus', $error) ?? DateInput::parse($now->format('Y-m-d'));
        if ($error !== null) {
            return [400, $error];
        }

        $partner = self::optionalDate($query, 'partner_birth', $error);
        if ($error !== null) {
            return [400, $error];
        }

        $preset = $query['preset'] ?? 'custom';
        if ($preset === '') {
            $preset = 'custom';
        }
        if (!is_string($preset) || !in_array($preset, ApiPayload::PRESETS, true)) {
            return [400, self::error('invalid_value', 'preset', 'preset debe ser uno de: ' . implode(', ', ApiPayload::PRESETS) . '.')];
        }

        $extreme = null;
        $rawExtreme = $query['extreme'] ?? null;
        if ($rawExtreme !== null && $rawExtreme !== '') {
            if (!is_string($rawExtreme) || !is_numeric($rawExtreme)
                || (float) $rawExtreme < self::EXTREME_MIN || (float) $rawExtreme > self::EXTREME_MAX) {
                return [400, self::error('invalid_value', 'extreme', sprintf('extreme debe ser un número entre %s y %s.', self::EXTREME_MIN, self::EXTREME_MAX))];
            }
            $extreme = (float) $rawExtreme;
        }

        $individual = ApiPayload::individual($birth, $focus);
        $selected   = $individual['selected'];
        $horizon    = $focus->modify('+' . ApiPayload::WINDOW_RADIUS . ' day');
        $reading    = DailyReading::forDate($birth, $focus, $individual['window']);
        $personal   = $reading['personal'];

        $phases = [];
        foreach (array_keys(Rhythm::PERIODS) as $key) {
            $phases[$key] = Rhythm::phase($key, DateInput::daysBetween($birth, $focus));
        }

        $appLinkParams = ['birth' => $birth->format('Y-m-d'), 'focus' => $focus->format('Y-m-d')];
        if ($partner !== null) {
            $appLinkParams['partner_birth'] = $partner->format('Y-m-d');
        }
        if ($preset !== 'custom') {
            $appLinkParams['preset'] = $preset;
        }
        $widgetLinkParams = $appLinkParams + ['embed' => '1'];

        $body = [
            'meta' => [
                'generated_at'  => $now->format(DATE_ATOM),
                'birth'         => $birth->format('Y-m-d'),
                'focus'         => $focus->format('Y-m-d'),
                'partner_birth' => $partner?->format('Y-m-d'),
                'preset'        => $preset,
                'window_radius' => ApiPayload::WINDOW_RADIUS,
            ],
            'profile' => [
                'key'          => Profile::key($selected),
                'label'        => Profile::labelEs($selected),
                'strongest'    => Profile::strongest($selected),
                'all_negative' => Forecast::allNegative($selected),
                'conflict'     => Forecast::hasConflict($selected),
                'spread'       => Forecast::spread($selected),
                'composites'   => Composites::values($selected),
                'phases'       => $phases,
                'selected'     => $selected,
                'focus_values' => $individual['focus_values'],
            ],
            'critical_days' => CriticalDays::find($birth, $focus, $horizon),
            'timeline' => ['window' => $individual['window']],
            'forecast' => [
                'days'      => $individual['forecast'],
                'best_day'  => $individual['best'],
                'worst_day' => $individual['worst'],
            ],
            'ritual'           => $reading['ritual'],
            'calendar_context' => [
                'sun_sign'   => Zodiac::sunSign($birth),
                'life_path'  => ['number' => Numerology::lifePath($birth)] + Numerology::meaning(Numerology::lifePath($birth)),
                'personal'   => $personal + [
                    'year_name'    => Numerology::meaning($personal['year'])['name'],
                    'month_name'   => Numerology::meaning($personal['month'])['name'],
                    'day_name'     => Numerology::meaning($personal['day'])['name'],
                    'day_keywords' => Numerology::meaning($personal['day'])['keywords'],
                ],
                'moon'       => Lunar::phase($focus),
                'birth_moon' => Lunar::phase($birth),
            ],
            'compatibility' => $partner === null ? null : ApiPayload::compatibility(
                $birth,
                $partner,
                $focus,
                $individual['focus_values'],
                $individual['forecast'],
            ) + ApiPayload::compatibilityExtras($birth, $partner, $focus, $preset),
            'links' => [
                'app'    => '/?' . http_build_query($appLinkParams, '', '&', PHP_QUERY_RFC3986),
                'widget' => '/?' . http_build_query($widgetLinkParams, '', '&', PHP_QUERY_RFC3986),
            ],
        ];

        if ($extreme !== null) {
            $body['extreme_days'] = [
                'threshold' => $extreme,
                'days'      => ExtremeDays::find(new Biorrhythms(), $birth, $focus, $extreme),
            ];
        }

        return [200, $body];
    }

    private static function requiredDate(array $query, string $field, ?array &$error): ?\DateTimeImmutable
    {
        $error = null;
        $value = $query[$field] ?? null;

        if ($value === null || $value === '') {
            $error = self::error('missing_parameter', $field, "Falta el parámetro obligatorio {$field} (YYYY-MM-DD).");
            return null;
        }

        return self::parseOrError($value, $field, $error);
    }

    private static function optionalDate(array $query, string $field, ?array &$error): ?\DateTimeImmutable
    {
        $error = null;
        $value = $query[$field] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return self::parseOrError($value, $field, $error);
    }

    private static function parseOrError(mixed $value, string $field, ?array &$error): ?\DateTimeImmutable
    {
        $date = DateInput::parse($value);
        if ($date === null) {
            $error = self::error(
                'invalid_date',
                $field,
                "{$field} debe ser una fecha real YYYY-MM-DD entre " . DateInput::MIN . ' y ' . DateInput::MAX . '.',
            );
        }

        return $date;
    }

    private static function error(string $code, string $field, string $message): array
    {
        return ['error' => ['code' => $code, 'field' => $field, 'message' => $message]];
    }
}
