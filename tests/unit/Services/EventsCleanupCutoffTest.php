<?php

declare(strict_types=1);

namespace dwy\FacebookConversion\Tests\unit\Services;

use DateTimeImmutable;
use DateTimeZone;
use dwy\FacebookConversion\services\Events;
use PHPUnit\Framework\TestCase;

final class EventsCleanupCutoffTest extends TestCase
{
    public function test_thirty_day_window_subtracts_thirty_days(): void
    {
        $now = new DateTimeImmutable('2026-05-05 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame(
            '2026-04-05 12:00:00',
            Events::computeCutoff(30, $now)
        );
    }

    public function test_one_day_window(): void
    {
        $now = new DateTimeImmutable('2026-05-05 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame(
            '2026-05-04 12:00:00',
            Events::computeCutoff(1, $now)
        );
    }

    public function test_max_window_3650_days(): void
    {
        $now = new DateTimeImmutable('2026-05-05 12:00:00', new DateTimeZone('UTC'));

        $this->assertSame(
            '2016-05-07 12:00:00',
            Events::computeCutoff(3650, $now)
        );
    }

    public function test_leap_year_boundary(): void
    {
        // 2024 is a leap year; subtracting 365 days from 2025-03-01 lands on 2024-03-01.
        $now = new DateTimeImmutable('2025-03-01 00:00:00', new DateTimeZone('UTC'));

        $this->assertSame(
            '2024-03-01 00:00:00',
            Events::computeCutoff(365, $now)
        );
    }

    public function test_preserves_microsecond_independent_format(): void
    {
        // Format is Y-m-d H:i:s — no microseconds, no timezone offset suffix.
        $now = new DateTimeImmutable('2026-05-05 12:34:56', new DateTimeZone('UTC'));

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            Events::computeCutoff(7, $now)
        );
    }
}
