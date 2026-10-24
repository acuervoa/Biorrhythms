<?php

declare(strict_types=1);

namespace Biorrhythms;

/** Classic composite readings: pairs and the full mean of the three rhythms, each in [-1, 1]. */
class Composites
{
    /**
     * @param array{physical: float, emotional: float, intellectual: float} $point
     * @return array{passion: float, wisdom: float, mastery: float}
     */
    public static function values(array $point): array
    {
        return [
            'passion' => ($point['physical'] + $point['emotional']) / 2.0,
            'wisdom'  => ($point['emotional'] + $point['intellectual']) / 2.0,
            'mastery' => ($point['physical'] + $point['emotional'] + $point['intellectual']) / 3.0,
        ];
    }
}
