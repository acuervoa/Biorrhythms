<?php

declare(strict_types=1);

use Biorrhythms\Composites;
use PHPUnit\Framework\TestCase;

class CompositesTest extends TestCase
{
    public function testValues(): void
    {
        $values = Composites::values(['physical' => 0.6, 'emotional' => 0.2, 'intellectual' => -0.8]);

        $this->assertEqualsWithDelta(0.4, $values['passion'], 1e-12);
        $this->assertEqualsWithDelta(-0.3, $values['wisdom'], 1e-12);
        $this->assertEqualsWithDelta(0.0, $values['mastery'], 1e-12);
    }

    public function testStayWithinUnitRange(): void
    {
        $values = Composites::values(['physical' => 1.0, 'emotional' => 1.0, 'intellectual' => 1.0]);

        $this->assertEqualsWithDelta(1.0, $values['passion'], 1e-12);
        $this->assertEqualsWithDelta(1.0, $values['wisdom'], 1e-12);
        $this->assertEqualsWithDelta(1.0, $values['mastery'], 1e-12);
    }
}
