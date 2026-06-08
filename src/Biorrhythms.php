<?php

declare(strict_types=1);

namespace Biorrhythms;

class Biorrhythms
{
    public function calculatePhysical(float $time): float
    {
        return sin(2 * M_PI * $time / 23);
    }

    public function calculateEmotional(float $time): float
    {
        return sin(2 * M_PI * $time / 28);
    }

    public function calculateIntellectual(float $time): float
    {
        return sin(2 * M_PI * $time / 33);
    }
}
