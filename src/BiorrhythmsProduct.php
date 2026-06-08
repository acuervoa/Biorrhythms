<?php

declare(strict_types=1);

namespace Biorrhythms;

require_once __DIR__ . '/Biorrhythms.php';

function biorrhythms_clamp_date_input(?string $value, string $fallback): string
{
    if ($value === null || $value === '') {
        return $fallback;
    }

    $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if ($date === false) {
        return $fallback;
    }

    return $date->format('Y-m-d');
}

function biorrhythms_days_between(\DateTimeImmutable $from, \DateTimeImmutable $to): float
{
    return ($to->getTimestamp() - $from->getTimestamp()) / 86400;
}

function biorrhythms_compatibility_score(float $a, float $b): float
{
    return max(0.0, 1 - abs($a - $b) / 2);
}

function biorrhythms_point_compatibility(array $a, array $b): float
{
    return (
        biorrhythms_compatibility_score($a['physical'], $b['physical']) +
        biorrhythms_compatibility_score($a['emotional'], $b['emotional']) +
        biorrhythms_compatibility_score($a['intellectual'], $b['intellectual'])
    ) / 3;
}

function biorrhythms_partner_birth_for_preset(string $preset, \DateTimeImmutable $focusDate): ?string
{
    $shiftDays = [
        'pair' => 0,
        'friend' => -42,
        'work' => 73,
    ][$preset] ?? null;

    if ($shiftDays === null) {
        return null;
    }

    return $focusDate->modify(($shiftDays >= 0 ? '+' : '') . $shiftDays . ' day')->format('Y-m-d');
}

function biorrhythms_average_series(array $point): float
{
    return ($point['physical'] + $point['emotional'] + $point['intellectual']) / 3;
}

function biorrhythms_dominant_series(array $point): array
{
    $candidates = [
        ['key' => 'physical', 'label' => 'Físico', 'value' => $point['physical']],
        ['key' => 'emotional', 'label' => 'Emocional', 'value' => $point['emotional']],
        ['key' => 'intellectual', 'label' => 'Intelectual', 'value' => $point['intellectual']],
    ];

    return array_reduce(
        $candidates,
        static fn (array $carry, array $item): array => $item['value'] > $carry['value'] ? $item : $carry,
        $candidates[0]
    );
}
