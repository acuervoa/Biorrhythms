<?php

declare(strict_types=1);

namespace Biorrhythms;

/**
 * Payload of the unversioned /api/ endpoint.
 *
 * Frozen contract: invalid or missing dates silently fall back to defaults and
 * the profile label stays in English. New behaviour goes into ApiV1.
 */
class ApiLegacy
{
    public static function build(array $query, \DateTimeImmutable $now): array
    {
        $birthInput   = DateInput::normalize($query['birth'] ?? null, '1990-01-01');
        $focusInput   = DateInput::normalize($query['focus'] ?? null, $now->format('Y-m-d'));
        $partnerInput = DateInput::normalize($query['partner_birth'] ?? null, '1991-01-01');

        $birth   = DateInput::parse($birthInput);
        $focus   = DateInput::parse($focusInput);
        $partner = DateInput::parse($partnerInput);

        $individual = ApiPayload::individual($birth, $focus);

        $appLinkParams    = ['birth' => $birthInput, 'focus' => $focusInput, 'partner_birth' => $partnerInput];
        $widgetLinkParams = $appLinkParams + ['embed' => '1'];

        return [
            'meta' => [
                'generated_at'  => $now->format(DATE_ATOM),
                'birth'         => $birthInput,
                'focus'         => $focusInput,
                'partner_birth' => $partnerInput,
                'window_radius' => ApiPayload::WINDOW_RADIUS,
            ],
            'profile' => [
                'label'        => Profile::labelEn($individual['selected']),
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
            'compatibility' => ApiPayload::compatibility(
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
        ];
    }
}
