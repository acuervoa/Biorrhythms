<?php

declare(strict_types=1);

use Biorrhythms\IcsFeed;
use PHPUnit\Framework\TestCase;

class IcsFeedTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-10-24 10:00:00', new DateTimeZone('UTC'));
    }

    public function testMissingBirthIs400PlainText(): void
    {
        [$status, $type, $body] = IcsFeed::handle([], $this->now);

        $this->assertSame(400, $status);
        $this->assertStringStartsWith('text/plain', $type);
        $this->assertStringContainsString('birth', $body);
    }

    public function testInvalidDatesAre400(): void
    {
        $this->assertSame(400, IcsFeed::handle(['birth' => '2026-02-31'], $this->now)[0]);
        $this->assertSame(400, IcsFeed::handle(['birth' => '1990-01-01', 'from' => 'x'], $this->now)[0]);
    }

    public function testValidRequestReturnsCalendar(): void
    {
        [$status, $type, $body] = IcsFeed::handle(['birth' => '1990-01-01', 'from' => '2026-10-01', 'days' => '30'], $this->now);

        $this->assertSame(200, $status);
        $this->assertStringStartsWith('text/calendar', $type);
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $body);
        $this->assertGreaterThan(0, substr_count($body, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('DTSTART;VALUE=DATE:2026', $body);
    }

    public function testFromDefaultsToToday(): void
    {
        [, , $body] = IcsFeed::handle(['birth' => '1990-01-01', 'days' => '60'], $this->now);

        preg_match_all('/DTSTART;VALUE=DATE:(\d{8})/', $body, $m);
        $this->assertNotEmpty($m[1]);
        $this->assertGreaterThanOrEqual('20261024', min($m[1]));
        $this->assertLessThanOrEqual('20261224', max($m[1]));
    }

    public function testDaysIsClampedAndMinFilters(): void
    {
        [, , $all] = IcsFeed::handle(['birth' => '1990-01-01', 'from' => '2026-01-01', 'days' => '99999'], $this->now);
        [, , $double] = IcsFeed::handle(['birth' => '1990-01-01', 'from' => '2026-01-01', 'days' => '366', 'min' => '2'], $this->now);

        preg_match_all('/DTSTART;VALUE=DATE:(\d{8})/', $all, $m);
        $this->assertLessThanOrEqual('20270102', max($m[1]));
        $this->assertLessThan(substr_count($all, 'BEGIN:VEVENT'), substr_count($double, 'BEGIN:VEVENT') + 1);
    }

    public function testInvalidMinIs400(): void
    {
        $this->assertSame(400, IcsFeed::handle(['birth' => '1990-01-01', 'min' => '4'], $this->now)[0]);
        $this->assertSame(400, IcsFeed::handle(['birth' => '1990-01-01', 'min' => 'x'], $this->now)[0]);
    }
}
