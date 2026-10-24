<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Strict /api/v1/ endpoint: invalid input is a 400, never a silent default. */
class ApiV1
{
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

        $individual = ApiPayload::individual($birth, $focus);

        $appLinkParams = ['birth' => $birth->format('Y-m-d'), 'focus' => $focus->format('Y-m-d')];
        if ($partner !== null) {
            $appLinkParams['partner_birth'] = $partner->format('Y-m-d');
        }
        if ($preset !== 'custom') {
            $appLinkParams['preset'] = $preset;
        }
        $widgetLinkParams = $appLinkParams + ['embed' => '1'];

        return [200, [
            'meta' => [
                'generated_at'  => $now->format(DATE_ATOM),
                'birth'         => $birth->format('Y-m-d'),
                'focus'         => $focus->format('Y-m-d'),
                'partner_birth' => $partner?->format('Y-m-d'),
                'preset'        => $preset,
                'window_radius' => ApiPayload::WINDOW_RADIUS,
            ],
            'profile' => [
                'key'          => Profile::key($individual['selected']),
                'label'        => Profile::labelEs($individual['selected']),
                'selected'     => $individual['selected'],
                'focus_values' => $individual['focus_values'],
            ],
            'timeline' => ['window' => $individual['window']],
            'forecast' => [
                'days'      => $individual['forecast'],
                'best_day'  => $individual['best'],
                'worst_day' => $individual['worst'],
            ],
            'ritual'        => $individual['ritual'],
            'compatibility' => $partner === null ? null : ApiPayload::compatibility(
                $birth,
                $partner,
                $focus,
                $individual['focus_values'],
                $individual['forecast'],
            ),
            'links' => [
                'app'    => '/?' . http_build_query($appLinkParams, '', '&', PHP_QUERY_RFC3986),
                'widget' => '/?' . http_build_query($widgetLinkParams, '', '&', PHP_QUERY_RFC3986),
            ],
        ]];
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
